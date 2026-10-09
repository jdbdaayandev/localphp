<?php

declare(strict_types=1);

namespace LocalPHP\Http;

class Response
{
    protected string $content;

    protected int $statusCode;

    protected array $headers = [];

    public function __construct(
        string $content = '',
        int $statusCode = 200,
        array $headers = []
    ) {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function status(int $statusCode): static
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function header(
        string $name,
        string $value
    ): static {
        $this->headers[$name] = $value;

        return $this;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->content;
    }
}