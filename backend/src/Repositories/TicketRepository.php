<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class TicketRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $userId, string $title, string $description, int $statusId): array
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO tickets (user_id, title, description, status_id)
            VALUES (:user_id, :title, :description, :status_id)
        ');
        $stmt->execute([
            'user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'status_id' => $statusId,
        ]);

        return $this->findWithRelations((int) $this->pdo->lastInsertId());
    }

    public function findWithRelations(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT t.*, s.name AS status_name, u.email AS user_email
            FROM tickets t
            JOIN statuses s ON s.id = t.status_id
            JOIN users u ON u.id = t.user_id
            WHERE t.id = :id
        ');
        $stmt->execute(['id' => $id]);

        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ticket) {
            return null;
        }

        $ticket['tags'] = $this->getTags([$ticket['id']])[$ticket['id']] ?? [];

        return $ticket;
    }

    public function paginate(
        array $filters,
        string $sortField,
        string $sortDirection,
        int $page,
        int $perPage,
        bool $includeAllUsers,
        int $userId
    ): array {
        $where = ['1=1'];
        $params = [];

        if (!$includeAllUsers) {
            $where[] = 't.user_id = :user_id';
            $params['user_id'] = $userId;
        }

        if (!empty($filters['status'])) {
            if (is_numeric($filters['status'])) {
                $where[] = 't.status_id = :status_id';
                $params['status_id'] = (int) $filters['status'];
            } else {
                $where[] = 's.name = :status_name';
                $params['status_name'] = $filters['status'];
            }
        }

        if (!empty($filters['search'])) {
            $where[] = '(t.title LIKE :search_title OR t.description LIKE :search_desc)';
            $searchValue = '%' . $filters['search'] . '%';
            $params['search_title'] = $searchValue;
            $params['search_desc'] = $searchValue;
        }

        if (!empty($filters['dateFrom'])) {
            $where[] = 't.created_at >= :date_from';
            $params['date_from'] = $filters['dateFrom'];
        }

        if (!empty($filters['dateTo'])) {
            $where[] = 't.created_at <= :date_to';
            $params['date_to'] = $filters['dateTo'];
        }

        $whereClause = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM tickets t JOIN statuses s ON s.id = t.status_id WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $allowedFields = ['id', 'created_at', 'updated_at', 'status_id'];
        $sortField = in_array($sortField, $allowedFields, true) ? $sortField : 'created_at';
        $sortDirection = $sortDirection === 'ASC' ? 'ASC' : 'DESC';

        $sql = "
            SELECT t.*, s.name AS status_name, u.email AS user_email
            FROM tickets t
            JOIN statuses s ON s.id = t.status_id
            JOIN users u ON u.id = t.user_id
            WHERE {$whereClause}
            ORDER BY t.{$sortField} {$sortDirection}
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rowsWithTags = $this->attachTags($rows);

        return [
            'data' => $rowsWithTags,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 1,
            ],
        ];
    }

    public function updateStatus(int $ticketId, int $statusId): void
    {
        $stmt = $this->pdo->prepare('UPDATE tickets SET status_id = :status_id, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'status_id' => $statusId,
            'id' => $ticketId,
        ]);
    }

    public function syncTags(int $ticketId, array $tagIds): void
    {
        $deleteStmt = $this->pdo->prepare('DELETE FROM ticket_tags WHERE ticket_id = :ticket_id');
        $deleteStmt->execute(['ticket_id' => $ticketId]);

        if (empty($tagIds)) {
            return;
        }

        $insert = $this->pdo->prepare('INSERT INTO ticket_tags (ticket_id, tag_id) VALUES (:ticket_id, :tag_id)');
        foreach ($tagIds as $tagId) {
            $insert->execute([
                'ticket_id' => $ticketId,
                'tag_id' => $tagId,
            ]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $tickets
     * @return array<int, array<string, mixed>>
     */
    private function attachTags(array $tickets): array
    {
        $tagMap = $this->getTags(array_column($tickets, 'id'));

        return array_map(static function (array $ticket) use ($tagMap) {
            $ticket['tags'] = $tagMap[$ticket['id']] ?? [];

            return $ticket;
        }, $tickets);
    }

    /**
     * @param array<int> $ticketIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function getTags(array $ticketIds): array
    {
        $ticketIds = array_filter(array_map('intval', $ticketIds));
        if (empty($ticketIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ticketIds), '?'));
        $sql = "
            SELECT tt.ticket_id, tg.*
            FROM ticket_tags tt
            JOIN tags tg ON tg.id = tt.tag_id
            WHERE tt.ticket_id IN ({$placeholders})
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($ticketIds);

        $map = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $map[$row['ticket_id']][] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'color' => $row['color'],
            ];
        }

        return $map;
    }
}

