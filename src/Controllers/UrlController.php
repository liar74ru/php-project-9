<?php

namespace Hexlet\Code\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hexlet\Code\Services\UrlValidator;
use Hexlet\Code\Services\PageParser;
use Hexlet\Code\Services\HttpClient;
use Hexlet\Code\Services\UrlCheckService;
use Hexlet\Code\Models\Url;
use Hexlet\Code\Models\UrlCheck;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use Slim\Routing\RouteParser;
use GuzzleHttp\Client;

class UrlController
{
    public function __construct(
        private Url $urlModel,
        private UrlCheck $urlCheckModel,
        private PhpRenderer $renderer,
        private Messages $flash,
        private RouteParser $router
    ) {
    }

    public function home(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = [
            'urlValue' => '',
            'errors' => [],
            'router' => $this->router,
            'flash' => $this->flash->getMessages(),
            'choice' => 'home'
        ];
        return $this->renderer->render($response, "/index.phtml", $params);
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
            'flash' => $this->flash->getMessages(),
            'choice' => 'urls'
        ];

        return $this->renderer->render($response, "urls.phtml", $params);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $urlId = (int) $args['id'];
        $urlData = $this->urlModel->find($urlId);

        if (!$urlData) {
            return $this->renderer->render($response->withStatus(404), '404.phtml', ['router' => $this->router]);
        }

        // Получаем проверки для этого URL
        $checks = $this->urlCheckModel->findByUrlId($urlId);

        $params = [
            'urlData' => $urlData,
            'checks' => $checks,
            'flash' => $this->flash->getMessages(),
            'router' => $this->router,
            'choice' => 'urls'
        ];

        return $this->renderer->render($response, 'url.phtml', $params);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $url = $data['url']['name'];

        $validator = new UrlValidator();
        $normalized = $validator->validate($url);

        if (!empty($normalized['errorMessage'])) {
            $templateData = [
                'urlValue' => $url,
                'showValidation' => true,
                'errors' => $normalized,
                'router' => $this->router,
                'flash' => $this->flash->getMessages(),
                'choice' => 'home'
            ];

            return $this->renderer->render($response->withStatus(422), 'index.phtml', $templateData);
        }

        $existingUrl = $this->urlModel->findByName($normalized['url']);

        if ($existingUrl) {
            $urlId = $existingUrl['id'];
            $this->flash->addMessage('info', 'Страница уже существует');
            $location = $this->router->urlFor('urls.show', ['id' => $urlId]);
        } else {
            $urlId = $this->urlModel->save($normalized['url']);
            $this->flash->addMessage('success', 'Страница успешно добавлена');
            $location = "/urls/{$urlId}";
        }

        return $response
            ->withHeader('Location', $location)
            ->withStatus(302);
    }

    public function createChecks(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $urlId = (int) $args['id'];
        $urlData = $this->urlModel->find($urlId);

        if (!$urlData) {
            return $this->renderer->render($response->withStatus(404), '404.phtml', ['router' => $this->router]);
        }

        $urlCheckService = new UrlCheckService(
            $this->urlCheckModel,
            new HttpClient(),
            new PageParser()
        );

        $result = $urlCheckService->performCheck($urlId, $urlData['name']);

        if ($result['success']) {
            $this->flash->addMessage('success', 'Страница успешно проверена');
        } elseif ($result['check_data']['status_code'] !== 0) {
            $this->flash->addMessage(
                'warning',
                "Проверка была выполнена успешно, но сервер ответил с ошибкой"
            );
        } else {
            $this->flash->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключится');
        }

        return $response
        ->withHeader('Location', $this->router->urlFor('urls.show', ['id' => (string)$urlId]))
        ->withStatus(302);
    }
}
