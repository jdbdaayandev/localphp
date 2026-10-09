<?php

declare(strict_types=1);

namespace LocalPHP\Database;

use Closure;

/**
 * Static database entry point for LocalPHP applications.
 *
 * Example: DB::table('users')->where('active', true)->get();
 */
final class DB
{
    private function __construct()
    {
    }

    protected static function manager(): DatabaseManager
    {
        /** @var DatabaseManager $manager */
        $manager = \app(DatabaseManager::class);
        return $manager;
    }

    public static function table(string $table): QueryBuilder
    {
        return self::manager()->table($table);
    }

    public static function connection(?string $name = null): Connection
    {
        return self::manager()->connection($name);
    }

    public static function raw(string $sql, array $bindings = []): RawExpression
    {
        return new RawExpression($sql, $bindings);
    }

    public static function beginTransaction(): bool
    {
        return self::connection()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::connection()->commit();
    }

    public static function rollBack(): bool
    {
        return self::connection()->rollBack();
    }

    public static function transaction(callable $callback): mixed
    {
        return self::connection()->transaction($callback);
    }

    public static function disconnect(?string $name = null): void
    {
        self::manager()->disconnect($name);
    }
}
