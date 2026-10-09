<?php

declare(strict_types=1);

namespace LocalPHP\Database;

use PDO;
use PDOStatement;
use RuntimeException;

class Connection
{
    protected PDO $pdo;

    public function __construct(array $config)
    {
        if (($config['driver'] ?? 'mysql') !== 'mysql') {
            throw new RuntimeException(
                'Unsupported database driver.'
            );
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = (int) ($config['port'] ?? 3306);
        $database = $config['database'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        if ($database === '') {
            throw new RuntimeException(
                'The database name is not configured.'
            );
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $database,
            $charset
        );

        $options = $config['options'] ?? [];

        $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
        $options[PDO::ATTR_EMULATE_PREPARES] = false;

        $this->pdo = new PDO(
            $dsn,
            $config['username'] ?? '',
            $config['password'] ?? '',
            $options
        );
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function select(
        string $sql,
        array $bindings = []
    ): array {
        return $this->statement(
            $sql,
            $bindings
        )->fetchAll();
    }

    public function selectOne(
        string $sql,
        array $bindings = []
    ): ?array {
        $result = $this->statement(
            $sql,
            $bindings
        )->fetch();

        return $result === false ? null : $result;
    }

    public function execute(
        string $sql,
        array $bindings = []
    ): int {
        return $this->statement(
            $sql,
            $bindings
        )->rowCount();
    }

    public function insert(
        string $sql,
        array $bindings = []
    ): string|false {
        $this->statement($sql, $bindings);

        return $this->pdo->lastInsertId();
    }

    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        if (!$this->pdo->inTransaction()) {
            return false;
        }

        return $this->pdo->rollBack();
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);

            $this->commit();

            return $result;
        } catch (\Throwable $exception) {
            $this->rollBack();

            throw $exception;
        }
    }

    protected function statement(
        string $sql,
        array $bindings = []
    ): PDOStatement {
        $statement = $this->pdo->prepare($sql);

        foreach ($bindings as $key => $value) {
            $parameter = is_int($key)
                ? $key + 1
                : ':' . ltrim((string) $key, ':');

            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };

            $statement->bindValue(
                $parameter,
                $value,
                $type
            );
        }

        $statement->execute();

        return $statement;
    }
}