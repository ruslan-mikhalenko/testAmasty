<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\Container;
use App\Exceptions\HttpException;
use App\Http\Routing\Routes;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

class Router
{
    public static function handle(Request $request, Container $container): Response
    {
        $dispatcher = self::makeDispatcher($container);

        $uri = parse_url($request->uri, PHP_URL_PATH) ?: '/';
        $routeInfo = $dispatcher->dispatch($request->method, rawurldecode($uri));

        return match ($routeInfo[0]) {
            Dispatcher::NOT_FOUND => Response::json(['errors' => ['Ресурс не найден']], 404),
            Dispatcher::METHOD_NOT_ALLOWED => Response::json(['errors' => ['Метод не поддерживается']], 405),
            Dispatcher::FOUND => self::executeHandler($routeInfo[1], $request, $routeInfo[2]),
            default => Response::json(['errors' => ['Неизвестная ошибка']], 500),
        };
    }

    private static function makeDispatcher(Container $container): Dispatcher
    {
        return simpleDispatcher(static function (RouteCollector $collector) use ($container) {
            Routes::register($collector, $container);
        });
    }

    private static function executeHandler(callable $handler, Request $request, array $vars): Response
    {
        try {
            $result = $handler($request, $vars);
        } catch (HttpException $exception) {
            return Response::json(['errors' => [$exception->getMessage()]], $exception->status());
        } catch (\Throwable $throwable) {
            $status = $throwable instanceof \InvalidArgumentException ? 422 : 400;

            return Response::json([
                'errors' => [$throwable->getMessage()],
            ], $status);
        }

        return $result instanceof Response ? $result : Response::json($result);
    }
}

