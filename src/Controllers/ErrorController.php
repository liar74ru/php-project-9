<?php

namespace Hexlet\Code\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class ErrorController
{
    public function __construct(
        private \Slim\Views\PhpRenderer $renderer,
        private \Slim\Interfaces\RouteParserInterface $router,
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    public function notFound(
        ServerRequestInterface $request,
        \Throwable $exception,  // ← Второй параметр - исключение
        bool $displayErrorDetails  // ← Третий параметр - флаг деталей ошибки
    ): ResponseInterface {
        return $this->renderer->render(
            $this->responseFactory->createResponse()->withStatus(404), // ← Создаем новый response
            '404.phtml',
            ['router' => $this->router]
        );
    }

    public function serverError(
        ServerRequestInterface $request,
        \Throwable $exception,  // ← Второй параметр - исключение
        bool $displayErrorDetails  // ← Третий параметр - флаг деталей ошибки
    ): ResponseInterface {
        return $this->renderer->render(
            $this->responseFactory->createResponse()->withStatus(500), // ← Создаем новый response
            '500.phtml',
            [
                'router' => $this->router,
            ]
        );
    }
}
