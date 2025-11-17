<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\TagService;

class TagController
{
    public function __construct(private readonly TagService $tags) {}

    public function index(Request $request, array $params = []): Response
    {
        $request->requireAuth();
        $items = $this->tags->list();

        return Response::json(['data' => $items]);
    }

    public function store(Request $request, array $params = []): Response
    {
        $request->requireAuth('admin');
        $data = $request->json();
        $tag = $this->tags->create($data['name'] ?? '', $data['color'] ?? '#111827');

        return Response::json(['data' => $tag], 201);
    }

    public function update(Request $request, array $params = []): Response
    {
        $request->requireAuth('admin');
        $data = $request->json();
        $id = (int) ($data['id'] ?? ($params['id'] ?? 0));
        if ($id <= 0) {
            throw new \InvalidArgumentException('Некорректный идентификатор');
        }
        $tag = $this->tags->update($id, $data['name'] ?? '', $data['color'] ?? '#111827');

        return Response::json(['data' => $tag]);
    }

    public function destroy(Request $request, array $params = []): Response
    {
        $request->requireAuth('admin');
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            throw new \InvalidArgumentException('Некорректный идентификатор');
        }
        $this->tags->delete($id);

        return Response::json(['data' => ['deleted' => true]]);
    }
}
