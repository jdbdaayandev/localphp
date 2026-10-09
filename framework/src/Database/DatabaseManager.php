<?php

declare(strict_types=1);

namespace LocalPHP\Database;

use RuntimeException;

class DatabaseManager
{
    protected array $config;

    protected array $connections = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function connection(
        ?string $name = null
    ): Connection {
        $name ??= $this->config['default'] ?? 'mysql';

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $connectionConfig =
            $this->config['connections'][$name] ?? null;

        if (!is_array($connectionConfig)) {
            throw new RuntimeException(
                "Database connection [{$name}] is not configured."
            );
        }

        return $this->connections[$name] =
            new Connection($connectionConfig);
    }

    public function table(string $table): QueryBuilder
    {
        return QueryBuilder::table(
            $this->connection(),
            $table
        );
    }

    public function disconnect(
        ?string $name = null
    ): void {
        if ($name === null) {
            $this->connections = [];

            return;
        }

        unset($this->connections[$name]);
    }
}