<?php

namespace Hexlet\Code\Tests\Controllers;

use PHPUnit\Framework\TestCase;
use Hexlet\Code\Controllers\UrlController;
use Hexlet\Code\Models\Url;
use Hexlet\Code\Models\UrlCheck;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use Slim\Routing\RouteParser;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
* @group url-controller
*/

class UrlControllerTest extends TestCase
{
    private UrlController $controller;
    private Url $urlModel;
    private UrlCheck $urlCheckModel;
    private PhpRenderer $renderer;
    private Messages $flash;
    private RouteParser $router;

    protected function setUp(): void
    {
        // Создаем моки зависимостей
        $this->urlModel = $this->createMock(Url::class);
        $this->urlCheckModel = $this->createMock(UrlCheck::class);
        $this->renderer = $this->createMock(PhpRenderer::class);
        $this->flash = $this->createMock(Messages::class);
        $this->router = $this->createMock(RouteParser::class);

        $this->controller = new UrlController(
            $this->urlModel,
            $this->urlCheckModel,
            $this->renderer,
            $this->flash,
            $this->router
        );
    }

    private function createRequest(
        string $method = 'GET',
        string $path = '/',
        array $body = []
    ): \Slim\Psr7\Request {
        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest($method, $path);

        if (!empty($body)) {
            $request = $request->withParsedBody($body);
        }

        return $request;
    }

    private function createResponse(): Response
    {
        return (new ResponseFactory())->createResponse();
    }

    public function testHome(): void
    {
        // Arrange
        $request = $this->createRequest();
        $response = $this->createResponse();
        
        $this->flash->method('getMessages')
            ->willReturn([]);
        
        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $response,
                '/index.phtml',
                $this->callback(function ($params) {
                    return isset($params['urlValue']) 
                        && $params['urlValue'] === ''
                        && isset($params['router']);
                })
            )
            ->willReturn($response);

        // Act
        $result = $this->controller->home($request, $response);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testIndex(): void
    {
        // Arrange
        $request = $this->createRequest();
        $response = $this->createResponse();
        
        $urls = [
            ['id' => 1, 'name' => 'https://example.com'],
            ['id' => 2, 'name' => 'https://google.com']
        ];
        
        $this->urlModel->method('findAll')
            ->willReturn($urls);
        
        $this->urlCheckModel->method('findLastCheck')
            ->willReturnMap([
                [1, ['created_at' => '2023-01-01', 'status_code' => 200]],
                [2, null]
            ]);
        
        $this->flash->method('getMessages')
            ->willReturn([]);
        
        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $response,
                'urls.phtml',
                $this->callback(function ($params) use ($urls) {
                    return isset($params['urls']) 
                        && count($params['urls']) === 2
                        && $params['urls'][0]['name'] === 'https://example.com';
                })
            )
            ->willReturn($response);

        // Act
        $result = $this->controller->index($request, $response);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testShowWithExistingUrl(): void
    {
        // Arrange
        $request = $this->createRequest('GET', '/urls/1');
        $response = $this->createResponse();
        $args = ['id' => 1];
        
        $urlData = ['id' => 1, 'name' => 'https://example.com'];
        $checks = [['id' => 1, 'status_code' => 200]];
        
        $this->urlModel->method('find')
            ->with(1)
            ->willReturn($urlData);
        
        $this->urlCheckModel->method('findByUrlId')
            ->with(1)
            ->willReturn($checks);
        
        $this->flash->method('getMessages')
            ->willReturn([]);
        
        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $response,
                'url.phtml',
                $this->callback(function ($params) use ($urlData) {
                    return $params['urlData'] === $urlData
                        && isset($params['checks']);
                })
            )
            ->willReturn($response);

        // Act
        $result = $this->controller->show($request, $response, $args);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testShowWithNonExistingUrl(): void
    {
        // Arrange
        $request = $this->createRequest('GET', '/urls/999');
        $response = $this->createResponse();
        $args = ['id' => 999];
        
        $this->urlModel->method('find')
            ->with(999)
            ->willReturn(null);
        
        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $this->callback(function ($response) {
                    return $response->getStatusCode() === 404;
                }),
                '404.phtml',
                $this->callback(function ($params) {
                    return isset($params['router']);
                })
            )
            ->willReturn($response);

        // Act
        $result = $this->controller->show($request, $response, $args);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
    public function testStoreWithValidUrl(): void
    {
        // Arrange
        $request = $this->createRequest('POST', '/urls', [
            'url' => ['name' => 'https://example.com']
        ]);
        $response = $this->createResponse();
        
        $this->urlModel->method('findByName')
            ->with('https://example.com')
            ->willReturn(null);
        
        $this->urlModel->method('save')
            ->with('https://example.com')
            ->willReturn(1);
        
        $this->flash->expects($this->once())
            ->method('addMessage')
            ->with('success', 'Страница успешно добавлена');
        
        $this->router->method('urlFor')
            ->with('urls.show', ['id' => 1])
            ->willReturn('/urls/1');

        // Act
        $result = $this->controller->store($request, $response);

        // Assert
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/urls/1', $result->getHeaderLine('Location'));
    }

