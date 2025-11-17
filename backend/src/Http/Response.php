<?php

declare(strict_types=1);

namespace App\Http;

class Response
{
    public function __construct(
        private readonly array $payload,
        private readonly int $status = 200,
        private readonly array $headers = ['Content-Type' => 'application/json']
    ) {
    }

    public static function json(array $payload, int $status = 200, array $headers = []): self
    {
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);

        return new self($payload, $status, $headers);
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo json_encode($this->payload, JSON_UNESCAPED_UNICODE);
    }
}

