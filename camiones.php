// api/camiones.php
header('Content-Type: application/json');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $gestor = new GestorCamiones();

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // GET /api/camiones.php?id=123
                $camion = $gestor->buscarCamionPorId($_GET['id']);
                echo json_encode($camion ? $camion->toArray() : null);
            } else {
                // GET /api/camiones.php
                echo json_encode($gestor->obtenerCamiones());
            }
            break;

        case 'POST':
            // POST /api/camiones.php
            $data = json_decode(file_get_contents('php://input'), true);
            $camion = new Camion(null, $data['placa'], $data['capacidad'], $data['conductor']);
            $gestor->agregarCamion($camion);
            echo json_encode(['success' => true, 'id' => $camion->getId()]);
            break;

        case 'PUT':
            // PUT /api/camiones.php?id=123
            $data = json_decode(file_get_contents('php://input'), true);
            $gestor->actualizarCamion($_GET['id'], $data);
            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            // DELETE /api/camiones.php?id=123
            $gestor->eliminarCamion($_GET['id']);
            echo json_encode(['success' => true]);
            break;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}