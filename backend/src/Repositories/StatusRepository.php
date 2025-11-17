<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class StatusRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM statuses ORDER BY id ASC');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM statuses WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $status = $stmt->fetch(PDO::FETCH_ASSOC);

        return $status ?: null;
    }

    public function getDefaultStatus(): ?array
    {
        $stmt = $this->pdo->query('SELECT * FROM statuses ORDER BY id ASC LIMIT 1');

        $status = $stmt->fetch(PDO::FETCH_ASSOC);

        return $status ?: null;
    }

    public function create(string $name): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO statuses (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, string $name): array
    {
        $stmt = $this->pdo->prepare('UPDATE statuses SET name = :name WHERE id = :id');
        $stmt->execute(['name' => $name, 'id' => $id]);

        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM statuses WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}

