<?php

ini_set('display_errors', 'Off'); // No imprime errores en la salida
ini_set('log_errors', 'On');     // Los registra
ini_set('error_log', __DIR__ . '/php_error.log'); // Ruta del log (asegúrese que PHP pueda escribir aquí)
error_reporting(E_ALL); 
// api-ruta.php - Backend para guardar rutas optimizadas

// 1. --- CONFIGURACIÓN INICIAL ---

// Establecer que la respuesta será en formato JSON
header('Content-Type: application/json');

// Incluir las clases necesarias
require_once './GestorRutas.php';
require_once './RutaOptimizada.php';

// Clave de API de Google Maps
define('CLAVE_API_GOOGLE_MAPS', 'aca_va_la clave');

// Solo permitir peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Solo se permiten peticiones POST']);
    exit;
}

// 2. --- OBTENER DATOS DEL FRONTEND ---

// Leer los datos JSON enviados
$datosJson = file_get_contents('php://input');
$datos = json_decode($datosJson, true);

// Validar datos obligatorios
if (!isset($datos['origin'], $datos['destination'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan el origen o el destino']);
    exit;
}

$origen = $datos['origin'] ?? ''; 
$destino = $datos['destination'] ?? ''; 

// 3. --- PROCESAR DATOS DE LA RUTA ---

try {
    // Crear instancia del gestor de rutas
    $gestor_rutas = new GestorRutas();
    
    // Preparar puntos de recolección
    $puntos_recoleccion = [];
    if (!empty($datos['waypoints'])) {
        foreach ($datos['waypoints'] as $waypoint) {
            $puntos_recoleccion[] = [
                'direccion' => $waypoint['location'] ?? '',
                'estimado_kg' => $waypoint['estimado_kg'] ?? 25, // Peso por defecto si no se especifica
                'tipo_residuo' => $waypoint['tipo_residuo'] ?? 'general',
                'prioridad' => $waypoint['prioridad'] ?? 'normal'
            ];
        }
    }
    
    // Crear fecha de salida
    $hora_salida = new DateTime($datos['hora_salida'] ?? '08:00');
    
    // Obtener conductor y camión de los datos enviados o usar valores por defecto
    $conductor = $datos['conductor'] ?? 'Conductor Sin Asignar';
    $camion = $datos['camion'] ?? 'CAM-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    // Crear la ruta usando el gestor
    $ruta_id = $gestor_rutas->crearRuta(
        $puntos_recoleccion,
        $hora_salida,
        $origen,
        $destino,
        $conductor,
        $camion
    );
    
    // Obtener la ruta creada para mostrar detalles
    $ruta_creada = $gestor_rutas->obtenerRuta($ruta_id);
    
    // Preparar datos de respuesta
    $respuesta = [
        'id' => $ruta_id,
        'mensaje' => 'Ruta guardada exitosamente en datos_rutas.json',
        'datos_guardados' => [
            'origen' => $origen,
            'destino' => $destino,
            'puntos_parada' => $datos['waypoints'] ?? [],
            'hora_salida' => $datos['hora_salida'] ?? null,
            'resumen' => $datos['resumen'] ?? null,
            'fecha_guardado' => $datos['fecha_guardado'] ?? date('Y-m-d H:i:s'),
            'conductor' => $ruta_creada['conductor'] ?? $conductor,
            'camion' => $ruta_creada['camion'] ?? $camion,
            'estado' => $ruta_creada['estado'] ?? 'pendiente'
        ],
        'estadisticas' => [
            'total_puntos_parada' => count($datos['waypoints'] ?? []),
            'marca_tiempo' => time(),
            'archivo_datos' => 'datos_rutas.json',
            'ruta_optimizada' => $ruta_creada['ruta_optimizada'] ?? null
        ]
    ];

    http_response_code(200);
    echo json_encode($respuesta);
    exit;

} catch (Exception $e) {
    // Enviar error
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

?>