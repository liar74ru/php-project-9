<?php

namespace Hexlet\Code\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hexlet\Code\Services\UrlValidator;
use Hexlet\Code\Models\Url;
use Hexlet\Code\Models\UrlCheck;

class UrlController
{
    private $urlModel;
    private $urlCheckModel;
    private $renderer;
    private $flash;
    private $validator;
    private $router;

    public function __construct(
        $urlModel,
        $urlCheckModel,
        $renderer,
        $flash,
        ?UrlValidator $validator = null,
        $router = null
    ) {
        $this->urlModel = $urlModel;
        $this->urlCheckModel = $urlCheckModel;
        $this->renderer = $renderer;
        $this->flash = $flash;
        $this->validator = $validator ?? new UrlValidator();
        $this->router = $router;
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $urls = $this->urlModel->findAll();
        $result = [];

        foreach ($urls as $url) {
            $lastCheck = $this->urlCheckModel->findLastCheck($url['id']);

            $result[] = [
                'id' => $url['id'],
                'name' => $url['name'],
                'last_check_date' => $lastCheck['created_at'] ?? null,
                'last_status_code' => $lastCheck['status_code'] ?? null
            ];
        }

        $params = [
            'urls' => $result,
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

        // Получаем проверки для этого URL
        $checks = $this->urlCheckModel->findByUrlId($urlId);

        $params = [
            'urlData' => $urlData,
            'checks' => $checks,
            'flash' => $this->flash->getMessages(),
            'router' => $this->router
        ];

        return $this->renderer->render($response, 'url.phtml', $params);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $url = $data['url']['name'] ?? '';

        $errors = $this->validator->validate($url);

        if (!empty($errors)) {
            $templateData = [
                'urlValue' => $url,
                'showValidation' => true,
                'errors' => $errors,
                'router' => $this->router,
                'flash' => $this->flash->getMessages()
            ];

            return $this->renderer->render($response->withStatus(422), 'index.phtml', $templateData);
        }

        $normalizedUrl = $this->validator->normalizeUrl($url);
        $existingUrl = $this->urlModel->findByName($normalizedUrl);

        if ($existingUrl) {
            $urlId = $existingUrl['id'];
            $this->flash->addMessage('success', 'Страница уже существует');
        } else {
            $urlId = $this->urlModel->save($normalizedUrl);
            $this->flash->addMessage('success', 'Страница успешно добавлена');
        }

        if ($this->router) {
            return $response
                ->withHeader('Location', $this->router->urlFor('urls.show', ['id' => $urlId]))
                ->withStatus(302);
        } else {
            return $response
                ->withHeader('Location', "/urls/{$urlId}")
                ->withStatus(302);
        }
    }

    public function createChecks(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $urlId = (int) $args['id'];

        // Данные для проверки
        $checkData = [
            'status_code' => 200,
            'h1' => 'Заголовок страницы',
            'title' => 'Title страницы',
            'description' => 'Описание страницы'
        ];

        // Сохраняем проверку
        $this->urlCheckModel->save($urlId, $checkData);

        $this->flash->addMessage('success', 'Проверка успешно выполнена');

        return $response->withRedirect($this->router->urlFor('urls.show', ['id' => $urlId]));
    }
}
