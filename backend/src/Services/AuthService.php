<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use InvalidArgumentException;

class AuthService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function register(string $email, string $password, string $role = 'client'): array
    {
        $email = strtolower(trim($email));
        $this->assertEmail($email);
        $this->assertPassword($password);

        if ($this->users->findByEmail($email)) {
            throw new InvalidArgumentException('Пользователь уже существует');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $user = $this->users->create($email, $hash, $role);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        return $this->formatUser($user);
    }

    public function login(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $this->assertEmail($email);

        $user = $this->users->findByEmail($email);
        if (!$user || !password_verify($password, $user['password'])) {
            throw new InvalidArgumentException('Неверные учетные данные');
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        return $this->formatUser($user);
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    private function assertEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Некорректный email');
        }
    }

    private function assertPassword(string $password): void
    {
        if (strlen($password) < 6) {
            throw new InvalidArgumentException('Пароль должен содержать минимум 6 символов');
        }
    }

    private function formatUser(array $user): array
    {
        unset($user['password']);

        return $user;
    }
}

