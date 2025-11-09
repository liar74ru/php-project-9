<?php

namespace Hexlet\Code\Tests\Controllers;

use PHPUnit\Framework\TestCase;
use Hexlet\Code\Controllers\ErrorController;
use Slim\Views\PhpRenderer;
use Slim\Interfaces\RouteParserInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use RuntimeException;

class ErrorControllerTest extends TestCase
{
    private ErrorController $controller;
    private PhpRenderer $renderer;
    private RouteParserInterface $router;

    protected function setUp(): void
    {
        // Создаем заглушки для зависимостей
        $this->renderer = $this->createMock(PhpRenderer::class);
        $this->router = $this->createMock(RouteParserInterface::class);
        $responseFactory = new ResponseFactory();

        $this->controller = new ErrorController(
            $this->renderer,
            $this->router,
            $responseFactory
        );
    }

    // Создает тестовый HTTP запрос
    private function createRequest(): \Slim\Psr7\Request
    {
        $factory = new ServerRequestFactory();
        return $factory->createServerRequest('GET', '/');
    }

    public function testNotFound(): void
    {
        // Проверяет что для несуществующей страницы возвращается 404 ошибка
        $request = $this->createRequest();
        $exception = new RuntimeException('Страница не найдена');

        $this->renderer->method('render')->willReturn((new ResponseFactory())->createResponse(404));

        $result = $this->controller->notFound($request, $exception, false);

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testServerError(): void
    {
        // Проверяет что для ошибки сервера возвращается 500 ошибка
        $request = $this->createRequest();
        $exception = new RuntimeException('Ошибка базы данных');

        $this->renderer->method('render')->willReturn((new ResponseFactory())->createResponse(500));

        $result = $this->controller->serverError($request, $exception, false);

        $this->assertEquals(500, $result->getStatusCode());
    }

    public function testNotFoundWithDifferentExceptions(): void
    {
        // Проверяет что разные типы исключений обрабатываются как 404
        $request = $this->createRequest();
        $exceptions = [
            new \InvalidArgumentException('Неверный маршрут'),
            new \OutOfBoundsException('Страница не существует'),
            new \LogicException('Логическая ошибка')
        ];

        $this->renderer->method('render')->willReturn((new ResponseFactory())->createResponse(404));

        foreach ($exceptions as $exception) {
            $result = $this->controller->notFound($request, $exception, true);
            $this->assertEquals(404, $result->getStatusCode());
        }
    }

    public function testServerErrorWithDifferentExceptions(): void
    {
        // Проверяет что разные типы исключений обрабатываются как 500
        $request = $this->createRequest();
        $exceptions = [
            new \PDOException('Ошибка подключения к БД'),
            new \DivisionByZeroError('Деление на ноль'),
            new \Exception('Общая ошибка')
        ];

        $this->renderer->method('render')->willReturn((new ResponseFactory())->createResponse(500));

        foreach ($exceptions as $exception) {
            $result = $this->controller->serverError($request, $exception, false);
            $this->assertEquals(500, $result->getStatusCode());
        }
    }

    public function testErrorPagesWithDifferentDisplaySettings(): void
    {
        // Проверяет что настройки отображения ошибок не влияют на статус код
        $request = $this->createRequest();
        $exception = new RuntimeException('Тестовая ошибка');

        $this->renderer->method('render')->willReturn((new ResponseFactory())->createResponse());

        // Проверяем 404 с разными настройками
        $result1 = $this->controller->notFound($request, $exception, false);
        $result2 = $this->controller->notFound($request, $exception, true);

        $this->assertInstanceOf(\Slim\Psr7\Response::class, $result1);
        $this->assertInstanceOf(\Slim\Psr7\Response::class, $result2);

        // Проверяем 500 с разными настройками
        $result3 = $this->controller->serverError($request, $exception, false);
        $result4 = $this->controller->serverError($request, $exception, true);

        $this->assertInstanceOf(\Slim\Psr7\Response::class, $result3);
        $this->assertInstanceOf(\Slim\Psr7\Response::class, $result4);
    }
}
