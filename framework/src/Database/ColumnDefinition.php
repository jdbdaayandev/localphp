<?php

declare(strict_types=1);
namespace LocalPHP\Database;

final class ColumnDefinition
{
    public function __construct(public string $name, public string $type, public ?int $length = null, public bool $nullable = false, public bool $autoIncrement = false, public bool $primary = false, public bool $unsigned = false, public mixed $default = null, public bool $hasDefault = false, public ?string $after = null) {}
    public function nullable(bool $value = true): self { $this->nullable = $value; return $this; }
    public function autoIncrement(): self { $this->autoIncrement = true; return $this; }
    public function primary(): self { $this->primary = true; return $this; }
    public function unsigned(): self { $this->unsigned = true; return $this; }
    public function default(mixed $value): self { $this->default = $value; $this->hasDefault = true; return $this; }
    public function after(string $column): self { $this->after = $column; return $this; }
}
