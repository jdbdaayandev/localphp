<?php

declare(strict_types=1);

namespace LocalPHP\Routing;

class RouteCollection
{
    /**
     * @var Route[]
     */
    protected array $routes = [];

    public function add(Route $route): Route
    {
        $this->routes[] = $route;

        return $route;
    }

    /**
     * Find a matching route.
     */
    public function match(
        string $method,
        string $uri
    ): ?Route {
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if (
                $route->method() === $method &&
                $route->matches($uri)
            ) {
                return $route->parameters(
                    $route->extractParameters($uri)
                );
            }
        }

        return null;
    }

    /**
     * Get all routes.
     *
     * @return Route[]
     */
    public function all(): array
    {
        return $this->routes;
    }
}