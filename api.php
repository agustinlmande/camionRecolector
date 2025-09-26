<?php
// api.php
header('Content-Type: application/json');
require_once "Vehiculos.php";

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $gestor = new GestorVehiculos();

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $vehiculo = $gestor->buscarVehiculoPorId($_GET['id']);
                echo json_encode($vehiculo ? $vehiculo->toArray() : null);
            } else {
                echo json_encode($gestor->obtenerVehiculos());
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $vehiculo = Vehiculo::fromArray($data);
            $gestor->agregarVehiculo($vehiculo);
            echo json_encode(['success' => true, 'id' => $vehiculo->getId()]);
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            $gestor->actualizarVehiculo($_GET['id'], $data);
            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            $gestor->eliminarVehiculo($_GET['id']);
            echo json_encode(['success' => true]);
            break;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
