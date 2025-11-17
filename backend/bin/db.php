#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;

$basePath = dirname(__DIR__);
if (file_exists($basePath . '/.env')) {
    Dotenv::createImmutable($basePath)->safeLoad();
}

$command = $argv[1] ?? null;

if (!$command) {
    echo "Доступные команды: migrate, seed\n";
    exit(0);
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

match ($command) {
    'migrate' => runMigrations($pdo, $basePath . '/database/migrations'),
    'seed' => require $basePath . '/database/seeders/DatabaseSeeder.php',
    default => print "Неизвестная команда\n",
};

function runMigrations(PDO $pdo, string $path): void
{
    $files = glob($path . '/*.sql');
    sort($files);

    foreach ($files as $file) {
        $sql = file_get_contents($file);
        if ($sql === false) {
            continue;
        }

        echo "Выполняется {$file}\n";

        // Разбиваем SQL на отдельные запросы по точке с запятой
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt) && !preg_match('/^--/', $stmt)
        );

        foreach ($statements as $statement) {
            if (trim($statement) === '') {
                continue;
            }
            try {
                $pdo->exec($statement);
            } catch (\PDOException $e) {
                // Пропускаем ошибки создания индексов если они уже существуют
                if (str_contains($e->getMessage(), 'Duplicate key name')) {
                    continue;
                }
                throw $e;
            }
        }
    }

    echo "Миграции выполнены\n";
}
