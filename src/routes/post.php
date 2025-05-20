<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Illuminate\Database\Capsule\Manager as DB;

return function (App $app) {
    require_once __DIR__ . '/../db.php';

    $app->post('/login', function (Request $request, Response $response) {
        $datos = json_decode($request->getBody()->getContents(), true);

        $username = $datos['username'] ?? '';
        $password = $datos['password'] ?? '';

        if ($username === '' || $password === '') {
            $response->getBody()->write(json_encode(['error' => 'Campos incompletos']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // 🔐 CORREGIDO: columna es 'username', no 'usuario'
        $usuario = DB::table('usuarios')->where('username', $username)->first();

        if (!$usuario || $usuario->password !== $password) {
            $response->getBody()->write(json_encode(['error' => 'Credenciales incorrectas']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Actualiza el último acceso
        DB::table('usuarios')->where('id', $usuario->id)->update([
            'ultimo_acceso' => date('Y-m-d H:i:s')
        ]);

        $respuesta = [
            'id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'rol' => $usuario->rol,
            'activo' => $usuario->activo
        ];

        $response->getBody()->write(json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json');
    });
};
