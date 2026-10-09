<?php

declare(strict_types=1);

namespace LocalPHP\Routing;

class Route
{
    protected string $method;

    protected string $uri;

    protected mixed $action;

    protected ?string $name = null;

    protected array $parameters = [];

    public function __construct(
        string $method,
        string $uri,
        mixed $action
    ) {
        $this->method = strtoupper($method);
        $this->uri = $this->normalizeUri($uri);
        $this->action = $action;
    }

    protected function normalizeUri(string $uri): string
    {
        $uri = '/' . trim($uri, '/');

        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        return $uri;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function action(): mixed
    {
        return $this->action;
    }

    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function parameters(array $parameters): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Determine whether this route matches a URI.
     */
    public function matches(string $uri): bool
    {
        $pattern = preg_replace(
            '#\{([^}]+)\}#',
            '([^/]+)',
            $this->uri
        );

        return preg_match(
            '#^' . $pattern . '$#',
            $uri
        ) === 1;
    }

    /**
     * Extract route parameters.
     */
    public function extractParameters(string $uri): array
    {
        $parameterNames = [];

        preg_match_all(
            '#\{([^}]+)\}#',
            $this->uri,
            $parameterMatches
        );

        $parameterNames = $parameterMatches[1];

        $pattern = preg_replace(
            '#\{([^}]+)\}#',
            '([^/]+)',
            $this->uri
        );

        preg_match(
            '#^' . $pattern . '$#',
            $uri,
            $matches
        );

        array_shift($matches);

        $parameters = [];

        foreach ($parameterNames as $index => $name) {
            $parameters[$name] = $matches[$index] ?? null;
        }

        return $parameters;
    }
}