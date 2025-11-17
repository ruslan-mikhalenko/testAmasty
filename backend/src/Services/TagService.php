<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TagRepository;
use InvalidArgumentException;

class TagService
{
    public function __construct(private readonly TagRepository $tags)
    {
    }

    public function list(): array
    {
        return $this->tags->all();
    }

    public function create(string $name, string $color): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Название тега обязательно');
        }

        return $this->tags->create($name, $color);
    }

    public function update(int $id, string $name, string $color): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Название тега обязательно');
        }

        return $this->tags->update($id, $name, $color);
    }

    public function delete(int $id): void
    {
        $this->tags->delete($id);
    }
}

