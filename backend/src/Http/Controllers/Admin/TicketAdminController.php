<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Request;
use App\Http\Response;
use App\Services\AdminTicketService;

class TicketAdminController
{
    public function __construct(private readonly AdminTicketService $tickets)
    {
    }

    public function update(Request $request, array $params = []): Response
    {
        $request->requireAuth('admin');
        $data = $request->json();
        $ticketId = (int) ($data['id'] ?? ($params['id'] ?? 0));
        if ($ticketId <= 0) {
            throw new \InvalidArgumentException('Некорректный идентификатор');
        }

        $ticket = $this->tickets->update(
            $ticketId,
            [
                'status_id' => $data['status_id'] ?? null,
                'tags' => $data['tags'] ?? [],
            ]
        );

        return Response::json(['data' => $ticket]);
    }

    public function reply(Request $request, array $params = []): Response
    {
        $admin = $request->requireAuth('admin');
        $data = $request->json();
        $ticketId = (int) ($data['id'] ?? ($params['id'] ?? 0));
        if ($ticketId <= 0) {
            throw new \InvalidArgumentException('Некорректный идентификатор');
        }

        $reply = $this->tickets->addReply(
            $ticketId,
            $admin['id'],
            $data['message'] ?? ''
        );

        return Response::json(['data' => $reply], 201);
    }
}

