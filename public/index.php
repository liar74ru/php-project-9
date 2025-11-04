<?php
// Разрешить встроенному PHP-серверу отдавать статические файлы напрямую
if (PHP_SAPI === 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $url;
    if ($file !== false && is_file($file)) {
        return false; // позволить встроенному серверу обслужить файл
    }
}

require __DIR__ . '/../vendor/autoload.php';

use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use DI\Container;
use Hexlet\Code\Database\Connection;
use Hexlet\Code\Models\Url;
use Hexlet\Code\Models\UrlCheck;
use Hexlet\Code\Controllers\UrlController;
use Hexlet\Code\Services\UrlValidator;
use Hexlet\Code\Controllers\ErrorController;

session_start();

// Создание контейнера и приложения
$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Сервисы
$container->set('renderer', fn() => new PhpRenderer(__DIR__ . '/../templates'));
$container->set('flash', fn() => new Messages());
$container->set('db', fn() => Connection::get());
$container->set('router', fn () => $app->getRouteCollector()->getRouteParser());
$container->set(ResponseFactoryInterface::class, fn() => $app->getResponseFactory());

// Модели
$container->set(Url::class, fn($container) => new Url($container->get('db')));
$container->set(UrlCheck::class, fn($container) => new UrlCheck($container->get('db')));

// Контроллер UrlController с внедрением зависимостей
$container->set(UrlController::class, function ($container) {
    return new UrlController(
        $container->get(Url::class),
        $container->get(UrlCheck::class),
        $container->get('renderer'),
        $container->get('flash'),
        $container->get('router')
    );
});
// Контроллер ErrorController с внедрением зависимостей
$container->set(ErrorController::class, function ($container) {
    return new ErrorController(
        $container->get('renderer'),
        $container->get('router'),
        $container->get(ResponseFactoryInterface::class)
    );
});

// Маршруты
$app->get('/', [UrlController::class, 'home'])->setName('home');
$app->post('/urls', [UrlController::class, 'store'])->setName('urls.store');
$app->get('/urls', [UrlController::class, 'index'])->setName('urls.index');
$app->get('/urls/{id}', [UrlController::class, 'show'])->setName('urls.show');
$app->post('/urls/{id}/checks', [UrlController::class, 'createChecks'])->setName('urls.checks.create');

// Error middleware добавляется ПОСЛЕ всех маршрутов
$errorMiddleware = $app->addErrorMiddleware(
    displayErrorDetails: true,      // для разработки
    logErrors: true,               // логировать ошибки
    logErrorDetails: true          // детали в логах
);

$errorMiddleware->setErrorHandler(HttpNotFoundException::class, [ErrorController::class, 'notFound']);
$errorMiddleware->setErrorHandler(\Throwable::class, [ErrorController::class, 'serverError']);

$app->run();
