<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(string $email, string $passwordHash, string $role = 'client'): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (email, password, role) VALUES (:email, :password, :role)');
        $stmt->execute([
            'email' => $email,
            'password' => $passwordHash,
            'role' => $role,
        ]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }
}

