<?php

namespace Hexlet\Code\Tests\Controllers;

use PHPUnit\Framework\TestCase;
use Hexlet\Code\Controllers\UrlController;
use Hexlet\Code\Models\Url;
use Hexlet\Code\Models\UrlCheck;
use Hexlet\Code\Services\UrlService;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use Slim\Routing\RouteParser;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Response;
use Slim\Exception\HttpNotFoundException;

class UrlControllerTest extends TestCase
{
    private UrlController $controller;
    private Url $urlModel;
    private UrlCheck $urlCheckModel;
    private UrlService $urlService;
    private PhpRenderer $renderer;
    private Messages $flash;
    private RouteParser $router;

    protected function setUp(): void
    {
        // Создаем заглушки для всех зависимостей
        $this->urlModel = $this->createMock(Url::class);
        $this->urlCheckModel = $this->createMock(UrlCheck::class);
        $this->urlService = $this->createMock(UrlService::class);
        $this->renderer = $this->createMock(PhpRenderer::class);
        $this->flash = $this->createMock(Messages::class);
        $this->router = $this->createMock(RouteParser::class);

        $this->controller = new UrlController(
            $this->urlModel,
            $this->urlCheckModel,
            $this->urlService,
            $this->renderer,
            $this->flash,
            $this->router
        );
    }

    // Создает тестовый HTTP запрос
    private function createRequest(string $method = 'GET', array $body = []): \Slim\Psr7\Request
    {
        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest($method, '/');
        return !empty($body) ? $request->withParsedBody($body) : $request;
    }

    // Создает тестовый HTTP ответ
    private function createResponse(): Response
    {
        return (new ResponseFactory())->createResponse();
    }

    public function testHome(): void
    {
        // Проверяет что главная страница открывается
        $request = $this->createRequest();
        $response = $this->createResponse();

        $this->flash->method('getMessages')->willReturn([]);
        $this->renderer->method('render')->willReturn($response);

        $result = $this->controller->home($request, $response);

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testIndex(): void
    {
        // Проверяет что страница со списком URL открывается
        $request = $this->createRequest();
        $response = $this->createResponse();

        $urls = [['id' => 1, 'name' => 'https://example.com']];
        $this->urlService->method('findAllWithLastChecks')->willReturn($urls);
        $this->flash->method('getMessages')->willReturn([]);
        $this->renderer->method('render')->willReturn($response);

        $result = $this->controller->index($request, $response);

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testShowWithExistingUrl(): void
    {
        // Проверяет что страница существующего URL открывается
        $request = $this->createRequest();
        $response = $this->createResponse();
        $args = ['id' => 1];

        $urlData = ['id' => 1, 'name' => 'https://example.com'];
        $checks = [['id' => 1, 'status_code' => 200]];

        $this->urlModel->method('findByIdUrl')->willReturn($urlData);
        $this->urlCheckModel->method('findByUrlId')->willReturn($checks);
        $this->flash->method('getMessages')->willReturn([]);
        $this->renderer->method('render')->willReturn($response);

        $result = $this->controller->show($request, $response, $args);

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testShowWithNonExistingUrl(): void
    {
        // Проверяет что для несуществующего URL бросается HttpNotFoundException
        $request = $this->createRequest();
        $response = $this->createResponse();
        $args = ['id' => 999];

        $this->urlModel->method('findByIdUrl')->willReturn(null);

        // Ожидаем исключение
        $this->expectException(HttpNotFoundException::class);

        $this->controller->show($request, $response, $args);
    }

    public function testStoreWithValidUrl(): void
    {
        // Проверяет что валидный URL сохраняется и происходит редирект
        $request = $this->createRequest('POST', ['url' => ['name' => 'https://example.com']]);
        $response = $this->createResponse();

        $this->urlModel->method('findByNameUrl')->willReturn(null);
        $this->urlModel->method('saveNewUrl')->willReturn(1);
        $this->flash->method('addMessage');
        $this->router->method('urlFor')->willReturn('/urls/1');

        $result = $this->controller->store($request, $response);

        $this->assertEquals(302, $result->getStatusCode()); // Проверяем редирект
    }

    public function testStoreWithExistingUrl(): void
    {
        // Проверяет что при добавлении существующего URL показывается сообщение
        $request = $this->createRequest('POST', ['url' => ['name' => 'https://example.com']]);
        $response = $this->createResponse();

        $existingUrl = ['id' => 1, 'name' => 'https://example.com'];
        $this->urlModel->method('findByNameUrl')->willReturn($existingUrl);
        $this->flash->method('addMessage');
        $this->router->method('urlFor')->willReturn('/urls/1');

        $result = $this->controller->store($request, $response);

        $this->assertEquals(302, $result->getStatusCode());
    }

    public function testStoreWithInvalidUrl(): void
    {
        // Проверяет что невалидный URL не сохраняется и показывается форма с ошибкой
        $request = $this->createRequest('POST', ['url' => ['name' => 'invalid-url']]);
        $response = $this->createResponse();

        $this->renderer->method('render')->willReturn($response);

        $result = $this->controller->store($request, $response);

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testCreateChecksSuccess(): void
    {
        // Проверяет что проверка URL запускается и происходит редирект
        $request = $this->createRequest('POST');
        $response = $this->createResponse();
        $args = ['id' => 1];

        $urlData = ['id' => 1, 'name' => 'https://example.com'];
        $this->urlModel->method('findByIdUrl')->willReturn($urlData);
        $this->flash->method('addMessage');
        $this->router->method('urlFor')->willReturn('/urls/1');

        $result = $this->controller->createChecks($request, $response, $args);

        $this->assertEquals(302, $result->getStatusCode());
    }

    public function testCreateChecksWithNonExistingUrl(): void
    {
        // Проверяет что проверка не запускается для несуществующего URL
        $request = $this->createRequest('POST');
        $response = $this->createResponse();
        $args = ['id' => 999];

        $this->urlModel->method('findByIdUrl')->willReturn(null);

        // Ожидаем исключение
        $this->expectException(HttpNotFoundException::class);

        $this->controller->createChecks($request, $response, $args);
    }

    public function testStoreWithVariousInvalidUrls(): void
    {
        // Проверяет разные варианты невалидных URL
        $invalidUrls = [
            'invalid-url',
            '',
            'http://',
            'example.com',
            'https://gooаываgle.com',
            'httpsss://abcabca@test.ru',
            'https://goo gle.com'
        ];

        foreach ($invalidUrls as $invalidUrl) {
            $request = $this->createRequest('POST', ['url' => ['name' => $invalidUrl]]);
            $response = $this->createResponse();

            $this->renderer->method('render')->willReturn($response);

            $result = $this->controller->store($request, $response);

            $this->assertInstanceOf(Response::class, $result);
        }
    }
}
