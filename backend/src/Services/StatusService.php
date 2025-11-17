<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\StatusRepository;
use InvalidArgumentException;

class StatusService
{
    public function __construct(private readonly StatusRepository $statuses)
    {
    }

    public function list(): array
    {
        return $this->statuses->all();
    }

    public function create(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Название статуса обязательно');
        }

        return $this->statuses->create($name);
    }

    public function update(int $id, string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Название статуса обязательно');
        }

        return $this->statuses->update($id, $name);
    }

    public function delete(int $id): void
    {
        $this->statuses->delete($id);
    }
}

