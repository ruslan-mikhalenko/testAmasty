<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class TagRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM tags ORDER BY name ASC');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(string $name, string $color): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO tags (name, color) VALUES (:name, :color)');
        $stmt->execute([
            'name' => $name,
            'color' => $color,
        ]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, string $name, string $color): array
    {
        $stmt = $this->pdo->prepare('UPDATE tags SET name = :name, color = :color WHERE id = :id');
        $stmt->execute([
            'name' => $name,
            'color' => $color,
            'id' => $id,
        ]);

        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM tags WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tags WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $tag = $stmt->fetch(PDO::FETCH_ASSOC);

        return $tag ?: null;
    }

    public function getForTicket(int $ticketId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT tg.*
            FROM ticket_tags tt
            JOIN tags tg ON tg.id = tt.tag_id
            WHERE tt.ticket_id = :ticket_id
            ORDER BY tg.name ASC
        ');
        $stmt->execute(['ticket_id' => $ticketId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

