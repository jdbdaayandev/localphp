<?php

declare(strict_types=1);

namespace LocalPHP\Database;

use PDO;
use RuntimeException;
use Throwable;

final class MigrationManager
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $migrationsPath
    ) {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createRepository();
    }

    private function createRepository(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS `migrations` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL UNIQUE,
                `batch` INT NOT NULL,
                `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB'
        );
    }

    public function migrate(): int
    {
        $files = glob($this->migrationsPath . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files, SORT_STRING);

        $completed = $this->completed();
        $batch = $this->currentBatch() + 1;
        $count = 0;

        foreach ($files as $file) {
            $name = basename($file);

            if (isset($completed[$name])) {
                continue;
            }

            $migration = require $file;

            if (!$migration instanceof Migration) {
                throw new RuntimeException(
                    "Migration [{$name}] must return an instance of "
                    . Migration::class . '.'
                );
            }

            $this->pdo->beginTransaction();

            try {
                $migration->up($this->pdo);

                $statement = $this->pdo->prepare(
                    'INSERT INTO `migrations` (`migration`, `batch`)
                     VALUES (:migration, :batch)'
                );

                $statement->execute([
                    'migration' => $name,
                    'batch' => $batch,
                ]);

                $this->pdo->commit();

                echo "Migrated: {$name}" . PHP_EOL;
                $count++;
            } catch (Throwable $exception) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                throw new RuntimeException(
                    "Migration failed: {$name}. " . $exception->getMessage(),
                    0,
                    $exception
                );
            }
        }

        if ($count === 0) {
            echo "Nothing to migrate." . PHP_EOL;
        }

        return $count;
    }

    public function status(): void
    {
        $files = glob($this->migrationsPath . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files, SORT_STRING);

        $completed = $this->completed();

        echo str_pad('Migration', 55)
            . str_pad('Status', 12)
            . PHP_EOL;

        echo str_repeat('-', 67) . PHP_EOL;

        foreach ($files as $file) {
            $name = basename($file);
            $status = isset($completed[$name]) ? 'Ran' : 'Pending';

            echo str_pad($name, 55)
                . str_pad($status, 12)
                . PHP_EOL;
        }

        if ($files === []) {
            echo "No migration files found." . PHP_EOL;
        }
    }

    public function rollback(): int
    {
        $statement = $this->pdo->query(
            'SELECT `migration`
             FROM `migrations`
             WHERE `batch` = (
                 SELECT MAX(`batch`) FROM `migrations`
             )
             ORDER BY `id` DESC'
        );

        $names = $statement->fetchAll(PDO::FETCH_COLUMN);

        if ($names === []) {
            echo "Nothing to roll back." . PHP_EOL;
            return 0;
        }

        $count = 0;

        foreach ($names as $name) {
            $file = $this->migrationsPath . DIRECTORY_SEPARATOR . $name;

            if (!is_file($file)) {
                throw new RuntimeException(
                    "Cannot roll back [{$name}]: migration file is missing."
                );
            }

            $migration = require $file;

            if (!$migration instanceof Migration) {
                throw new RuntimeException(
                    "Migration [{$name}] does not return a valid Migration."
                );
            }

            $this->pdo->beginTransaction();

            try {
                $migration->down($this->pdo);

                $delete = $this->pdo->prepare(
                    'DELETE FROM `migrations` WHERE `migration` = :migration'
                );
                $delete->execute(['migration' => $name]);

                $this->pdo->commit();

                echo "Rolled back: {$name}" . PHP_EOL;
                $count++;
            } catch (Throwable $exception) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                throw new RuntimeException(
                    "Rollback failed: {$name}. " . $exception->getMessage(),
                    0,
                    $exception
                );
            }
        }

        return $count;
    }

    public function fresh(bool $force = false): void
    {
        if (!$force) {
            echo "WARNING: migrate:fresh drops every table in the database."
                . PHP_EOL;
            echo "Run php local migrate:fresh --force to confirm."
                . PHP_EOL;
            return;
        }

        $database = (string) env('DB_DATABASE', 'localphp');

        $statement = $this->pdo->prepare(
            'SELECT TABLE_NAME
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = :database'
        );
        $statement->execute(['database' => $database]);

        $tables = $statement->fetchAll(PDO::FETCH_COLUMN);

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach ($tables as $table) {
                $quoted = '`' . str_replace('`', '``', $table) . '`';
                $this->pdo->exec("DROP TABLE IF EXISTS {$quoted}");
            }
        } finally {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        echo "Database tables dropped." . PHP_EOL;
        $this->createRepository();
        $this->migrate();
    }

    private function completed(): array
    {
        $statement = $this->pdo->query(
            'SELECT `migration` FROM `migrations`'
        );

        $names = $statement->fetchAll(PDO::FETCH_COLUMN);

        return array_fill_keys($names, true);
    }

    private function currentBatch(): int
    {
        return (int) $this->pdo->query(
            'SELECT COALESCE(MAX(`batch`), 0) FROM `migrations`'
        )->fetchColumn();
    }
}