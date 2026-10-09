<?php

declare(strict_types=1);
namespace LocalPHP\Database;

use InvalidArgumentException;

final class Schema
{
    private function __construct() {}
    public static function create(string $table, callable $callback): void { self::build($table,$callback,false); }
    public static function table(string $table, callable $callback): void { self::build($table,$callback,true); }
    public static function drop(string $table): void { self::pdo()->exec('DROP TABLE IF EXISTS '.self::quote($table)); }
    public static function dropIfExists(string $table): void { self::drop($table); }
    private static function build(string $table,callable $callback,bool $alter): void
    {
        $blueprint=new Blueprint($table); $callback($blueprint); $pdo=self::pdo();
        if (!$alter) { $parts=[]; foreach($blueprint->columns() as $column) $parts[]=self::columnSql($column); foreach($blueprint->indexes() as [$type,$columns,$name]) $parts[]=self::indexSql($type,$columns,$name); foreach($blueprint->foreignKeys() as $fk) $parts[]=self::foreignSql($fk); $sql='CREATE TABLE '.self::quote($table).' ('.implode(', ',$parts).') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'; $pdo->exec($sql); return; }
        foreach($blueprint->columns() as $column) $pdo->exec('ALTER TABLE '.self::quote($table).' ADD COLUMN '.self::columnSql($column));
        foreach($blueprint->indexes() as [$type,$columns,$name]) $pdo->exec('ALTER TABLE '.self::quote($table).' ADD '.self::indexSql($type,$columns,$name));
        foreach($blueprint->foreignKeys() as $fk) $pdo->exec('ALTER TABLE '.self::quote($table).' ADD '.self::foreignSql($fk));
    }
    private static function columnSql(ColumnDefinition $c): string
    {
        $type=$c->type; if ($c->length !== null) $type.='('.$c->length.')';
        $sql=self::quote($c->name).' '.$type.($c->unsigned?' UNSIGNED':'').($c->autoIncrement?' AUTO_INCREMENT':'').($c->nullable?' NULL':' NOT NULL');
        if($c->hasDefault) $sql.=$c->default===null?' DEFAULT NULL':(is_int($c->default)||is_float($c->default)?' DEFAULT '.$c->default:" DEFAULT ".self::pdo()->quote((string)$c->default));
        if($c->primary) $sql.=' PRIMARY KEY'; return $sql;
    }
    private static function indexSql(string $type,string|array $columns,?string $name): string
    { $cols=(array)$columns; foreach($cols as $col) self::assertIdentifier((string)$col); $label=$name?self::quote($name).' ':''; return match($type){'PRIMARY'=>'PRIMARY KEY ('.implode(', ',array_map([self::class,'quote'],$cols)).')','UNIQUE'=>'UNIQUE '.$label.'('.implode(', ',array_map([self::class,'quote'],$cols)).')',default=>'INDEX '.$label.'('.implode(', ',array_map([self::class,'quote'],$cols)).')'}; }
    private static function foreignSql(ForeignKeyDefinition $f): string { if(!$f->referenceTable||!$f->referenceColumn) throw new InvalidArgumentException('Foreign key must define references() and on().'); return 'FOREIGN KEY ('.self::quote($f->column).') REFERENCES '.self::quote($f->referenceTable).' ('.self::quote($f->referenceColumn).') ON DELETE '.$f->onDelete.' ON UPDATE '.$f->onUpdate; }
    private static function quote(string $id): string { self::assertIdentifier($id); return '`'.$id.'`'; }
    private static function assertIdentifier(string $id): void { if(!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/',$id)) throw new InvalidArgumentException("Invalid SQL identifier [{$id}]."); }
    private static function pdo(): \PDO { return DB::connection()->pdo(); }
}
