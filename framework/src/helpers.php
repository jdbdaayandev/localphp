<?php

declare(strict_types=1);

use LocalPHP\Http\Request;
use LocalPHP\Http\Response;
use LocalPHP\Support\Environment;
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
        return Environment::get($key, $default);
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
        static $configuration = null;

        if ($configuration === null) {
            $basePath = app()->basePath();
            $cacheFile = $basePath
                . DIRECTORY_SEPARATOR . 'storage'
                . DIRECTORY_SEPARATOR . 'cache'
                . DIRECTORY_SEPARATOR . 'config.php';

            if (is_file($cacheFile)) {
                $cached = require $cacheFile;
                $configuration = is_array($cached) ? $cached : [];
            } else {
                $configuration = [];
                $files = glob(
                    $basePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . '*.php'
                ) ?: [];

                foreach ($files as $file) {
                    $name = basename($file, '.php');
                    $loaded = require $file;
                    $configuration[$name] = is_array($loaded) ? $loaded : [];
                }
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
                JSON_UNESCAPED_SLASHES
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
        $base = rtrim(
            (string) config(
                'app.url',
                ''
            ),
            '/'
        );

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