<?php

declare(strict_types=1);
namespace LocalPHP\Database;

use InvalidArgumentException;

final class Blueprint
{
    private array $columns = []; private array $indexes = []; private array $foreignKeys = [];
    public function __construct(public readonly string $table) {}
    private function add(string $name,string $type,?int $length=null): ColumnDefinition { $this->assertIdentifier($name); return $this->columns[$name] = new ColumnDefinition($name,$type,$length); }
    public function id(string $name='id'): ColumnDefinition { return $this->add($name,'BIGINT UNSIGNED')->autoIncrement()->primary(); }
    public function bigIncrements(string $name): ColumnDefinition { return $this->id($name); }
    public function increments(string $name): ColumnDefinition { return $this->add($name,'INT')->autoIncrement()->primary(); }
    public function string(string $name,int $length=255): ColumnDefinition { return $this->add($name,'VARCHAR',$length); }
    public function char(string $name,int $length=1): ColumnDefinition { return $this->add($name,'CHAR',$length); }
    public function text(string $name): ColumnDefinition { return $this->add($name,'TEXT'); }
    public function mediumText(string $name): ColumnDefinition { return $this->add($name,'MEDIUMTEXT'); }
    public function longText(string $name): ColumnDefinition { return $this->add($name,'LONGTEXT'); }
    public function integer(string $name): ColumnDefinition { return $this->add($name,'INT'); }
    public function bigInteger(string $name): ColumnDefinition { return $this->add($name,'BIGINT'); }
    public function tinyInteger(string $name): ColumnDefinition { return $this->add($name,'TINYINT'); }
    public function boolean(string $name): ColumnDefinition { return $this->add($name,'TINYINT',1); }
    public function decimal(string $name,int $precision=8,int $scale=2): ColumnDefinition { return $this->add($name,"DECIMAL({$precision},{$scale})"); }
    public function float(string $name): ColumnDefinition { return $this->add($name,'FLOAT'); }
    public function double(string $name): ColumnDefinition { return $this->add($name,'DOUBLE'); }
    public function date(string $name): ColumnDefinition { return $this->add($name,'DATE'); }
    public function dateTime(string $name): ColumnDefinition { return $this->add($name,'DATETIME'); }
    public function timestamp(string $name): ColumnDefinition { return $this->add($name,'TIMESTAMP'); }
    public function json(string $name): ColumnDefinition { return $this->add($name,'JSON'); }
    public function timestamps(): void { $this->timestamp('created_at')->nullable()->default(null); $this->timestamp('updated_at')->nullable()->default(null); }
    public function softDeletes(string $name='deleted_at'): ColumnDefinition { return $this->timestamp($name)->nullable(); }
    public function unique(string|array $columns,?string $name=null): self { $this->indexes[]=['UNIQUE',$columns,$name]; return $this; }
    public function index(string|array $columns,?string $name=null): self { $this->indexes[]=['INDEX',$columns,$name]; return $this; }
    public function primaryKey(string|array $columns): self { $this->indexes[]=['PRIMARY',$columns,null]; return $this; }
    public function foreign(string $column): ForeignKeyDefinition { $this->assertIdentifier($column); return $this->foreignKeys[] = new ForeignKeyDefinition($column); }
    public function columns(): array { return $this->columns; } public function indexes(): array { return $this->indexes; } public function foreignKeys(): array { return $this->foreignKeys; }
    private function assertIdentifier(string $name): void { if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/',$name)) throw new InvalidArgumentException("Invalid SQL identifier [{$name}]."); }
}
