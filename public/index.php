<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();

// Permitir preflight para CORS
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

// Middleware CORS
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Cargar las rutas
$routeGet = require __DIR__ . '/../src/routes/get.php';
$routePost = require __DIR__ . '/../src/routes/post.php';
$routePut = require __DIR__ . '/../src/routes/update.php';
$routeDelete = require __DIR__ . '/../src/routes/delete.php';

$routeGet($app);
$routePost($app);
$routePut($app);
$routeDelete($app);

$app->run();
