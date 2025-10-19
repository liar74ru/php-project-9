<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;

//session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);

$app = AppFactory::create();
//$app->add(new MethodOverrideMiddleware());
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) use ($app) {
    $params = [];
    return $this->get('renderer')->render($response, "/home.phtml", $params);
})->setName('home');

$app->run();