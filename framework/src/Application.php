<?php

declare(strict_types=1);

namespace LocalPHP;

use LocalPHP\Container\Container;
use LocalPHP\Http\Request;
use LocalPHP\Http\Response;
use LocalPHP\Routing\Router;
use LocalPHP\Session\SessionManager;
use LocalPHP\Database\DatabaseManager;
use LocalPHP\Support\Environment;
use InvalidArgumentException;

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

        // Load .env before any config file calls env().
        Environment::load($this->basePath);

        // Apply the configured timezone as part of application startup.
        $timezone = (string) Environment::get(
            'APP_TIMEZONE',
            'UTC'
        );

        if (!in_array($timezone, timezone_identifiers_list(), true)) {
            throw new InvalidArgumentException(
                "Invalid APP_TIMEZONE value [{$timezone}]."
            );
        }

        date_default_timezone_set($timezone);

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
        return $this->router->dispatch(
            $request
        );
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