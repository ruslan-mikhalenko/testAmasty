<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class ReplyRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $ticketId, int $adminId, string $message): array
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO responses (ticket_id, admin_id, body)
            VALUES (:ticket_id, :admin_id, :body)
        ');
        $stmt->execute([
            'ticket_id' => $ticketId,
            'admin_id' => $adminId,
            'body' => $message,
        ]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function getByTicket(int $ticketId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT r.*, u.email AS admin_email
            FROM responses r
            JOIN users u ON u.id = r.admin_id
            WHERE r.ticket_id = :ticket_id
            ORDER BY r.created_at ASC
        ');
        $stmt->execute(['ticket_id' => $ticketId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT r.*, u.email AS admin_email
            FROM responses r
            JOIN users u ON u.id = r.admin_id
            WHERE r.id = :id
        ');
        $stmt->execute(['id' => $id]);

        $reply = $stmt->fetch(PDO::FETCH_ASSOC);

        return $reply ?: null;
    }
}

