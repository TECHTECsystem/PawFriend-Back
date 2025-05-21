<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Illuminate\Database\Capsule\Manager as DB;

return function (App $app) {
    require_once __DIR__ . '/../db.php'; // Solo carga Eloquent

// GET clientes con mascota
    $app->get('/clientes', function (Request $request, Response $response) {
    $clientes = DB::table('clientes')
        ->leftJoin('mascotas', 'clientes.id', '=', 'mascotas.cliente_id')
        ->select(
            'clientes.id as id',
            'clientes.nombre as nombre',
            'clientes.telefono',
            'clientes.correo',
            'mascotas.nombre as mascota_nombre',
            'mascotas.edad as mascota_edad',
            'mascotas.unidad_edad as mascota_unidad',
            'mascotas.raza as mascota_raza'
        )
        ->get();

    $response->getBody()->write(json_encode($clientes));
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
    $productos = DB::table('productos')->select('id', 'sku', 'nombre', 'categoria', 'descripcion', 'precio', 'stock')->get();
    $response->getBody()->write(json_encode($productos));
    return $response->withHeader('Content-Type', 'application/json');
    });


    // GET servicios
    $app->get('/servicios', function (Request $request, Response $response) {
    $servicios = DB::table('servicios')->get();
    $response->getBody()->write(json_encode($servicios));
    return $response->withHeader('Content-Type', 'application/json');
    });

    // GET proveedores
$app->get('/proveedores', function (Request $request, Response $response) {
    $proveedores = DB::table('proveedores')->get();

    $transformados = $proveedores->map(function ($p) {
        return [
            'id' => $p->id,
            'razon_social' => $p->razon_social ?? '',
            'rfc' => $p->rfc ?? '',
            'domicilio_fiscal' => $p->domicilio_fiscal ?? '',
            'domicilio_entrega' => $p->domicilio_entrega ?? '',
            'contacto_nombre' => $p->contacto_nombre ?? '',
            'contacto_email' => $p->contacto_email ?? '',
            'contacto_telefono' => $p->contacto_telefono ?? '',
            'condiciones_pago_dias' => $p->condiciones_pago_dias ?? 0,
            'condiciones_pago_descuento' => $p->condiciones_pago_descuento ?? 0,
            'moneda' => $p->moneda ?? '',
            'tipo_cambio' => $p->tipo_cambio ?? 1,
            'cuenta_contable' => $p->cuenta_contable ?? '',
            'centro_costo' => $p->centro_costo ?? '',
            'transportista' => $p->transportista ?? '',
            'incoterm' => $p->incoterm ?? ''
        ];
    });

    $response->getBody()->write(json_encode($transformados));
    return $response->withHeader('Content-Type', 'application/json');
});

// GET usuarios
$app->get('/usuarios', function (Request $request, Response $response) {
    $usuarios = DB::table('usuarios')->get();

    $transformados = $usuarios->map(function ($u) {
        return [
            'id' => $u->id,
            'nombre' => $u->nombre,
            'email' => $u->email,
            'username' => $u->username,
            'password' => $u->password,
            'rol' => $u->rol,
            'activo' => (bool) $u->activo,
            'ultimoAcceso' => $u->ultimo_acceso
        ];
    });

    $response->getBody()->write(json_encode($transformados));
    return $response->withHeader('Content-Type', 'application/json');
});

// catalgo completo
$app->get('/catalogo', function (Request $request, Response $response) {
    $productos = DB::table('productos')->select([
        "id",
        DB::raw("sku as codigo"),
        "nombre",
        DB::raw("'Producto' as tipo"),
        "precio",
        "stock"
    ]);

    $servicios = DB::table('servicios')->select([
        "id",
        DB::raw("clave as codigo"),
        "nombre",
        DB::raw("'Servicio' as tipo"),
        "precio",
        DB::raw("null as stock")
    ]);

    $catalogo = $productos->unionAll($servicios)->get();

    $response->getBody()->write(json_encode($catalogo));
    return $response->withHeader('Content-Type', 'application/json');
});

    // KPI
    $app->get('/dashboard/kpis', function (Request $request, Response $response) {
    try {
        // Total de ventas del día
        $ventasDia = DB::table('ventas')
            ->whereDate('fecha', DB::raw('CURDATE()'))
            ->sum('total');

        // Total de ventas del mes actual
        $ventasMes = DB::table('ventas')
            ->whereMonth('fecha', DB::raw('MONTH(CURDATE())'))
            ->whereYear('fecha', DB::raw('YEAR(CURDATE())'))
            ->sum('total');

        // Total de clientes registrados
        $clientes = DB::table('clientes')->count();

        // Total de productos con stock mayor a 0
        $productosActivos = DB::table('productos')->where('stock', '>', 0)->count();

        // Respuesta JSON
        $response->getBody()->write(json_encode([
            'ventasDia' => $ventasDia,
            'ventasMes' => $ventasMes,
            'clientes' => $clientes,
            'productosActivos' => $productosActivos
        ]));

        return $response->withHeader('Content-Type', 'application/json');

    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'error' => 'Error al obtener KPIs',
            'detalle' => $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
    });


    // Citas de hoy
    $app->get('/dashboard/citas-hoy', function (Request $request, Response $response) {
        $citas = DB::table('citas')
            ->join('mascotas', 'citas.mascota_id', '=', 'mascotas.id')
            ->join('servicios', 'citas.servicio_id', '=', 'servicios.id')
            ->whereDate('citas.fecha', DB::raw('CURDATE()'))
            ->select([
                DB::raw('DATE_FORMAT(citas.hora, "%H:%i") as hora'),
                'mascotas.nombre as nombre',
                'servicios.nombre as servicio'
            ])
            ->orderBy('citas.hora')
            ->get();

        $response->getBody()->write($citas->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Productos con stock bajo
    $app->get('/dashboard/stock-bajo', function (Request $request, Response $response) {
        $productos = DB::table('productos')
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->select(['nombre', 'stock', 'stock_minimo as minimo'])
            ->orderBy('stock', 'asc')
            ->get();

        $response->getBody()->write($productos->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });



};
