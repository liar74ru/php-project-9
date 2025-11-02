<?php

namespace Hexlet\Code\Tests\Controllers;

use PHPUnit\Framework\TestCase;
use Hexlet\Code\Controllers\ErrorController;
use Slim\Views\PhpRenderer;
use Slim\Interfaces\RouteParserInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * @group error-controller
 */
class ErrorControllerTest extends TestCase
{
    private ErrorController $controller;
    private PhpRenderer $renderer;
    private RouteParserInterface $router;
    private ResponseFactoryInterface $responseFactory;

    protected function setUp(): void
    {
        // Создаем моки зависимостей
        $this->renderer = $this->createMock(PhpRenderer::class);
        $this->router = $this->createMock(RouteParserInterface::class);
        $this->responseFactory = new ResponseFactory();

        $this->controller = new ErrorController(
            $this->renderer,
            $this->router,
            $this->responseFactory
        );
    }

    private function createRequest(): \Slim\Psr7\Request
    {
        $factory = new ServerRequestFactory();
        return $factory->createServerRequest('GET', '/');
    }

    public function testNotFound(): void
    {
        // Arrange
        $request = $this->createRequest();
        $exception = new RuntimeException('Page not found');
        $displayErrorDetails = false;

        $expectedResponse = $this->responseFactory->createResponse(404);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $this->callback(function ($response) {
                    return $response->getStatusCode() === 404;
                }),
                '404.phtml',
                $this->callback(function ($params) {
                    return isset($params['router']) 
                        && $params['router'] === $this->router;
                })
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->controller->notFound($request, $exception, $displayErrorDetails);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testNotFoundWithDifferentExceptions(): void
    {
        // Arrange
        $request = $this->createRequest();
        $exception = new \InvalidArgumentException('Invalid route');
        $displayErrorDetails = true;

        $expectedResponse = $this->responseFactory->createResponse(404);

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
            ->willReturn($expectedResponse);

        // Act
        $result = $this->controller->notFound($request, $exception, $displayErrorDetails);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testServerError(): void
    {
        // Arrange
        $request = $this->createRequest();
        $exception = new RuntimeException('Database connection failed');
        $displayErrorDetails = false;

        $expectedResponse = $this->responseFactory->createResponse(500);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $this->callback(function ($response) {
                    return $response->getStatusCode() === 500;
                }),
                '500.phtml',
                $this->callback(function ($params) {
                    return isset($params['router']) 
                        && $params['router'] === $this->router;
                })
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->controller->serverError($request, $exception, $displayErrorDetails);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(500, $result->getStatusCode());
    }

    public function testServerErrorWithDisplayErrorDetailsTrue(): void
    {
        // Arrange
        $request = $this->createRequest();
        $exception = new \PDOException('SQLSTATE[HY000] [2002] Connection refused');
        $displayErrorDetails = true;

        $expectedResponse = $this->responseFactory->createResponse(500);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $this->callback(function ($response) {
                    return $response->getStatusCode() === 500;
                }),
                '500.phtml',
                $this->callback(function ($params) {
                    // Проверяем, что router передается в шаблон
                    return isset($params['router']) 
                        && $params['router'] === $this->router;
                })
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->controller->serverError($request, $exception, $displayErrorDetails);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testServerErrorWithDifferentExceptionTypes(): void
    {
        // Arrange
        $request = $this->createRequest();
        $exception = new \DivisionByZeroError('Division by zero');
        $displayErrorDetails = false;

        $expectedResponse = $this->responseFactory->createResponse(500);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $this->callback(function ($response) {
                    return $response->getStatusCode() === 500;
                }),
                '500.phtml',
                $this->callback(function ($params) {
                    return isset($params['router']);
                })
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->controller->serverError($request, $exception, $displayErrorDetails);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testNotFoundWithDifferentDisplayErrorDetailsValues(): void
    {
        // Test with displayErrorDetails = false
        $request = $this->createRequest();
        $exception = new RuntimeException('Test exception');
        
        $expectedResponse = $this->responseFactory->createResponse(404);

        $this->renderer->expects($this->exactly(2))
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
            ->willReturn($expectedResponse);

        // Act - test with false
        $result1 = $this->controller->notFound($request, $exception, false);
        
        // Act - test with true
        $result2 = $this->controller->notFound($request, $exception, true);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result1);
        $this->assertInstanceOf(ResponseInterface::class, $result2);
    }

    public function testServerErrorWithEmptyException(): void
    {
        // Arrange
        $request = $this->createRequest();
        $exception = new \Exception(); // Exception without message
        $displayErrorDetails = false;

        $expectedResponse = $this->responseFactory->createResponse(500);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                $this->callback(function ($response) {
                    return $response->getStatusCode() === 500;
                }),
                '500.phtml',
                $this->callback(function ($params) {
                    return isset($params['router']);
                })
            )
            ->willReturn($expectedResponse);

        // Act
        $result = $this->controller->serverError($request, $exception, $displayErrorDetails);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}