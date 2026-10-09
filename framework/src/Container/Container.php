<?php

declare(strict_types=1);

namespace LocalPHP\Container;

use Closure;
use RuntimeException;

class Container
{
    protected array $bindings = [];

    protected array $instances = [];

    protected array $singletons = [];

    public function bind(
        string $abstract,
        Closure $resolver
    ): void {
        $this->bindings[$abstract] =
            $resolver;

        $this->singletons[$abstract] =
            false;
    }

    public function singleton(
        string $abstract,
        Closure $resolver
    ): void {
        $this->bindings[$abstract] =
            $resolver;

        $this->singletons[$abstract] =
            true;
    }

    public function make(
        string $abstract
    ): mixed {
        if (
            isset($this->instances[$abstract])
        ) {
            return $this->instances[$abstract];
        }

        if (
            !isset($this->bindings[$abstract])
        ) {
            throw new RuntimeException(
                "Service [{$abstract}] is not bound."
            );
        }

        $instance = (
            $this->bindings[$abstract]
        )();

        if (
            $this->singletons[$abstract]
            ?? false
        ) {
            $this->instances[$abstract] =
                $instance;
        }

        return $instance;
    }

    public function bound(
        string $abstract
    ): bool {
        return isset(
            $this->bindings[$abstract]
        );
    }
}