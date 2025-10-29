<?php

namespace Hexlet\Code\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Hexlet\Code\Services\UrlValidator;
use Hexlet\Code\Models\Url;

class UrlController
{
    private $urlModel;
    private $renderer;
    private $flash;
    private $validator;
    private $router;

    public function __construct($urlModel, $renderer, $flash, UrlValidator $validator = null, $router = null)
    {
        $this->urlModel = $urlModel;
        $this->renderer = $renderer;
        $this->flash = $flash;
        $this->validator = $validator ?? new UrlValidator();
        $this->router = $router;
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $urls = $this->urlModel->findAll();

        $params = [
            'urls' => $urls,
            'router' => $this->router,
            'flash' => $this->flash->getMessages()
        ];

        return $this->renderer->render($response, "urls.phtml", $params);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $urlId = (int) $args['id'];
        $urlData = $this->urlModel->find($urlId);

        if (!$urlData) {
            return $this->renderer->render($response->withStatus(404), '404.phtml');
        }

        $params = [
            'urlData' => $urlData,
            'flash' => $this->flash->getMessages(),
            'router' => $this->router
        ];

        return $this->renderer->render($response, 'url.phtml', $params);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $url = $data['url']['name'] ?? '';

        // Используем валидатор
        $errors = $this->validator->validate($url);

        if (!empty($errors)) {
            $templateData = [
                'urlValue' => $url,
                'showValidation' => true,
                'errors' => $errors,
                'router' => $this->router
            ];

            return $this->renderer->render($response->withStatus(422), 'index.phtml', $templateData);
        }

        // Нормализуем URL перед сохранением
        $normalizedUrl = $this->validator->normalizeUrl($url);

        // Используем модель для работы с базой
        $existingUrl = $this->urlModel->findByName($normalizedUrl);

        if ($existingUrl) {
            $urlId = $existingUrl['id'];
            $this->flash->addMessage('success', 'Страница уже существует');
        } else {
            $urlId = $this->urlModel->save($normalizedUrl);
            $this->flash->addMessage('success', 'Страница успешно добавлена');
        }

        // ИСПОЛЬЗУЕМ ИМЕНОВАННЫЙ МАРШРУТ
        if ($this->router) {
            return $response
                ->withHeader('Location', $this->router->urlFor('urls.show', ['id' => $urlId]))
                ->withStatus(302);
        } else {
            // Fallback
            return $response
                ->withHeader('Location', "/urls/{$urlId}")
                ->withStatus(302);
        }
    }
}
