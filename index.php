<?php
session_start();

$routes = [
    '/' => 'login.html',
    '/login' => 'login.html',
    '/login.html' => 'login.html',
    '/login.php' => 'login.php',
    '/dashboard' => 'dashboard.php',
    '/logout' => 'logout.php',
    '/api/camiones.php' => 'api/camiones.php',
    '/api/rutas.php' => 'api/rutas.php',
    '/api/mantenimientos.php' => 'api/mantenimientos.php'
];

// Normaliza la URI (quita barra final si existe)
$uri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// Soporte para rutas tipo /api/camiones/123
if (preg_match('#^/api/camiones/(\d+)$#', $uri, $matches)) {
    $_GET['id'] = $matches[1];
    require 'api/camiones.php';
    exit;
}

if (isset($routes[$uri])) {
    require $routes[$uri];
} else {
    http_response_code(404);
    echo "Página no encontrada";
}