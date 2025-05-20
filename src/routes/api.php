<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/prueba', function (Request $request, Response $response) {
    $datos = Capsule::table('usuarios')->get(); // Suponiendo tabla `usuarios`
    $response->getBody()->write($datos->toJson());
    return $response->withHeader('Content-Type', 'application/json');
});
