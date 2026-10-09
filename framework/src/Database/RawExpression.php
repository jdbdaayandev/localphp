<?php

declare(strict_types=1);

namespace LocalPHP\Database;

/** Explicit SQL expression. Only use for developer-authored SQL, never raw user input. */
final class RawExpression
{
    public function __construct(
        public readonly string $sql,
        public readonly array $bindings = [],
    ) {
    }
}
