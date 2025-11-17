<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\TicketService;

class TicketController
{
    public function __construct(private readonly TicketService $tickets)
    {
    }

    public function index(Request $request, array $params = []): Response
    {
        $user = $request->requireAuth();
        $filters = [
            'status' => $request->query['status'] ?? null,
            'search' => $request->query['search'] ?? null,
            'dateFrom' => $request->query['dateFrom'] ?? null,
            'dateTo' => $request->query['dateTo'] ?? null,
        ];

        $sort = $request->query['sort'] ?? 'created_at:desc';
        $page = (int) ($request->query['page'] ?? 1);
        $perPage = (int) ($request->query['perPage'] ?? 10);

        $includeAll = ($request->query['scope'] ?? '') === 'all';
        $result = $this->tickets->list($user, $filters, $sort, $page, $perPage, $includeAll);

        return Response::json(['data' => $result['data'], 'meta' => $result['meta']]);
    }

    public function store(Request $request, array $params = []): Response
    {
        $user = $request->requireAuth('client');
        $data = $request->json();

        $ticket = $this->tickets->create(
            $user['id'],
            $data['title'] ?? '',
            $data['description'] ?? ''
        );

        return Response::json(['data' => $ticket], 201);
    }

    public function show(Request $request, array $params = []): Response
    {
        $user = $request->requireAuth();
        $ticketId = (int) ($params['id'] ?? 0);
        if ($ticketId <= 0) {
            throw new \InvalidArgumentException('Некорректный идентификатор');
        }

        $ticket = $this->tickets->findById($ticketId, $user);

        return Response::json(['data' => $ticket]);
    }
}

