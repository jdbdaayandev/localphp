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

    public function addRoute(
        string $method,
        string $uri,
        mixed $action
    ): Route {
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
            if ($request->expectsJson()) {
                return new Response(
                    json_encode([
                        'success' => false,
                        'message' => 'The requested resource was not found.',
                        'error' => ['code' => 'NOT_FOUND'],
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    404,
                    ['Content-Type' => 'application/json; charset=UTF-8']
                );
            }

            return new Response(
                '<h1>404 - Page Not Found</h1>',
                404,
                ['Content-Type' => 'text/html; charset=UTF-8']
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
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                ),
                200,
                [
                    'Content-Type' =>
                        'application/json; charset=UTF-8'
                ]
            );
        }

        return new Response('');
    }

    public function routes(): RouteCollection
    {
        return $this->routes;
    }
}