    public function testStoreWithExistingUrl(): void
    {
        // Arrange
        $request = $this->createRequest('POST', '/urls', [
            'url' => ['name' => 'https://example.com']
        ]);
        $response = $this->createResponse();
        
        $existingUrl = ['id' => 1, 'name' => 'https://example.com'];
        
        $this->urlModel->method('findByName')
            ->with('https://example.com')
            ->willReturn($existingUrl);
        
        $this->flash->expects($this->once())
            ->method('addMessage')
            ->with('info', 'Страница уже существует');
        
        $this->router->method('urlFor')
            ->with('urls.show', ['id' => 1])
            ->willReturn('/urls/1');

        // Act
        $result = $this->controller->store($request, $response);

        // Assert
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/urls/1', $result->getHeaderLine('Location'));
    }

    public function testStoreWithInvalidUrl(): void
    {
        // Arrange
        $request = $this->createRequest('POST', '/urls', [
            'url' => ['name' => 'invalid-url']
        ]);
        $response = $this->createResponse();
        
        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $this->callback(function ($response) {
                    return $response->getStatusCode() === 422;
                }),
                'index.phtml',
                $this->callback(function ($params) {
                    return isset($params['errors'])
                        && $params['urlValue'] === 'invalid-url'
                        && $params['showValidation'] === true;
                })
            )
            ->willReturn($response);

        // Act
        $result = $this->controller->store($request, $response);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testCreateChecksSuccess(): void
    {
        // Arrange
        $request = $this->createRequest('POST', '/urls/1/checks');
        $response = $this->createResponse();
        $args = ['id' => 1];
        
        $urlData = ['id' => 1, 'name' => 'https://example.com'];
        
        $this->urlModel->method('find')
            ->with(1)
            ->willReturn($urlData);
        
        $this->flash->expects($this->once())
            ->method('addMessage')
            ->with('success', 'Страница успешно проверена');
        
        $this->router->method('urlFor')
            ->with('urls.show', ['id' => 1])
            ->willReturn('/urls/1');

        // Act
        $result = $this->controller->createChecks($request, $response, $args);

        // Assert
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/urls/1', $result->getHeaderLine('Location'));
    }

    public function testCreateChecksWithNonExistingUrl(): void
    {
        // Arrange
        $request = $this->createRequest('POST', '/urls/999/checks');
        $response = $this->createResponse();
        $args = ['id' => 999];
        
        $this->urlModel->method('find')
            ->with(999)
            ->willReturn(null);
        
        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $this->callback(function ($response) {
                    return $response->getStatusCode() === 404;
                }),
                '404.phtml',
                $this->callback(function ($params) {
                    return isset($params['router']);
                })
            )
            ->willReturn($response);

        // Act
        $result = $this->controller->createChecks($request, $response, $args);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

public function testStoreWithInvalidUrlFormat(): void
{
    $this->runInvalidUrlTest('invalid-url');
}

public function testStoreWithEmptyUrl(): void
{
    $this->runInvalidUrlTest('');
}

public function testStoreWithIncompleteUrl(): void
{
    $this->runInvalidUrlTest('http://');
}

public function testStoreWithDomainOnly(): void
{
    $this->runInvalidUrlTest('example.com');
}

private function runInvalidUrlTest(string $invalidUrl): void
{
    // Arrange
    $request = $this->createRequest('POST', '/urls', [
        'url' => ['name' => $invalidUrl]
    ]);
    $response = $this->createResponse();
    
    $this->renderer->expects($this->once())
        ->method('render')
        ->with(
            $this->callback(function ($response) {
                return $response->getStatusCode() === 422;
            }),
            'index.phtml',
            $this->callback(function ($params) use ($invalidUrl) {
                return $params['urlValue'] === $invalidUrl;
            })
        )
        ->willReturn($response);

    // Act
    $result = $this->controller->store($request, $response);

    // Assert
    $this->assertInstanceOf(ResponseInterface::class, $result);
}

    public function testIndexWithLastCheckData(): void
    {
        // Arrange
        $request = $this->createRequest();
        $response = $this->createResponse();
        
        $urls = [['id' => 1, 'name' => 'https://example.com']];
        
        $this->urlModel->method('findAll')
            ->willReturn($urls);
        
        $this->urlCheckModel->method('findLastCheck')
            ->with(1)
            ->willReturn([
                'created_at' => '2023-01-01 10:00:00',
                'status_code' => 200
            ]);
        
        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $response,
                'urls.phtml',
                $this->callback(function ($params) {
                    return $params['urls'][0]['last_check_date'] === '2023-01-01 10:00:00'
                        && $params['urls'][0]['last_status_code'] === 200;
                })
            )
            ->willReturn($response);

        // Act
        $result = $this->controller->index($request, $response);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}   