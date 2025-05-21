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

    $app->post('/proveedores', function (Request $request, Response $response) {
    $datos = json_decode($request->getBody()->getContents(), true);

    $nuevoProveedor = [
        'id'                         => $datos['id'] ?? null,
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
    ];

    DB::table('proveedores')->insert($nuevoProveedor);

    $response->getBody()->write(json_encode([
        'mensaje' => 'Proveedor registrado correctamente',
        'proveedor' => $nuevoProveedor
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// usuarios
$app->post('/usuarios', function (Request $request, Response $response) {
    $datos = json_decode($request->getBody()->getContents(), true);

    $nuevoUsuario = [
        'nombre' => $datos['nombre'] ?? '',
        'email' => $datos['email'] ?? '',
        'username' => $datos['username'] ?? '',
        'password' => $datos['password'] ?? '',
        'rol' => $datos['rol'] ?? 'empleado',
        'activo' => $datos['activo'] ? 1 : 0,
        'ultimo_acceso' => $datos['ultimoAcceso'] ?? date('Y-m-d H:i:s')
    ];

    DB::table('usuarios')->insert($nuevoUsuario);

    $response->getBody()->write(json_encode([
        'mensaje' => 'Usuario creado exitosamente',
        'usuario' => $nuevoUsuario
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// ventas
$app->post('/ventas', function (Request $request, Response $response) {
    error_reporting(0);
    ini_set('display_errors', '0');

    try {
        // 1. Leer JSON
        $json = $request->getBody()->getContents();
        $datos = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Error al decodificar JSON: ' . json_last_error_msg());
        }

        // 2. Validar estructura mínima
        if (!isset($datos['cliente'])) {
            throw new \Exception('El campo "cliente" es requerido');
        }

        if (!isset($datos['ticket']) || !is_array($datos['ticket']) || count($datos['ticket']) === 0) {
            throw new \Exception('El ticket debe contener al menos un item');
        }

        // 3. Validar items del ticket
        foreach ($datos['ticket'] as $index => $item) {
            if (!isset($item['id'])) {
                throw new \Exception('Item ' . ($index + 1) . ': Falta el campo "id"');
            }

            if (!isset($item['tipo']) || !in_array($item['tipo'], ['Producto', 'Servicio'])) {
                throw new \Exception('Item ' . ($index + 1) . ': Tipo inválido o faltante');
            }

            if (!isset($item['precio']) || !is_numeric($item['precio'])) {
                throw new \Exception('Item ' . ($index + 1) . ': Precio inválido o faltante');
            }

            if ($item['tipo'] === 'Producto' && (!isset($item['cantidad']) || !is_numeric($item['cantidad']))) {
                throw new \Exception('Item ' . ($index + 1) . ' (Producto): Cantidad inválida o faltante');
            }
        }

        DB::beginTransaction();

        // 4. Insertar cliente
        $clienteId = DB::table('clientes')->insertGetId([
            'nombre' => $datos['cliente'],
            'telefono' => $datos['clienteTelefono'] ?? null,
            'correo' => $datos['clienteEmail'] ?? null
        ]);

        // 5. Insertar mascota si aplica
        $mascotaId = null;
        if (isset($datos['mascota']) && !empty($datos['mascota']['nombre'])) {
            $mascota = $datos['mascota'];
            $mascotaId = DB::table('mascotas')->insertGetId([
                'nombre' => $mascota['nombre'],
                'especie' => $mascota['especie'] ?? '',
                'edad' => $mascota['edad'] ?? 0,
                'unidad_edad' => $mascota['unidad_edad'] ?? 'años',
                'raza' => $mascota['raza'] ?? '',
                'cliente_id' => $clienteId
            ]);
        }

        // 6. Determinar tipo de venta
        $tipos = array_column($datos['ticket'], 'tipo');
        $tipoVenta = (count(array_unique($tipos)) === 1) ? strtolower($tipos[0]) : 'mixto';

        // 7. Insertar venta
        $ventaId = DB::table('ventas')->insertGetId([
            'cliente_id' => $clienteId,
            'mascota_id' => $mascotaId,
            'usuario_id' => $datos['usuarioId'] ?? 1,
            'total' => $datos['total'],
            'tipo' => $tipoVenta,
            'observaciones' => $datos['observaciones'] ?? null,
            'metodo_pago' => $datos['metodoPago'] ?? 'efectivo'
        ]);

        // 8. Insertar productos y servicios
        foreach ($datos['ticket'] as $item) {
            if ($item['tipo'] === 'Producto') {
                DB::table('venta_productos')->insert([
                    'venta_id' => $ventaId,
                    'producto_id' => $item['id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                    'subtotal' => $item['precio'] * $item['cantidad']
                ]);

                DB::table('productos')->where('id', $item['id'])->decrement('stock', $item['cantidad']);
            }

            if ($item['tipo'] === 'Servicio') {
                DB::table('venta_servicios')->insert([
                    'venta_id' => $ventaId,
                    'servicio_id' => $item['id'],
                    'precio_unitario' => $item['precio']
                ]);
            }
        }

        DB::commit();

        $response->getBody()->write(json_encode([
            'success' => true,
            'ventaId' => $ventaId,
            'mensaje' => 'Venta registrada correctamente'
        ]));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (\Exception $e) {
        DB::rollBack();
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
});



};
