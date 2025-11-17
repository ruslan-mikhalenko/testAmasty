<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

class Container
{
    /**
     * @var array<string, callable|object>
     */
    private array $bindings = [];

    /**
     * @var array<string, mixed>
     */
    private array $instances = [];

    public function set(string $id, callable|object $resolver): void
    {
        $this->bindings[$id] = $resolver;
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]);
    }

    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->bindings[$id])) {
            throw new RuntimeException("Container binding [{$id}] not found.");
        }

        $binding = $this->bindings[$id];

        $object = $binding instanceof Closure ? $binding($this) : $binding;

        return $this->instances[$id] = $object;
    }
}

