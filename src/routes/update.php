<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Illuminate\Database\Capsule\Manager as DB;

return function (App $app) {
    require_once __DIR__ . '/../db.php';

    // PUT /productos/{id}
    $app->put('/productos/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];
        $datos = json_decode($request->getBody()->getContents(), true);

        DB::table('productos')->where('id', $id)->update([
            'nombre' => $datos['nombre'],
            'sku' => $datos['sku'],
            'categoria' => $datos['categoria'],
            'descripcion' => $datos['descripcion'],
            'precio' => $datos['precio'],
            'stock' => $datos['stock']
        ]);

        $response->getBody()->write(json_encode(['mensaje' => 'Producto actualizado']));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->put('/clientes/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $datos = json_decode($request->getBody()->getContents(), true);

    // Validar campos básicos
    if (!$id || !$datos['nombre'] || !$datos['telefono']) {
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['error' => 'Datos incompletos']));
    }

    // Actualizar cliente
    DB::table('clientes')->where('id', $id)->update([
        'nombre'   => $datos['nombre'],
        'telefono' => $datos['telefono'],
        'correo'   => $datos['correo'] ?? null
    ]);

    // Verificar si existe mascota asociada
    $mascotaExiste = DB::table('mascotas')->where('cliente_id', $id)->exists();

    if ($mascotaExiste) {
        // Actualizar mascota
        DB::table('mascotas')->where('cliente_id', $id)->update([
            'nombre'       => $datos['mascota']['nombre'] ?? '',
            'raza'         => $datos['mascota']['raza'] ?? '',
            'edad'         => $datos['mascota']['edad'] ?? 0,
            'unidad_edad'  => $datos['mascota']['unidad'] ?? ''
        ]);
    } else {
        // Insertar mascota si no existía
        DB::table('mascotas')->insert([
            'cliente_id'   => $id,
            'nombre'       => $datos['mascota']['nombre'] ?? '',
            'raza'         => $datos['mascota']['raza'] ?? '',
            'edad'         => $datos['mascota']['edad'] ?? 0,
            'unidad_edad'  => $datos['mascota']['unidad'] ?? ''
        ]);
    }

    $response->getBody()->write(json_encode(['mensaje' => 'Cliente actualizado correctamente']));
    return $response->withHeader('Content-Type', 'application/json');
    });

    $app->put('/servicios/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];
        $data = json_decode($request->getBody()->getContents(), true);

        $actualizado = DB::table('servicios')->where('id', $id)->update([
            'clave' => $data['clave'],
            'nombre' => $data['nombre'],
            'categoria' => $data['categoria'],
            'precio' => $data['precio'],
            'descripcion' => $data['descripcion']
        ]);

        if ($actualizado) {
            $response->getBody()->write(json_encode(['mensaje' => 'Servicio actualizado']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')
                            ->write(json_encode(['error' => 'Servicio no encontrado o sin cambios']));
        }
    });

$app->put('/proveedores/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $datos = json_decode($request->getBody()->getContents(), true);

    DB::table('proveedores')->where('id', $id)->update([
        'razon_social'              => $datos['razonSocial'] ?? '',
        'rfc'                       => $datos['rfc'] ?? '',
        'domicilio_fiscal'         => $datos['domicilioFiscal'] ?? '',
        'domicilio_entrega'        => $datos['domicilioEntrega'] ?? '',
        'contacto_nombre'          => $datos['contacto']['nombre'] ?? '',
        'contacto_email'           => $datos['contacto']['email'] ?? '',
        'contacto_telefono'        => $datos['contacto']['telefono'] ?? '',
        'condiciones_pago_dias'    => $datos['condicionesPago']['diasNetos'] ?? 0,
        'condiciones_pago_descuento' => $datos['condicionesPago']['descuento'] ?? 0,
        'moneda'                   => $datos['moneda'] ?? '',
        'tipo_cambio'              => $datos['tipoCambio'] ?? 1,
        'cuenta_contable'          => $datos['cuentaContable'] ?? '',
        'centro_costo'             => $datos['centroCosto'] ?? '',
        'transportista'            => $datos['transportista'] ?? '',
        'incoterm'                 => $datos['incoterm'] ?? ''
    ]);

    $response->getBody()->write(json_encode(['mensaje' => 'Proveedor actualizado']));
    return $response->withHeader('Content-Type', 'application/json');
});

// usuarios
$app->put('/usuarios/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $datos = json_decode($request->getBody()->getContents(), true);

    $usuarioActualizado = [
        'nombre' => $datos['nombre'] ?? '',
        'email' => $datos['email'] ?? '',
        'username' => $datos['username'] ?? '',
        'password' => $datos['password'] ?? '',
        'rol' => $datos['rol'] ?? 'empleado',
        'activo' => $datos['activo'] ? 1 : 0,
        'ultimo_acceso' => $datos['ultimoAcceso'] ?? date('Y-m-d H:i:s')
    ];

    DB::table('usuarios')->where('id', $id)->update($usuarioActualizado);

    $response->getBody()->write(json_encode(['mensaje' => 'Usuario actualizado']));
    return $response->withHeader('Content-Type', 'application/json');
});


};
