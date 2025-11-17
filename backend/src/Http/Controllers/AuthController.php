<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;

class AuthController
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function register(Request $request): Response
    {
        $data = $request->json();
        $user = $this->auth->register(
            $data['email'] ?? '',
            $data['password'] ?? '',
            'client'
        );

        return Response::json(['data' => $user], 201);
    }

    public function login(Request $request): Response
    {
        $data = $request->json();
        $user = $this->auth->login(
            $data['email'] ?? '',
            $data['password'] ?? ''
        );

        return Response::json(['data' => $user]);
    }

    public function logout(Request $request): Response
    {
        $request->requireAuth();
        $this->auth->logout();

        return Response::json(['data' => ['message' => 'Вы вышли из системы']]);
    }

    public function me(Request $request): Response
    {
        $user = $request->user;

        if (!$user) {
            return Response::json(['data' => null]);
        }

        unset($user['password']);

        return Response::json(['data' => $user]);
    }
}

