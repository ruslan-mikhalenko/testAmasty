<?php

declare(strict_types=1);

use App\Core\Container;
use App\Repositories\ReplyRepository;
use App\Repositories\StatusRepository;
use App\Repositories\TagRepository;
use App\Repositories\TicketRepository;
use App\Repositories\UserRepository;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\Admin\TicketAdminController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\StatusController;
use App\Services\AdminTicketService;
use App\Services\AuthService;
use App\Services\StatusService;
use App\Services\TagService;
use App\Services\TicketService;

$container = new Container();

$container->set(PDO::class, static function (): PDO {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $_ENV['DB_HOST'] ?? '127.0.0.1',
        $_ENV['DB_PORT'] ?? '3306',
        $_ENV['DB_DATABASE'] ?? 'support_tracker'
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    return new PDO(
        $dsn,
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        $options
    );
});

$container->set(UserRepository::class, static fn(Container $c) => new UserRepository($c->get(PDO::class)));
$container->set(StatusRepository::class, static fn(Container $c) => new StatusRepository($c->get(PDO::class)));
$container->set(TagRepository::class, static fn(Container $c) => new TagRepository($c->get(PDO::class)));
$container->set(TicketRepository::class, static fn(Container $c) => new TicketRepository($c->get(PDO::class)));
$container->set(ReplyRepository::class, static fn(Container $c) => new ReplyRepository($c->get(PDO::class)));

$container->set(AuthService::class, static fn(Container $c) => new AuthService($c->get(UserRepository::class)));
$container->set(TicketService::class, static fn(Container $c) => new TicketService(
    $c->get(TicketRepository::class),
    $c->get(StatusRepository::class),
    $c->get(TagRepository::class),
    $c->get(ReplyRepository::class)
));
$container->set(AdminTicketService::class, static fn(Container $c) => new AdminTicketService(
    $c->get(TicketRepository::class),
    $c->get(TagRepository::class),
    $c->get(StatusRepository::class),
    $c->get(ReplyRepository::class)
));
$container->set(TagService::class, static fn(Container $c) => new TagService($c->get(TagRepository::class)));
$container->set(StatusService::class, static fn(Container $c) => new StatusService($c->get(StatusRepository::class)));

$container->set(AuthController::class, static fn(Container $c) => new AuthController($c->get(AuthService::class)));
$container->set(TicketController::class, static fn(Container $c) => new TicketController($c->get(TicketService::class)));
$container->set(TicketAdminController::class, static fn(Container $c) => new TicketAdminController($c->get(AdminTicketService::class)));
$container->set(TagController::class, static fn(Container $c) => new TagController($c->get(TagService::class)));
$container->set(StatusController::class, static fn(Container $c) => new StatusController($c->get(StatusService::class)));

return $container;
