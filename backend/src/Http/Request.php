<?php

declare(strict_types=1);

namespace App\Http;

use App\Exceptions\HttpException;

class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly array $query,
        public readonly array $body,
        public readonly array $headers,
        public readonly ?array $user = null
    ) {
    }

    public static function fromGlobals(?array $user = null): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        $input = file_get_contents('php://input') ?: '';
        $body = [];

        if (!empty($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $body = $decoded;
            } else {
                parse_str($input, $body);
            }
        }

        return new self(
            $method,
            $uri,
            $_GET ?? [],
            $body,
            $headers,
            $user
        );
    }

    public function json(): array
    {
        return $this->body;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function requireAuth(?string $role = null): array
    {
        if (!$this->user) {
            throw new HttpException('Требуется авторизация', 401);
        }

        if ($role && $this->user['role'] !== $role) {
            throw new HttpException('Недостаточно прав', 403);
        }

        return $this->user;
    }
}

