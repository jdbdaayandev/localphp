<?php

declare(strict_types=1);

namespace LocalPHP\Http;

class Request
{
    protected string $method;

    protected string $uri;

    protected array $query;

    protected array $input;

    protected array $files;

    protected array $cookies;

    protected array $server;

    protected string $basePath;

    public function __construct(
        string $method,
        string $uri,
        array $query = [],
        array $input = [],
        array $files = [],
        array $cookies = [],
        array $server = [],
        string $basePath = ''
    ) {
        $this->method = strtoupper($method);

        $this->basePath = $this->normalizeBasePath(
            $basePath
        );

        $this->uri = $this->normalizeUri(
            $uri
        );

        $this->query = $query;
        $this->input = $input;
        $this->files = $files;
        $this->cookies = $cookies;
        $this->server = $server;
    }

    /**
     * Capture the current HTTP request.
     */
    public static function capture(): self
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        $path = parse_url(
            $requestUri,
            PHP_URL_PATH
        );

        $path = $path ?: '/';

        /**
         * Detect the directory where the application
         * is installed.
         *
         * Example:
         *
         * /localphp/
         * /localphp/hello
         * /localphp/users
         */
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

        $basePath = dirname($scriptName);

        // When Apache internally rewrites to public/index.php, hide /public from URLs.
        $basePath = preg_replace('~/public$~i', '', $basePath) ?? $basePath;

        if ($basePath === '\\' || $basePath === '.') {
            $basePath = '';
        }

                $input = $_POST;
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw ?: '', true);
            if (is_array($decoded)) {
                $input = $decoded;
            }
        }

        return new self(
            method: $_SERVER['REQUEST_METHOD'] ?? 'GET',
            uri: $path,
            query: $_GET,
            input: $input,
            files: $_FILES,
            cookies: $_COOKIE,
            server: $_SERVER,
            basePath: $basePath
        );
    }

    /**
     * Normalize application base path.
     */
    protected function normalizeBasePath(
        string $basePath
    ): string {
        if ($basePath === '/') {
            return '';
        }

        $basePath = '/' . trim(
            $basePath,
            '/'
        );

        return $basePath === '/'
            ? ''
            : rtrim($basePath, '/');
    }

    /**
     * Normalize request URI.
     */
    protected function normalizeUri(
        string $uri
    ): string {
        /**
         * Remove query string if present.
         */
        $uri = parse_url(
            $uri,
            PHP_URL_PATH
        ) ?: '/';

        /**
         * Remove application base path.
         *
         * /localphp/
         * becomes /
         *
         * /localphp/hello
         * becomes /hello
         */
        if (
            $this->basePath !== '' &&
            (
                $uri === $this->basePath ||
                str_starts_with(
                    $uri,
                    $this->basePath . '/'
                )
            )
        ) {
            $uri = substr(
                $uri,
                strlen($this->basePath)
            );
        }

        /**
         * Normalize slashes.
         */
        $uri = '/' . trim(
            $uri,
            '/'
        );

        /**
         * Root URL.
         */
        if ($uri === '') {
            return '/';
        }

        /**
         * Remove trailing slash.
         */
        if ($uri !== '/') {
            $uri = rtrim(
                $uri,
                '/'
            );
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

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function query(
        ?string $key = null,
        mixed $default = null
    ): mixed {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    public function input(
        ?string $key = null,
        mixed $default = null
    ): mixed {
        if ($key === null) {
            return $this->input;
        }

        return $this->input[$key] ?? $default;
    }

    public function file(
        ?string $key = null
    ): mixed {
        if ($key === null) {
            return $this->files;
        }

        return $this->files[$key] ?? null;
    }

    public function cookie(
        ?string $key = null,
        mixed $default = null
    ): mixed {
        if ($key === null) {
            return $this->cookies;
        }

        return $this->cookies[$key] ?? $default;
    }

    public function server(
        ?string $key = null,
        mixed $default = null
    ): mixed {
        if ($key === null) {
            return $this->server;
        }

        return $this->server[$key] ?? $default;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (strtolower($name) === 'content-type') {
            return $this->server['CONTENT_TYPE'] ?? $default;
        }
        return $this->server[$key] ?? $default;
    }

    public function expectsJson(): bool
    {
        $accept = (string) $this->header('Accept', '');
        return str_contains(strtolower($accept), 'application/json')
            || strtolower((string) $this->header('X-Requested-With', '')) === 'xmlhttprequest'
            || str_contains(strtolower((string) $this->header('Content-Type', '')), 'application/json');
    }

    public function isMethod(
        string $method
    ): bool {
        return $this->method === strtoupper(
            $method
        );
    }

    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }
}