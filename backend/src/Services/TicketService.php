<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReplyRepository;
use App\Repositories\StatusRepository;
use App\Repositories\TagRepository;
use App\Repositories\TicketRepository;
use InvalidArgumentException;

class TicketService
{
    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly StatusRepository $statuses,
        private readonly TagRepository $tags,
        private readonly ReplyRepository $replies
    ) {
    }

    public function list(array $user, array $filters, string $sort, int $page, int $perPage, bool $forceAll = false): array
    {
        [$sortField, $sortDir] = $this->parseSort($sort);
        $scopeAll = $user['role'] === 'admin' && $forceAll;

        return $this->tickets->paginate(
            filters: $filters,
            sortField: $sortField,
            sortDirection: $sortDir,
            page: $page,
            perPage: $perPage,
            includeAllUsers: $scopeAll,
            userId: $user['id']
        );
    }

    public function create(int $userId, string $title, string $description): array
    {
        $title = trim($title);
        $description = trim($description);

        if ($title === '' || $description === '') {
            throw new InvalidArgumentException('Заполните название и описание');
        }

        $status = $this->statuses->getDefaultStatus();
        if (!$status) {
            throw new InvalidArgumentException('Не найден статус по умолчанию');
        }

        return $this->tickets->create($userId, $title, $description, $status['id']);
    }

    public function findById(int $ticketId, array $user): array
    {
        $ticket = $this->tickets->findWithRelations($ticketId);

        if (!$ticket) {
            throw new InvalidArgumentException('Обращение не найдено');
        }

        if ($user['role'] !== 'admin' && (int) $ticket['user_id'] !== (int) $user['id']) {
            throw new InvalidArgumentException('Нет доступа к обращению');
        }

        $ticket['tags'] = $this->tags->getForTicket($ticketId);
        $ticket['replies'] = $this->replies->getByTicket($ticketId);

        return $ticket;
    }

    private function parseSort(string $sort): array
    {
        [$field, $direction] = array_pad(explode(':', $sort), 2, 'desc');
        $field = in_array($field, ['created_at', 'updated_at', 'status_id'], true) ? $field : 'created_at';
        $direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';

        return [$field, $direction];
    }
}

