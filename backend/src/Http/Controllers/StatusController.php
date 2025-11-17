<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\StatusService;

class StatusController
{
    public function __construct(private readonly StatusService $statuses)
    {
    }

    public function index(Request $request, array $params = []): Response
    {
        $request->requireAuth();

        return Response::json(['data' => $this->statuses->list()]);
    }

    public function store(Request $request, array $params = []): Response
    {
        $request->requireAuth('admin');
        $data = $request->json();
        $status = $this->statuses->create($data['name'] ?? '');

        return Response::json(['data' => $status], 201);
    }

    public function update(Request $request, array $params = []): Response
    {
        $request->requireAuth('admin');
        $data = $request->json();
        $id = (int) ($data['id'] ?? ($params['id'] ?? 0));
        if ($id <= 0) {
            throw new \InvalidArgumentException('Некорректный идентификатор');
        }
        $status = $this->statuses->update($id, $data['name'] ?? '');

        return Response::json(['data' => $status]);
    }

    public function destroy(Request $request, array $params = []): Response
    {
        $request->requireAuth('admin');
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            throw new \InvalidArgumentException('Некорректный идентификатор');
        }
        $this->statuses->delete($id);

        return Response::json(['data' => ['deleted' => true]]);
    }

}

