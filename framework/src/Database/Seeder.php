<?php

declare(strict_types=1);

namespace LocalPHP\Database;

use PDO;

/**
 * Class Seeder
 *
 * Base class for all LocalPHP database seeders.
 *
 * Seeders populate the database with initial, default,
 * or development data.
 *
 * Example:
 *
 * php local db:seed
 */
abstract class Seeder
{
    /**
     * Run the database seeder.
     *
     * All seeders receive the active PDO connection.
     *
     * @param PDO $pdo
     * @return void
     */
    abstract public function run(PDO $pdo): void;
}