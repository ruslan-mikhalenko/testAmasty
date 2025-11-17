<?php

declare(strict_types=1);

namespace App\Http\Routing;

use App\Core\Container;
use App\Http\Controllers\Admin\TicketAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TicketController;
use App\Http\Request;
use FastRoute\RouteCollector;

class Routes
{
    public static function register(RouteCollector $routes, Container $container): void
    {
        $routes->addRoute('POST', '/api/auth/register', self::controller($container, AuthController::class, 'register'));
        $routes->addRoute('POST', '/api/auth/login', self::controller($container, AuthController::class, 'login'));
        $routes->addRoute('POST', '/api/auth/logout', self::controller($container, AuthController::class, 'logout'));
        $routes->addRoute('GET', '/api/auth/me', self::controller($container, AuthController::class, 'me'));

        $routes->addRoute('GET', '/api/tickets', self::controller($container, TicketController::class, 'index'));
        $routes->addRoute('POST', '/api/tickets', self::controller($container, TicketController::class, 'store'));
        $routes->addRoute('GET', '/api/tickets/{id:\d+}', self::controller($container, TicketController::class, 'show'));

        $routes->addRoute('PATCH', '/api/tickets/{id:\d+}', self::controller($container, TicketAdminController::class, 'update'));
        $routes->addRoute('POST', '/api/tickets/{id:\d+}/reply', self::controller($container, TicketAdminController::class, 'reply'));

        $routes->addRoute('GET', '/api/tags', self::controller($container, TagController::class, 'index'));
        $routes->addRoute('POST', '/api/tags', self::controller($container, TagController::class, 'store'));
        $routes->addRoute('PUT', '/api/tags/{id:\d+}', self::controller($container, TagController::class, 'update'));
        $routes->addRoute('DELETE', '/api/tags/{id:\d+}', self::controller($container, TagController::class, 'destroy'));

        $routes->addRoute('GET', '/api/statuses', self::controller($container, StatusController::class, 'index'));
        $routes->addRoute('POST', '/api/statuses', self::controller($container, StatusController::class, 'store'));
        $routes->addRoute('PUT', '/api/statuses/{id:\d+}', self::controller($container, StatusController::class, 'update'));
        $routes->addRoute('DELETE', '/api/statuses/{id:\d+}', self::controller($container, StatusController::class, 'destroy'));
    }

    private static function controller(Container $container, string $controller, string $method): callable
    {
        return static function (Request $request, array $vars = []) use ($container, $controller, $method) {
            $instance = $container->get($controller);

            return $instance->$method($request, $vars);
        };
    }
}

