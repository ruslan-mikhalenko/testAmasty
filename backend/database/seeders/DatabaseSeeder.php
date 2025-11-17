<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dotenv\Dotenv;

$basePath = dirname(__DIR__, 2);
if (file_exists($basePath . '/.env')) {
    Dotenv::createImmutable($basePath)->safeLoad();
}

$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $_ENV['DB_HOST'] ?? '127.0.0.1',
        $_ENV['DB_PORT'] ?? '3306',
        $_ENV['DB_DATABASE'] ?? 'support_tracker'
    ),
    $_ENV['DB_USERNAME'] ?? 'root',
    $_ENV['DB_PASSWORD'] ?? '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]
);

$statuses = [
    'ToDo',
    'InProgress',
    'Ready For Review',
    'Done',
];

foreach ($statuses as $status) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM statuses WHERE name = :name');
    $stmt->execute(['name' => $status]);
    if (!(int) $stmt->fetchColumn()) {
        $insert = $pdo->prepare('INSERT INTO statuses (name) VALUES (:name)');
        $insert->execute(['name' => $status]);
    }
}

$adminEmail = $_ENV['ADMIN_EMAIL'] ?? 'admin@example.com';
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
$stmt->execute(['email' => $adminEmail]);

if (!(int) $stmt->fetchColumn()) {
    $password = password_hash($_ENV['ADMIN_PASSWORD'] ?? 'admin123', PASSWORD_BCRYPT);
    $create = $pdo->prepare('INSERT INTO users (email, password, role) VALUES (:email, :password, :role)');
    $create->execute([
        'email' => $adminEmail,
        'password' => $password,
        'role' => 'admin',
    ]);
}

echo "Seed завершен\n";
