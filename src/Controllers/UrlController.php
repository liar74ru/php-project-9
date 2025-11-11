<?php

namespace Hexlet\Code\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hexlet\Code\Services\UrlValidator;
use Hexlet\Code\Services\PageParser;
use Hexlet\Code\Services\HttpClient;
use Hexlet\Code\Services\UrlCheckService;
use Hexlet\Code\Services\UrlService;
use Hexlet\Code\Models\Url;
use Hexlet\Code\Models\UrlCheck;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use Slim\Routing\RouteParser;
use Slim\Exception\HttpNotFoundException;

readonly class UrlController
{
    public function __construct(
        private Url $urlModel,
        private UrlCheck $urlCheckModel,
        private UrlService $urlService,
        private PhpRenderer $renderer,
        private Messages $flash,
        private RouteParser $router
    ) {
    }

    public function home(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {

        $params = [
            'content_template' => 'pages/home/index.phtml',
            'urlValue' => '',
            'errors' => [],
            'router' => $this->router,
            'flash' => $this->flash->getMessages(),
            'choice' => 'home'
        ];
        return $this->renderer->render($response, "layouts/app.phtml", $params);
    }

    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {

        $urls = $this->urlService->findAllWithLastChecks();

        $params = [
            'content_template' => 'pages/urls/index.phtml',
            'urls' => $urls,
            'router' => $this->router,
            'flash' => $this->flash->getMessages(),
            'choice' => 'urls'
        ];

        return $this->renderer->render($response, "layouts/app.phtml", $params);
    }

    public function show(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

        $urlId = (int) $args['id'];
        $urlData = $this->urlModel->findByIdUrl($urlId);

        if (!$urlData) {
            throw new HttpNotFoundException($request);
        }

        $checks = $this->urlCheckModel->findByUrlId($urlId);

        $params = [
            'content_template' => 'pages/urls/show.phtml',
            'urlData' => $urlData,
            'checks' => $checks,
            'flash' => $this->flash->getMessages(),
            'router' => $this->router,
            'choice' => 'urls'
        ];

        return $this->renderer->render($response, "layouts/app.phtml", $params);
    }

    public function store(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {

        $data = (array)($request->getParsedBody() ?? []);
        $originalUrl = $data['url']['name'] ?? '';

        $validator = new UrlValidator();
        $result = $validator->validateFormData($data);

        if (!empty($result['errorMessage'])) {
            $templateData = [
                'content_template' => 'pages/home/index.phtml',
                'urlValue' => $originalUrl,
                'showValidation' => true,
                'errors' => $result,
                'router' => $this->router,
                'flash' => $this->flash->getMessages(),
                'choice' => 'home'
            ];

            return $this->renderer->render($response->withStatus(422), "layouts/app.phtml", $templateData);
        }

        $existingUrl = $this->urlModel->findByNameUrl($result['url']);

        if ($existingUrl) {
            $urlId = $existingUrl['id'];
            $this->flash->addMessage('info', 'Страница уже существует');
        } else {
            $urlId = $this->urlModel->saveNewUrl($result['url']);
            $this->flash->addMessage('success', 'Страница успешно добавлена');
        }
        return $response
            ->withHeader('Location', $this->router->urlFor('urls.show', ['id' => $urlId]))
            ->withStatus(302);
    }

    public function createChecks(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

        $urlId = (int) $args['id'];
        $urlData = $this->urlModel->findByIdUrl($urlId);

        if (!$urlData) {
            throw new HttpNotFoundException($request);
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
