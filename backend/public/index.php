<?php

declare(strict_types=1);

use App\Core\Container;
use App\Http\Request;
use App\Http\Router;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$basePath = dirname(__DIR__);

if (file_exists($basePath . '/.env')) {
    $dotenv = Dotenv::createImmutable($basePath);
    $dotenv->safeLoad();
}

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

$sessionName = $_ENV['SESSION_NAME'] ?? 'tracker_session';
if (session_status() === PHP_SESSION_NONE) {
    session_name($sessionName);
    session_start();
}

/** @var Container $container */
$container = require $basePath . '/bootstrap/container.php';

$currentUser = null;
if (!empty($_SESSION['user_id'])) {
    try {
        $userRepo = $container->get(App\Repositories\UserRepository::class);
        $currentUser = $userRepo->findById((int) $_SESSION['user_id']);
    } catch (\Throwable $e) {
        // Игнорируем ошибки загрузки пользователя - просто не устанавливаем его
        $currentUser = null;
    }
}

try {
    $request = Request::fromGlobals($currentUser);
    $response = Router::handle($request, $container);
    $response->send();
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['errors' => ['Внутренняя ошибка сервера']]);
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
}
