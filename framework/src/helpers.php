<?php

declare(strict_types=1);

use LocalPHP\Http\Request;
use LocalPHP\Http\Response;
use LocalPHP\Session\SessionManager;
use LocalPHP\View\View;

/*
|--------------------------------------------------------------------------
| Application
|--------------------------------------------------------------------------
*/

if (!function_exists('app')) {
    function app(
        ?string $abstract = null
    ): mixed {
        global $app;

        if ($abstract === null) {
            return $app;
        }

        return $app->make(
            $abstract
        );
    }
}

/*
|--------------------------------------------------------------------------
| Environment
|--------------------------------------------------------------------------
*/

if (!function_exists('env')) {
    function env(
        string $key,
        mixed $default = null
    ): mixed {
        static $loaded = false;
        static $values = [];

        if (!$loaded) {
            $file = dirname(
                __DIR__,
                2
            ) . DIRECTORY_SEPARATOR . '.env';

            if (file_exists($file)) {
                $lines = file(
                    $file,
                    FILE_IGNORE_NEW_LINES |
                    FILE_SKIP_EMPTY_LINES
                );

                foreach ($lines as $line) {
                    $line = trim($line);

                    if (
                        $line === '' ||
                        str_starts_with(
                            $line,
                            '#'
                        )
                    ) {
                        continue;
                    }

                    if (!str_contains(
                        $line,
                        '='
                    )) {
                        continue;
                    }

                    [
                        $name,
                        $value
                    ] = explode(
                        '=',
                        $line,
                        2
                    );

                    $name = trim($name);
                    $value = trim($value);

                    if (
                        strlen($value) >= 2 &&
                        (
                            (
                                $value[0] === '"' &&
                                $value[-1] === '"'
                            ) ||
                            (
                                $value[0] === "'" &&
                                $value[-1] === "'"
                            )
                        )
                    ) {
                        $value = substr(
                            $value,
                            1,
                            -1
                        );
                    }

                    $values[$name] = $value;
                }
            }

            $loaded = true;
        }

        $value = $values[$key]
            ?? $_ENV[$key]
            ?? $_SERVER[$key]
            ?? $default;

        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        if ($value === 'null') {
            return null;
        }

        return $value;
    }
}

/*
|--------------------------------------------------------------------------
| Config
|--------------------------------------------------------------------------
*/

