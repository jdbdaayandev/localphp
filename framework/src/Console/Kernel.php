<?php

declare(strict_types=1);

namespace LocalPHP\Console;

use LocalPHP\Database\MigrationManager;
use LocalPHP\Database\Seeder;
use PDO;
use RuntimeException;
use Throwable;
use FilesystemIterator;

final class Kernel
{
    private ?PDO $pdo = null;

    public function __construct(
        private readonly string $basePath
    ) {
        // Database connection is lazy.
        // Non-database commands can run without MySQL.
    }

    /**
     * Run a CLI command.
     */
    public function run(array $argv): int
    {
        $command = $argv[1] ?? 'list';
        $arguments = array_slice($argv, 2);

        try {
            return match ($command) {
                'list', 'help' => $this->listCommands(),
                'about' => $this->about(),
                'config:cache' => $this->cacheConfig(),
                'config:clear' => $this->clearConfigCache(),
                'optimize' => $this->optimize(),
                'optimize:clear' => $this->clean(['cache']),

                'make:migration' => $this->makeMigration(
                    $arguments[0] ?? ''
                ),
                'migrate' => $this->migrations()->migrate() >= 0 ? 0 : 1,
                'migrate:status' => $this->showMigrationStatus(),
                'migrate:rollback' => $this->rollback(),
                'migrate:fresh' => $this->fresh($arguments),
                'make:controller' => $this->makeController($argv[2] ?? ''),
                'make:model' => $this->makeModel($argv[2] ?? ''),
                'make:filter' => $this->makeFilter($argv[2] ?? ''),
                'make:validator' => $this->makeValidator($arguments[0] ?? ''),
                'make:middleware' => $this->makeMiddleware($arguments[0] ?? ''),

                'make:seeder' => $this->makeSeeder(
                    $arguments[0] ?? ''
                ),
                'db:seed' => $this->seed(
                    $arguments[0] ?? null
                ),

                'clean' => $this->clean(['cache', 'logs']),
                'clean:cache' => $this->clean(['cache']),
                'clean:logs' => $this->clean(['logs']),

                default => $this->unknownCommand($command),
            };
        } catch (Throwable $exception) {
            $debug = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);

            if ($debug) {
                fwrite(STDERR, 'Error: ' . $exception->getMessage() . PHP_EOL);
                fwrite(STDERR, $exception->getTraceAsString() . PHP_EOL);
            } else {
                fwrite(STDERR, 'Command failed. Check storage/logs or your server logs for details.' . PHP_EOL);
            }

            return 1;
        }
    }

    /**
     * Connect to MySQL only when needed.
     */
    private function database(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $driver = (string) env('DB_CONNECTION', 'mysql');

        if ($driver !== 'mysql') {
            throw new RuntimeException(
                "Unsupported database driver [{$driver}]. "
                . 'Only MySQL/MariaDB is currently supported.'
            );
        }

        $host = (string) env('DB_HOST', '127.0.0.1');
        $port = (string) env('DB_PORT', '3306');
        $database = (string) env('DB_DATABASE', 'localphp');
        $username = (string) env('DB_USERNAME', 'root');
        $password = (string) env('DB_PASSWORD', '');
        $charset = (string) env('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

        try {
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\PDOException $exception) {
            throw new RuntimeException(
                'Unable to connect to MySQL. Check that MySQL is running '
                . 'in Laragon and verify DB_HOST, DB_PORT, DB_DATABASE, '
                . 'DB_USERNAME, and DB_PASSWORD in your .env file.',
                0,
                $exception
            );
        }

        return $this->pdo;
    }

    /**
     * Display available commands.
     */
    private function listCommands(): int
    {
        echo PHP_EOL;
        echo "LocalPHP Console" . PHP_EOL;
        echo "Usage: php local <command>" . PHP_EOL . PHP_EOL;

        $commands = [
            'list' => 'Display available commands',
            'about' => 'Display framework information',
            'config:cache' => 'Compile configuration into a cache file',
            'config:clear' => 'Remove the compiled configuration cache',
            'optimize' => 'Prepare configuration cache for production',
            'optimize:clear' => 'Clear framework optimization cache',
            'make:migration <name>' => 'Create a migration',
            'migrate' => 'Run pending migrations',
            'migrate:status' => 'Show migration status',
            'migrate:rollback' => 'Roll back the latest batch',
            'migrate:fresh --force' => 'Drop tables and rerun migrations',
            'make:seeder <Name>' => 'Create a seeder',
            'db:seed [Name]' => 'Run a seeder',
            'clean' => 'Clear cache and logs',
            'clean:cache' => 'Clear cache files',
            'clean:logs' => 'Clear log files',
            'make:controller' => 'Create a new controller',
            'make:model'  => 'Create a new model',
            'make:filter' => 'Create a new filter',
            'make:validator <Name>' => 'Create a reusable validator class',
            'make:middleware <Name>' => 'Create middleware class',
        ];

        foreach ($commands as $name => $description) {
            echo '  ' . str_pad($name, 32) . $description . PHP_EOL;
        }

        echo PHP_EOL;

        return 0;
    }

    /**
     * Display framework information.
     */
    private function about(): int
    {
        echo "Framework: LocalPHP" . PHP_EOL;
        echo "PHP version: " . PHP_VERSION . PHP_EOL;
        echo "Project path: " . $this->basePath . PHP_EOL;

        return 0;
    }

    /**
     * Create the migration manager.
     */
    private function migrations(): MigrationManager
    {
        return new MigrationManager(
            $this->database(),
            $this->basePath
            . DIRECTORY_SEPARATOR . 'database'
            . DIRECTORY_SEPARATOR . 'migrations'
        );
    }

    private function showMigrationStatus(): int
    {
        $this->migrations()->status();

        return 0;
    }

    private function rollback(): int
    {
        $this->migrations()->rollback();

        return 0;
    }

    private function fresh(array $arguments): int
    {
        $force = in_array('--force', $arguments, true);

        $this->migrations()->fresh($force);

        return $force ? 0 : 1;
    }

    /**
     * Generate a migration file.
     */
    private function makeMigration(string $name): int
    {
        $name = $this->validateName($name, 'migration');

        $directory = $this->basePath
            . DIRECTORY_SEPARATOR . 'database'
            . DIRECTORY_SEPARATOR . 'migrations';

        $this->ensureDirectory($directory);

        $filename = date('Y_m_d_His') . '_' . $name . '.php';
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        if (file_exists($path)) {
            throw new RuntimeException(
                "Migration [{$filename}] already exists."
            );
        }

        $contents = <<<'PHP'
<?php

declare(strict_types=1);

use LocalPHP\Database\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        // Add database changes here.
    }

    public function down(PDO $pdo): void
    {
        // Reverse the changes made in up().
    }
};
PHP;

        $this->writeFile($path, $contents);

        echo "Created migration: database/migrations/{$filename}"
            . PHP_EOL;

        return 0;
    }

    private function makeValidator(string $name): int
    {
        $name = $this->validateClassName($name, 'validator');
        $directory = $this->basePath . '/app/Validators'; $this->ensureDirectory($directory);
        $path = $directory . '/' . $name . 'Validator.php';
        $strict = filter_var(env('APP_STRICT_TYPES', true), FILTER_VALIDATE_BOOLEAN) ? "declare(strict_types=1);\n\n" : '';
        $code = "<?php\n\n{$strict}namespace App\\Validators;\n\nfinal class {$name}Validator\n{\n    public function rules(): array\n    {\n        return [\n            // 'name' => 'required|string|min:2|max:100',\n        ];\n    }\n}\n";
        $this->writeFile($path, $code); echo "Created validator: app/Validators/{$name}Validator.php" . PHP_EOL; return 0;
    }

    private function makeMiddleware(string $name): int
    {
        $name = $this->validateClassName($name, 'middleware');
        $directory = $this->basePath . '/app/Middleware'; $this->ensureDirectory($directory);
        $path = $directory . '/' . $name . 'Middleware.php';
        $strict = filter_var(env('APP_STRICT_TYPES', true), FILTER_VALIDATE_BOOLEAN) ? "declare(strict_types=1);\n\n" : '';
        $code = "<?php\n\n{$strict}namespace App\\Middleware;\n\nuse LocalPHP\\Http\\Request;\nuse LocalPHP\\Http\\Response;\n\nfinal class {$name}Middleware\n{\n    public function handle(Request \$request, callable \$next): Response\n    {\n        return \$next(\$request);\n    }\n}\n";
        $this->writeFile($path, $code); echo "Created middleware: app/Middleware/{$name}Middleware.php" . PHP_EOL; return 0;
    }

    private function validateClassName(string $name, string $type): string
    {
        if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $name)) throw new RuntimeException(ucfirst($type) . ' name must be PascalCase.');
        return $name;
    }

    /**
     * Generate a seeder file.
     */
    private function makeSeeder(string $name): int
    {
        if (!preg_match('/^[A-Z][A-Za-z0-9]*Seeder$/', $name)) {
            throw new RuntimeException(
                'Seeder name must be PascalCase and end with Seeder. '
                . 'Example: UserSeeder.'
            );
        }

        $directory = $this->basePath
            . DIRECTORY_SEPARATOR . 'database'
            . DIRECTORY_SEPARATOR . 'seeders';

        $this->ensureDirectory($directory);

        $path = $directory . DIRECTORY_SEPARATOR . $name . '.php';

        if (file_exists($path)) {
            throw new RuntimeException(
                "Seeder [{$name}] already exists."
            );
        }

        $contents = <<<'PHP'
<?php

declare(strict_types=1);

use LocalPHP\Database\Seeder;

return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        // Add seed data here.
    }
};
PHP;

        $this->writeFile($path, $contents);

        echo "Created seeder: database/seeders/{$name}.php"
            . PHP_EOL;

        return 0;
    }

    /**
     * Execute a seeder.
     */
    private function seed(?string $name = null): int
    {
        $name ??= 'DatabaseSeeder';

        if (!preg_match('/^[A-Z][A-Za-z0-9]*Seeder$/', $name)) {
            throw new RuntimeException('Invalid seeder name.');
        }

        $path = $this->basePath
            . DIRECTORY_SEPARATOR . 'database'
            . DIRECTORY_SEPARATOR . 'seeders'
            . DIRECTORY_SEPARATOR . $name . '.php';

        if (!is_file($path)) {
            throw new RuntimeException(
                "Seeder [{$name}] not found."
            );
        }

        $seeder = require $path;

        if (!$seeder instanceof Seeder) {
            throw new RuntimeException(
                "Seeder [{$name}] must return an instance of "
                . Seeder::class . '.'
            );
        }

        $seeder->run($this->database());

        echo "Seeded: {$name}" . PHP_EOL;

        return 0;
    }

    /** Compile config/*.php into a production cache. */
    private function cacheConfig(): int
    {
        $config = [];
        $files = glob($this->basePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . '*.php') ?: [];
        foreach ($files as $file) {
            $name = basename($file, '.php');
            $value = require $file;
            if (!is_array($value)) {
                throw new RuntimeException("Configuration file [{$file}] must return an array.");
            }
            $config[$name] = $value;
        }

        $directory = $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache';
        $this->ensureDirectory($directory);
        $path = $directory . DIRECTORY_SEPARATOR . 'config.php';
        $contents = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($config, true) . ";\n";
        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write configuration cache.');
        }
        echo "Configuration cached: storage/cache/config.php" . PHP_EOL;
        return 0;
    }

    private function clearConfigCache(): int
    {
        $path = $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'config.php';
        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException('Unable to remove configuration cache.');
        }
        echo is_file($path) ? "Configuration cache could not be cleared." . PHP_EOL : "Configuration cache cleared." . PHP_EOL;
        return 0;
    }

    private function optimize(): int
    {
        $result = $this->cacheConfig();
        if ($result !== 0) {
            return $result;
        }
        echo "LocalPHP production configuration optimization complete." . PHP_EOL;
        echo "Note: PHP OPcache should be enabled in your PHP/server configuration for bytecode caching." . PHP_EOL;
        return 0;
    }

    /**
     * Clear files from approved storage directories.
     */
    private function clean(array $targets): int
    {
        foreach ($targets as $target) {
            if (!in_array($target, ['cache', 'logs'], true)) {
                throw new RuntimeException(
                    "Invalid cleanup target [{$target}]."
                );
            }

            $directory = $this->basePath
                . DIRECTORY_SEPARATOR . 'storage'
                . DIRECTORY_SEPARATOR . $target;

            if (!is_dir($directory)) {
                echo "Skipped missing directory: storage/{$target}"
                    . PHP_EOL;
                continue;
            }

            $removed = $this->clearDirectory($directory);

            echo "Cleaned storage/{$target}: {$removed} item(s)."
                . PHP_EOL;
        }

        return 0;
    }

    private function clearDirectory(string $directory): int
    {
        $removed = 0;

        foreach (
            new FilesystemIterator(
                $directory,
                FilesystemIterator::SKIP_DOTS
            ) as $item
        ) {
            $path = $item->getPathname();

            if ($item->isDir() && !$item->isLink()) {
                $removed += $this->clearDirectory($path);

                if (!rmdir($path)) {
                    throw new RuntimeException(
                        "Unable to remove directory: {$path}"
                    );
                }

                $removed++;
                continue;
            }

            if (!unlink($path)) {
                throw new RuntimeException(
                    "Unable to remove file: {$path}"
                );
            }

            $removed++;
        }

        return $removed;
    }

    private function ensureDirectory(string $directory): void
    {
        if (
            !is_dir($directory)
            && !mkdir($directory, 0775, true)
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                "Unable to create directory: {$directory}"
            );
        }
    }

    private function writeFile(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents . PHP_EOL) === false) {
            throw new RuntimeException(
                "Unable to write file: {$path}"
            );
        }
    }

    private function validateName(
        string $name,
        string $type
    ): string {
        if (
            $name === ''
            || !preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $name)
        ) {
            throw new RuntimeException(
                "Provide a valid {$type} name using letters, numbers, "
                . 'underscores, and hyphens.'
            );
        }

        return $name;
    }

    private function unknownCommand(string $command): int
    {
        fwrite(
            STDERR,
            "Unknown command: {$command}" . PHP_EOL
        );

        $this->listCommands();

        return 1;
    }

    /**
     * Create a new controller.
     *
     * Usage:
     * php local make:controller HomeController
     * php local make:controller HomeController2
     * php local make:controller Admin/DashboardController
     */
    private function makeController(string $name): int
    {
        $name = trim(str_replace('\\', '/', $name), '/');

        if ($name === '') {
            echo "Controller name is required.\n\n";
            echo "Usage:\n";
            echo "  php local make:controller HomeController\n";
            echo "  php local make:controller HomeController2\n";
            echo "  php local make:controller Admin/DashboardController\n";

            return 1;
        }

        $segments = explode('/', $name);

        foreach ($segments as $segment) {
            if (
                $segment === ''
                || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $segment)
            ) {
                echo "Invalid controller name: {$name}\n";
                return 1;
            }
        }

        $lastIndex = count($segments) - 1;
        $className = $segments[$lastIndex];

        /*
         * Append Controller only when the name does not
         * already contain the Controller suffix.
         *
         * Examples:
         * Home       -> HomeController
         * HomeController -> HomeController
         * HomeController2 -> HomeController2
         */
        if (!preg_match('/Controller\d*$/', $className)) {
            $className .= 'Controller';
        }

        $segments[$lastIndex] = $className;

        $className = array_pop($segments);

        $namespace = 'App\\Controllers';

        if ($segments !== []) {
            $namespace .= '\\' . implode('\\', $segments);
        }

        $directory = $this->basePath
            . DIRECTORY_SEPARATOR . 'app'
            . DIRECTORY_SEPARATOR . 'Controllers';

        if ($segments !== []) {
            $directory .= DIRECTORY_SEPARATOR
                . implode(DIRECTORY_SEPARATOR, $segments);
        }

        $filePath = $directory
            . DIRECTORY_SEPARATOR
            . $className
            . '.php';

        if (file_exists($filePath)) {
            echo "Controller already exists: {$filePath}\n";
            return 1;
        }

        if (
            !is_dir($directory)
            && !mkdir($directory, 0755, true)
            && !is_dir($directory)
        ) {
            echo "Unable to create directory: {$directory}\n";
            return 1;
        }

        $controller = <<<PHP
    <?php

    declare(strict_types=1);

    namespace {$namespace};

    use LocalPHP\\Http\\Response;

    class {$className}
    {
        /**
         * Display the page.
         */
        public function index(): Response
        {
            return view('home');
        }
    }
    PHP;

        if (file_put_contents($filePath, $controller) === false) {
            echo "Unable to create controller: {$filePath}\n";
            return 1;
        }

        echo "\033[32mController created successfully!\033[0m\n";
        echo "  File: {$filePath}\n";
        echo "  Class: {$namespace}\\{$className}\n";

        return 0;
    }

    /**
     * Create a new model.
     *
     * Usage:
     * php local make:model User
     * php local make:model Admin/Staff
     */
    private function makeModel(string $name): int
    {
        return $this->generateClass(
            name: $name,
            type: 'model',
            baseNamespace: 'App\\Models',
            baseDirectory: 'app/Models',
            template: 'model'
        );
    }

    /**
     * Create a new filter.
     *
     * Usage:
     * php local make:filter UserFilter
     * php local make:filter Admin/OrderFilter
     */
    private function makeFilter(string $name): int
    {
        return $this->generateClass(
            name: $name,
            type: 'filter',
            baseNamespace: 'App\\Filters',
            baseDirectory: 'app/Filters',
            template: 'filter'
        );
    }

    /**
     * Generate a PHP class file.
     */
    private function generateClass(
        string $name,
        string $type,
        string $baseNamespace,
        string $baseDirectory,
        string $template
    ): int {
        $name = trim(str_replace('\\', '/', $name), '/');

        if ($name === '') {
            echo ucfirst($type) . " name is required.\n";
            echo "Usage: php local make:{$type} Name\n";

            return 1;
        }

        $segments = explode('/', $name);

        foreach ($segments as $segment) {
            if (
                $segment === ''
                || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $segment)
            ) {
                echo "Invalid {$type} name: {$name}\n";
                return 1;
            }
        }

        $lastIndex = count($segments) - 1;
        $className = $segments[$lastIndex];

        if ($type === 'model') {
            if (!str_ends_with($className, 'Model')) {
                // Models conventionally use names such as User and Product.
                // Do not force a Model suffix.
            }
        } elseif ($type === 'filter') {
            if (!preg_match('/Filter\d*$/', $className)) {
                $className .= 'Filter';
            }
        }

        $segments[$lastIndex] = $className;
        $className = array_pop($segments);

        $namespace = $baseNamespace;

        if ($segments !== []) {
            $namespace .= '\\' . implode('\\', $segments);
        }

        $directory = $this->basePath
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $baseDirectory);

        if ($segments !== []) {
            $directory .= DIRECTORY_SEPARATOR
                . implode(DIRECTORY_SEPARATOR, $segments);
        }

        $filePath = $directory
            . DIRECTORY_SEPARATOR
            . $className
            . '.php';

        if (file_exists($filePath)) {
            echo ucfirst($type) . " already exists: {$filePath}\n";
            return 1;
        }

        if (
            !is_dir($directory)
            && !mkdir($directory, 0755, true)
            && !is_dir($directory)
        ) {
            echo "Unable to create directory: {$directory}\n";
            return 1;
        }

        if ($template === 'model') {
            $content = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use LocalPHP\\Model\\Model;

class {$className} extends Model
{
    /**
     * Database table associated with this model.
     *
     * Set this explicitly if your ORM does not infer table names.
     */
    protected string \$table = '{$this->modelTableName($className)}';
}

PHP;
        } else {
            $content = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

class {$className}
{
    /**
     * Apply this filter to an array of data.
     */
    public function apply(array \$data): array
    {
        // Add your filtering logic here.

        return \$data;
    }
}

PHP;
        }

        if (file_put_contents($filePath, $content) === false) {
            echo "Unable to write file: {$filePath}\n";
            return 1;
        }

        echo "\033[32m" . ucfirst($type) . " created successfully!\033[0m\n";
        echo "  File: {$filePath}\n";
        echo "  Class: {$namespace}\\{$className}\n";

        return 0;
    }

    /**
     * Infer a basic table name from a model class name.
     *
     * Examples:
     * User       -> users
     * Product    -> products
     * BlogPost   -> blog_posts
     *
     * This deliberately uses simple English pluralization.
     * Override the table property for irregular table names.
     */
    private function modelTableName(string $className): string
    {
        $name = preg_replace('/(?<!^)[A-Z]/', '_$0', $className);
        $name = strtolower($name ?? $className);

        if (str_ends_with($name, 'y') && !preg_match('/[aeiou]y$/', $name)) {
            return substr($name, 0, -1) . 'ies';
        }

        if (
            str_ends_with($name, 's')
            || str_ends_with($name, 'x')
            || str_ends_with($name, 'ch')
            || str_ends_with($name, 'sh')
        ) {
            return $name . 'es';
        }

        return $name . 's';
    }

}