<?php

declare(strict_types=1);

namespace LocalPHP\Validation;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    public function __construct(private readonly array $errors)
    {
        parent::__construct('The given data failed validation.');
    }

    public function errors(): array { return $this->errors; }
    public function first(?string $key = null): ?string
    {
        if ($key !== null) return $this->errors[$key][0] ?? null;
        foreach ($this->errors as $messages) if (isset($messages[0])) return $messages[0];
        return null;
    }
}
