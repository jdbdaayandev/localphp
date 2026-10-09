<?php

declare(strict_types=1);

namespace LocalPHP\Support;

use RuntimeException;

/**
 * Loads and reads LocalPHP environment variables.
 *
 * Existing process environment variables take precedence over values in .env.
 * The .env file is read once for the active project root.
 */
final class Environment
{
    private static ?string $loadedPath = null;

    /**
     * Parsed values from the project's .env file.
     *
     * @var array<string, mixed>
     */
    private static array $values = [];

    /** @var array<string, true> Values exported by this loader. */
    private static array $injected = [];

    /**
     * Load the project's .env file.
     */
    public static function load(string $basePath): void
    {
        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR);

        if ($basePath === '') {
            $basePath = DIRECTORY_SEPARATOR;
        }

        if (self::$loadedPath === $basePath) {
            return;
        }

        // Remove values injected by a previous project root before loading
        // another one in the same PHP process.
        foreach (array_keys(self::$injected) as $name) {
            unset($_ENV[$name], $_SERVER[$name]);
            putenv($name);
        }

        self::$injected = [];
        self::$loadedPath = $basePath;
        self::$values = [];

        $file = $basePath . DIRECTORY_SEPARATOR . '.env';

        if (!is_file($file)) {
            return;
        }

        $contents = file_get_contents($file);

        if ($contents === false) {
            throw new RuntimeException(
                "Unable to read environment file: {$file}"
            );
        }

        // Remove a UTF-8 BOM if the file was saved with one.
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;

        $parsed = [];

        foreach (preg_split('/\r\n|\r|\n/', $contents) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!preg_match(
                '/^(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/',
                $line,
                $matches
            )) {
                // Ignore malformed or unsupported lines instead of breaking
                // application startup.
                continue;
            }

            $name = $matches[1];
            [$value, $quoted] = self::parseValue($matches[2]);
            $parsed[$name] = [$value, $quoted];
        }

        // Resolve simple ${VARIABLE} references after all values are parsed.
        foreach ($parsed as $name => [$value, $quoted]) {
            if (is_string($value) && str_contains($value, '${')) {
                $value = preg_replace_callback(
                    '/\$\{([A-Za-z_][A-Za-z0-9_]*)\}/',
                    static function (array $matches) use ($parsed): string {
                        $reference = $matches[1];

                        if (array_key_exists($reference, $parsed)) {
                            return (string) $parsed[$reference][0];
                        }

                        $processValue = getenv($reference);

                        if ($processValue !== false) {
                            return $processValue;
                        }

                        if (isset($_ENV[$reference])) {
                            return (string) $_ENV[$reference];
                        }

                        if (isset($_SERVER[$reference])) {
                            return (string) $_SERVER[$reference];
                        }

                        return $matches[0];
                    },
                    $value
                ) ?? $value;
            }

            // Do not override values injected by the server/process.
            $processValue = getenv($name);
            $wasInjected = isset(self::$injected[$name]);
            $hasProcessValue = ($processValue !== false && !$wasInjected)
                || (array_key_exists($name, $_ENV) && !$wasInjected)
                || (array_key_exists($name, $_SERVER) && !$wasInjected);

            if (!$hasProcessValue) {
                self::$values[$name] = self::normalize($value, $quoted);
                $_ENV[$name] = self::$values[$name];
                $_SERVER[$name] = self::$values[$name];

                // Make .env values available to code that uses getenv().
                putenv($name . '=' . (is_bool(self::$values[$name])
                    ? (self::$values[$name] ? 'true' : 'false')
                    : (self::$values[$name] === null
                        ? 'null'
                        : (string) self::$values[$name])));

                self::$injected[$name] = true;
            }
        }
    }

    /**
     * Retrieve an environment value.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$loadedPath === null) {
            self::load(dirname(__DIR__, 2));
        }

        $processValue = getenv($key);

        if ($processValue !== false && !isset(self::$injected[$key])) {
            return self::normalize($processValue, false);
        }

        if (array_key_exists($key, $_ENV) && !isset(self::$injected[$key])) {
            return self::normalize($_ENV[$key], false);
        }

        if (array_key_exists($key, $_SERVER) && !isset(self::$injected[$key])) {
            return self::normalize($_SERVER[$key], false);
        }

        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }

        return $default;
    }

    /**
     * Return parsed .env values.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (self::$loadedPath === null) {
            self::load(dirname(__DIR__, 2));
        }

        return self::$values;
    }

    /**
     * Parse a dotenv value, retaining whether it was quoted.
     *
     * @return array{0: string, 1: bool}
     */
    private static function parseValue(string $value): array
    {
        $value = trim($value);

        if ($value === '') {
            return ['', false];
        }

        $first = $value[0];
        $last = $value[strlen($value) - 1];

        if (
            strlen($value) >= 2
            && (($first === '"' && $last === '"')
                || ($first === "'" && $last === "'"))
        ) {
            $value = substr($value, 1, -1);

            if ($first === '"') {
                $value = str_replace(
                    ['\\n', '\\r', '\\t', '\\"', '\\\\'],
                    ["\n", "\r", "\t", '"', '\\'],
                    $value
                );
            } else {
                $value = str_replace(["\\'", '\\\\'], ["'", '\\'], $value);
            }

            return [$value, true];
        }

        // An inline comment starts at a # preceded by whitespace.
        $value = preg_replace('/\s+#.*$/', '', $value) ?? $value;

        return [trim($value), false];
    }

    /**
     * Convert dotenv literals while leaving quoted values as strings.
     */
    private static function normalize(mixed $value, bool $quoted): mixed
    {
        if (!is_string($value) || $quoted) {
            return $value;
        }

        return match (strtolower(trim($value))) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}
