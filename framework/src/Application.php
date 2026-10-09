<?php

declare(strict_types=1);

namespace LocalPHP;

use LocalPHP\Container\Container;
use LocalPHP\Http\Request;
use LocalPHP\Http\Response;
use LocalPHP\Routing\Router;
use LocalPHP\Session\SessionManager;
use LocalPHP\Database\DatabaseManager;

class Application extends Container
{
    public const VERSION = '0.2.0';

    protected string $basePath;

    protected Router $router;

    public function __construct(
    ?string $basePath = null
    ) {
        $this->basePath =
            $basePath
            ?? dirname(__DIR__, 2);

        $GLOBALS['app'] = $this;

        $this->registerCoreServices();

        $this->loadRoutes();
    }

    protected function registerCoreServices(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Router
        |--------------------------------------------------------------------------
        */

        $this->singleton(
            Router::class,
            fn () => new Router()
        );

        $this->router = $this->make(
            Router::class
        );

        /*
        |--------------------------------------------------------------------------
        | Request
        |--------------------------------------------------------------------------
        */

        $this->singleton(
            Request::class,
            fn () => Request::capture()
        );

        /*
        |--------------------------------------------------------------------------
        | Session
        |--------------------------------------------------------------------------
        */

        $this->singleton(
            SessionManager::class,
            fn () => new SessionManager(
                config(
                    'app.session',
                    []
                )
            )
        );

        $this->singleton(
            DatabaseManager::class,
            fn () => new DatabaseManager(
                config('database', [])
            )
        );
    }

    protected function loadRoutes(): void
    {
        $routes = $this->basePath(
            'routes/web.php'
        );

        if (!file_exists($routes)) {
            return;
        }

        $app = $this;

        require $routes;
    }

    public function handle(
        Request $request
    ): Response {
        // Protect state-changing requests by default; GET/HEAD/OPTIONS are read-only.
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $provided = $request->input('_token')
                ?? $request->header('X-CSRF-TOKEN')
                ?? $request->header('X-XSRF-TOKEN');
            $expected = session()->get('_csrf_token');

            if (!is_string($expected) || !is_string($provided) || !hash_equals($expected, $provided)) {
                if ($request->expectsJson()) {
                    return response([
                        'success' => false,
                        'message' => 'CSRF token mismatch. Refresh the page and try again.',
                        'error' => ['code' => 'CSRF_TOKEN_MISMATCH'],
                    ], 419);
                }

                return new Response(
                    '<h1>419 - Page Expired</h1><p>Your security token is missing or expired. Refresh the page and try again.</p>',
                    419,
                    ['Content-Type' => 'text/html; charset=UTF-8']
                );
            }
        }

        return $this->router->dispatch($request);
    }

    public function basePath(
        string $path = ''
    ): string {
        if ($path === '') {
            return $this->basePath;
        }

        return $this->basePath
            . DIRECTORY_SEPARATOR
            . ltrim(
                $path,
                DIRECTORY_SEPARATOR
            );
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function version(): string
    {
        return self::VERSION;
    }
}