if (!function_exists('config')) {
    function config(
        ?string $key = null,
        mixed $default = null
    ): mixed {
        static $configuration = [];

        if (empty($configuration)) {
            $basePath = app()->basePath();

            $files = glob(
                $basePath .
                DIRECTORY_SEPARATOR .
                'config' .
                DIRECTORY_SEPARATOR .
                '*.php'
            ) ?: [];

            foreach ($files as $file) {
                $name = basename(
                    $file,
                    '.php'
                );

                $configuration[$name] =
                    require $file;
            }
        }

        if ($key === null) {
            return $configuration;
        }

        $segments = explode(
            '.',
            $key
        );

        $value = $configuration;

        foreach ($segments as $segment) {
            if (
                !is_array($value) ||
                !array_key_exists(
                    $segment,
                    $value
                )
            ) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

/*
|--------------------------------------------------------------------------
| Request
|--------------------------------------------------------------------------
*/

if (!function_exists('request')) {
    function request(
        ?string $key = null,
        mixed $default = null
    ): mixed {
        $request = app(
            Request::class
        );

        if ($key === null) {
            return $request;
        }

        return $request->input(
            $key,
            $default
        );
    }
}

/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/

if (!function_exists('response')) {
    function response(
        mixed $content = '',
        int $status = 200,
        array $headers = []
    ): Response {
        if ($content instanceof Response) {
            return $content;
        }

        if (is_array($content)) {
            $headers['Content-Type'] =
                'application/json';

            $content = json_encode(
                $content,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE |
                JSON_THROW_ON_ERROR
            );
        }

        return new Response(
            (string) $content,
            $status,
            $headers
        );
    }
}

/*
|--------------------------------------------------------------------------
| JSON Response
|--------------------------------------------------------------------------
*/

if (!function_exists('json')) {
    /** Return a JSON response with a status code and optional headers. */
    function json(
        mixed $data,
        int $status = 200,
        array $headers = []
    ): Response {
        $headers['Content-Type'] = 'application/json; charset=UTF-8';

        return new Response(
            json_encode(
                $data,
                JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE |
                JSON_THROW_ON_ERROR
            ),
            $status,
            $headers
        );
    }
}

/*
|--------------------------------------------------------------------------
| View
|--------------------------------------------------------------------------
*/

if (!function_exists('view')) {
    function view(
        string $name,
        array $data = []
    ): Response {
        static $renderer = null;

        if ($renderer === null) {
            $renderer = new View(
                app()->basePath(
                    'resources/views'
                )
            );
        }

        return response(
            $renderer->render(
                $name,
                $data
            ),
            200,
            [
                'Content-Type' =>
                    'text/html; charset=UTF-8'
            ]
        );
    }
}

/*
|--------------------------------------------------------------------------
| Base URL
|--------------------------------------------------------------------------
*/

if (!function_exists('base_url')) {
    function base_url(
        string $path = ''
    ): string {
        // The built-in server has its own host/port and serves public/ directly.
        if (PHP_SAPI === 'cli-server' && !empty($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                ? 'https' : 'http';
            $base = $scheme . '://' . $_SERVER['HTTP_HOST'];
        } else {
            $base = rtrim(
                (string) config(
                    'app.url',
                    ''
                ),
                '/'
            );
        }

        // The public directory is a document root, never part of a public URL.
        $base = preg_replace('~/public$~i', '', $base) ?? $base;

        if ($path === '') {
            return $base;
        }

        return $base . '/' . ltrim(
            $path,
            '/'
        );
    }
}

/*
|--------------------------------------------------------------------------
| URL
|--------------------------------------------------------------------------
*/

if (!function_exists('url')) {
    function url(
        string $path = ''
    ): string {
        return base_url($path);
    }
}

/*
|--------------------------------------------------------------------------
| Asset
|--------------------------------------------------------------------------
*/

if (!function_exists('asset')) {
    function asset(
        string $path
    ): string {
        return base_url(
            'assets/' . ltrim(
                $path,
                '/'
            )
        );
    }
}

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

if (!function_exists('redirect')) {
    function redirect(
        string $url,
        int $status = 302
    ): Response {
        return new Response(
            '',
            $status,
            [
                'Location' => $url
            ]
        );
    }
}

/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
*/

if (!function_exists('session')) {
    function session(
        ?string $key = null,
        mixed $default = null
    ): mixed {
        $session = app(
            SessionManager::class
        );

        if ($key === null) {
            return $session;
        }

        return $session->get(
            $key,
            $default
        );
    }
}

/*
|--------------------------------------------------------------------------
| Flash
|--------------------------------------------------------------------------
*/

if (!function_exists('flash')) {
    function flash(
        string $key,
        mixed $value
    ): void {
        session()->flash(
            $key,
            $value
        );
    }
}

/*
|--------------------------------------------------------------------------
| CSRF Token
|--------------------------------------------------------------------------
*/

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (!session()->has(
            '_csrf_token'
        )) {
            session()->put(
                '_csrf_token',
                bin2hex(
                    random_bytes(32)
                )
            );
        }

        return session()->get(
            '_csrf_token'
        );
    }
}

/*
|--------------------------------------------------------------------------
| CSRF Field
|--------------------------------------------------------------------------
*/

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return sprintf(
            '<input type="hidden" name="_token" value="%s">',
            htmlspecialchars(
                csrf_token(),
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }
}

/*
|--------------------------------------------------------------------------
| CSRF Meta Tag
|--------------------------------------------------------------------------
*/

if (!function_exists('csrf_meta')) {
    function csrf_meta(): string
    {
        return '<meta name="csrf-token" content="' . htmlspecialchars(
            csrf_token(), ENT_QUOTES, 'UTF-8'
        ) . '">';
    }
}

/*
|--------------------------------------------------------------------------
| Old Input
|--------------------------------------------------------------------------
*/

if (!function_exists('old')) {
    function old(
        string $key,
        mixed $default = null
    ): mixed {
        return session()->get(
            '_old.' . $key,
            $default
        );
    }
}

if (!function_exists('db')) {
    function db(): \LocalPHP\Database\DatabaseManager
    {
        return app(
            \LocalPHP\Database\DatabaseManager::class
        );
    }
}