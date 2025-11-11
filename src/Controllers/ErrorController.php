<?php

namespace Hexlet\Code\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Views\PhpRenderer;
use Slim\Interfaces\RouteParserInterface;
use Throwable;

readonly class ErrorController
{
    public function __construct(
        private PhpRenderer $renderer,
        private RouteParserInterface $router,
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    public function notFound(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails
    ): ResponseInterface {
        $params = [
            'content_template' => 'pages/errors/404.phtml',
            'router' => $this->router
        ];
        return $this->renderer->render(
            $this->responseFactory->createResponse()->withStatus(404),
            'layouts/app.phtml',
            $params
        );
    }

    public function serverError(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails
    ): ResponseInterface {
        $params = [
            'content_template' => 'pages/errors/500.phtml',
            'router' => $this->router
        ];
        return $this->renderer->render(
            $this->responseFactory->createResponse()->withStatus(500),
            'layouts/app.phtml',
            $params
        );
    }
}
