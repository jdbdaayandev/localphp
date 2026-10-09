<?php

declare(strict_types=1);

namespace LocalPHP\Support;

use ErrorException;
use Throwable;

/** Handles web exceptions and writes diagnostic details to private logs. */
final class ExceptionHandler
{
    private static string $basePath;
    private static bool $registered = false;

    public static function register(string $basePath): void
    {
        self::$basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        $debug = self::debugEnabled();
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('display_startup_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $exception): void {
            self::render($exception);
        });

        register_shutdown_function(static function (): void {
            $error = error_get_last();
            if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
                return;
            }
            self::render(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
        });
    }

    public static function report(Throwable $exception): void
    {
        self::writeLog($exception);
    }

    private static function debugEnabled(): bool
    {
        $default = Environment::get('APP_ENV', 'local') !== 'production';
        $value = Environment::get('APP_DEBUG', $default);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private static function render(Throwable $exception): void
    {
        self::writeLog($exception);
        $debug = self::debugEnabled();
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('X-Content-Type-Options: nosniff');
        }

        if ($debug) {
            $message = htmlspecialchars($exception->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $file = htmlspecialchars($exception->getFile(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $trace = htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>LocalPHP Error</title><style>body{font:16px/1.6 system-ui,sans-serif;background:#f4f6f9;color:#182230;margin:0;padding:40px}.panel{max-width:1000px;margin:auto;background:white;border:1px solid #e3e8ef;border-radius:12px;padding:28px}h1{color:#b42318}pre{white-space:pre-wrap;overflow-wrap:anywhere;background:#f8fafc;padding:16px;border-radius:8px}</style></head><body><main class="panel"><h1>Application Error</h1><p><strong>' . $message . '</strong></p><p>' . $file . ':' . $exception->getLine() . '</p><h2>Stack trace</h2><pre>' . $trace . '</pre></main></body></html>';
            exit(1);
        }

        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Server Error</title><style>body{font:16px/1.6 system-ui,sans-serif;background:#f7f8fa;color:#253041;display:grid;min-height:100vh;place-items:center;margin:0}.box{text-align:center;max-width:520px;padding:36px}h1{font-size:2rem;margin-bottom:.5rem}p{color:#64748b}</style></head><body><main class="box"><h1>Something went wrong.</h1><p>The server encountered an unexpected error. Please try again later.</p><p>HTTP 500</p></main></body></html>';
        exit(1);
    }

    private static function writeLog(Throwable $exception): void
    {
        $directory = self::$basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            error_log('[LocalPHP] Unable to create application log directory.');
            return;
        }
        $file = $directory . DIRECTORY_SEPARATOR . 'localphp-' . date('Y-m-d') . '.log';
        $entry = sprintf(
            "[%s] %s: %s in %s:%d\n%s\n\n",
            date('Y-m-d H:i:s'),
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        if (@file_put_contents($file, $entry, FILE_APPEND | LOCK_EX) === false) {
            error_log('[LocalPHP] Failed to write application exception log.');
        }
    }
}
