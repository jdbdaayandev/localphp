<?php

declare(strict_types=1);

namespace LocalPHP\Routing;

use Closure;
use LocalPHP\Http\Request;
use LocalPHP\Http\Response;
use RuntimeException;

class Router
{
    protected RouteCollection $routes;
    protected string $groupPrefix = '';
    protected array $groupMiddleware = [];

    public function __construct()
    {
        $this->routes = new RouteCollection();
    }

    public function get(
        string $uri,
        mixed $action
    ): Route {
        return $this->addRoute(
            'GET',
            $uri,
            $action
        );
    }

    public function post(
        string $uri,
        mixed $action
    ): Route {
        return $this->addRoute(
            'POST',
            $uri,
            $action
        );
    }

    public function put(
        string $uri,
        mixed $action
    ): Route {
        return $this->addRoute(
            'PUT',
            $uri,
            $action
        );
    }

    public function delete(
        string $uri,
        mixed $action
    ): Route {
        return $this->addRoute(
            'DELETE',
            $uri,
            $action
        );
    }

    public function patch(string $uri, mixed $action): Route { return $this->addRoute('PATCH', $uri, $action); }

    public function any(string $uri, mixed $action): Route
    {
        $first = null;
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'] as $method) {
            $route = $this->addRoute($method, $uri, $action);
            $first ??= $route;
        }
        return $first;
    }

    public function addRoute(
        string $method,
        string $uri,
        mixed $action
    ): Route {
        $uri = '/' . trim($this->groupPrefix . '/' . trim($uri, '/'), '/');
        if ($uri === '') $uri = '/';
        return $this->routes->add(
            new Route(
                method: $method,
                uri: $uri,
                action: $action
            )
        );
    }

    public function dispatch(Request $request): Response
    {
        $route = $this->routes->match(
            $request->method(),
            $request->uri()
        );

        if ($route === null) {
            return new Response(
                '<h1>404 - Page Not Found</h1>',
                404,
                [
                    'Content-Type' => 'text/html; charset=UTF-8'
                ]
            );
        }

        return $this->runRoute(
            $route,
            $request
        );
    }

    protected function runRoute(
        Route $route,
        Request $request
    ): Response {
        $action = $route->action();

        $parameters = $route->getParameters();

        if ($action instanceof Closure) {
            $result = $action(
                $request,
                ...array_values($parameters)
            );

            return $this->toResponse($result);
        }

        if (is_array($action)) {
            return $this->runController(
                $action,
                $request,
                $parameters
            );
        }

        throw new RuntimeException(
            'Invalid route action.'
        );
    }

    protected function runController(
        array $action,
        Request $request,
        array $parameters
    ): Response {
        [$controller, $method] = $action;

        $instance = new $controller();

        $result = $instance->{$method}(
            $request,
            ...array_values($parameters)
        );

        return $this->toResponse($result);
    }

    protected function toResponse(
        mixed $result
    ): Response {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_string($result)) {
            return new Response(
                $result,
                200,
                [
                    'Content-Type' =>
                        'text/html; charset=UTF-8'
                ]
            );
        }

        if (is_array($result)) {
            return new Response(
                json_encode(
                    $result,
                    JSON_PRETTY_PRINT
                ),
                200,
                [
                    'Content-Type' =>
                        'application/json'
                ]
            );
        }

        return new Response('');
    }

    public function group(array $attributes, callable $callback): void
    {
        $oldPrefix = $this->groupPrefix; $oldMiddleware = $this->groupMiddleware;
        $this->groupPrefix = trim($oldPrefix . '/' . trim((string)($attributes['prefix'] ?? ''), '/'), '/');
        $this->groupMiddleware = array_merge($oldMiddleware, (array)($attributes['middleware'] ?? []));
        try { $callback($this); } finally { $this->groupPrefix = $oldPrefix; $this->groupMiddleware = $oldMiddleware; }
    }

    public function urlFor(string $name, array $parameters = []): string
    {
        foreach ($this->routes->all() as $route) {
            if ($route->getName() !== $name) continue;
            $uri = $route->uri();
            $uri = preg_replace_callback('/\\{([^}]+)\\}/', function ($m) use (&$parameters) {
                if (!array_key_exists($m[1], $parameters)) throw new RuntimeException("Missing route parameter [{$m[1]}].");
                $value = rawurlencode((string)$parameters[$m[1]]); unset($parameters[$m[1]]); return $value;
            }, $uri);
            return $uri . ($parameters ? '?' . http_build_query($parameters) : '');
        }
        throw new RuntimeException("Named route [{$name}] not found.");
    }

    public function routes(): RouteCollection
    {
        return $this->routes;
    }
}