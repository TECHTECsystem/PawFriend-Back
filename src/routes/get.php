<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Illuminate\Database\Capsule\Manager as DB;

return function (App $app) {
    require_once __DIR__ . '/../db.php'; // Solo carga Eloquent

    // GET clientes
    $app->get('/clientes', function (Request $request, Response $response) {
        $clientes = DB::table('clientes')->get();
        $response->getBody()->write($clientes->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    // GET mascotas
    $app->get('/mascotas', function (Request $request, Response $response) {
        $mascotas = DB::table('mascotas')->get();
        $response->getBody()->write($mascotas->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    // GET productos
    $app->get('/productos', function (Request $request, Response $response) {
        $productos = DB::table('productos')->get();
        $response->getBody()->write($productos->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    // GET servicios
    $app->get('/servicios', function (Request $request, Response $response) {
        $servicios = DB::table('servicios')->get();
        $response->getBody()->write($servicios->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });
};
