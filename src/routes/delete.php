<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Illuminate\Database\Capsule\Manager as DB;

return function (App $app) {
    require_once __DIR__ . '/../db.php';

    // DELETE /productos/{id}
    $app->delete('/productos/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];

        DB::table('productos')->where('id', $id)->delete();

        $response->getBody()->write(json_encode(['mensaje' => 'Producto eliminado']));
        return $response->withHeader('Content-Type', 'application/json');
    });


    // DELETE servicios/{id}
        $app->delete('/servicios/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];

        $eliminado = DB::table('servicios')->where('id', $id)->delete();

        if ($eliminado) {
            $response->getBody()->write(json_encode(['mensaje' => 'Servicio eliminado']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')
                            ->write(json_encode(['error' => 'Servicio no encontrado']));
        }
    });

    // proveedores/{id}
    $app->delete('/proveedores/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    DB::table('proveedores')->where('id', $id)->delete();
    $response->getBody()->write(json_encode(['mensaje' => 'Proveedor eliminado']));
    return $response->withHeader('Content-Type', 'application/json');
    });

    // delete usuarios/{id}
    $app->delete('/usuarios/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    DB::table('usuarios')->where('id', $id)->delete();
    $response->getBody()->write(json_encode(['mensaje' => 'Usuario eliminado']));
    return $response->withHeader('Content-Type', 'application/json');
});

};
