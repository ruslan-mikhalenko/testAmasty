<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReplyRepository;
use App\Repositories\StatusRepository;
use App\Repositories\TagRepository;
use App\Repositories\TicketRepository;
use InvalidArgumentException;

class AdminTicketService
{
    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly TagRepository $tags,
        private readonly StatusRepository $statuses,
        private readonly ReplyRepository $replies
    ) {
    }

    public function update(int $ticketId, array $payload): array
    {
        $ticket = $this->tickets->findWithRelations($ticketId);
        if (!$ticket) {
            throw new InvalidArgumentException('Обращение не найдено');
        }

        if (!empty($payload['status_id'])) {
            $status = $this->statuses->find((int) $payload['status_id']);
            if (!$status) {
                throw new InvalidArgumentException('Статус не найден');
            }
            $this->tickets->updateStatus($ticketId, (int) $payload['status_id']);
        }

        if (isset($payload['tags'])) {
            $this->tickets->syncTags($ticketId, array_map('intval', $payload['tags']));
        }

        $updated = $this->tickets->findWithRelations($ticketId);
        if ($updated) {
            $updated['tags'] = $this->tags->getForTicket($ticketId);
            $updated['replies'] = $this->replies->getByTicket($ticketId);
        }

        return $updated ?? [];
    }

    public function addReply(int $ticketId, int $adminId, string $message): array
    {
        $message = trim($message);
        if ($message === '') {
            throw new InvalidArgumentException('Текст ответа обязателен');
        }

        $ticket = $this->tickets->findWithRelations($ticketId);
        if (!$ticket) {
            throw new InvalidArgumentException('Обращение не найдено');
        }

        return $this->replies->create($ticketId, $adminId, $message);
    }
}

