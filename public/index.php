<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use DI\Container;
use Hexlet\Code\Database\Connection;
use Hexlet\Code\Models\Url;
use Hexlet\Code\Controllers\UrlController;
use Hexlet\Code\Services\UrlValidator;

session_start();

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Сервисы
$container->set('renderer', fn() => new PhpRenderer(__DIR__ . '/../templates'));
$container->set('flash', fn() => new Messages());
$container->set('db', fn() => Connection::get());
$container->set('router', fn () => $app->getRouteCollector()->getRouteParser());

// Domain services
$container->set(Url::class, fn($container) => new Url($container->get('db')));
$container->set(UrlValidator::class, fn() => new UrlValidator());

// Обновленный контроллер UrlController с внедрением зависимостей
$container->set(UrlController::class, function ($container) {
    return new UrlController(
        $container->get(Url::class),
        $container->get('renderer'),
        $container->get('flash'),
        $container->get(UrlValidator::class),
        $container->get('router')
    );
});

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$errorMiddleware->setErrorHandler(
    HttpNotFoundException::class,
    function (ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails) use ($app) {
        $response = $app->getResponseFactory()->createResponse();
        return $app->getContainer()->get('renderer')->render($response->withStatus(404), '404.phtml');
    }
);

// Маршруты
$app->get('/', function ($request, $response) {
    $params = [
        'urlValue' => '',
        'errors' => [],
        'router' => $this->get('router'),
        'flash' => $this->get('flash')->getMessages()
    ];
    return $this->get('renderer')->render($response, "/index.phtml", $params);
})->setName('home');

$app->post('/urls', [UrlController::class, 'store'])->setName('urls.store');
$app->get('/urls', [UrlController::class, 'index'])->setName('urls.index');
$app->get('/urls/{id}', [UrlController::class, 'show'])->setName('urls.show');

$app->get('/debug-db', function ($request, $response) {
    try {
        $pdo = Hexlet\Code\Database\Connection::get();
        $stmt = $pdo->query('SELECT version()');
        $version = $stmt->fetchColumn();
        
        return $response->getBody()->write("Database connected! PostgreSQL version: " . $version);
    } catch (Exception $e) {
        return $response->getBody()->write("Database connection failed: " . $e->getMessage());
    }
});

$app->run();