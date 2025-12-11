<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/services/servicios.php';

// Verificar autenticación
if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos de entrada
$input = json_decode(file_get_contents('php://input'), true);

// Validar datos requeridos
if (!isset($input['id_alquiler']) || !is_numeric($input['id_alquiler'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de alquiler inválido']);
    exit;
}

if (!isset($input['precio_hora']) || !is_numeric($input['precio_hora']) || $input['precio_hora'] <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Precio por hora inválido']);
    exit;
}

$id_alquiler = (int)$input['id_alquiler'];
$precio_hora = (float)$input['precio_hora'];

try {
    $resultado = finalizarServicio($id_alquiler, $precio_hora);
    
    if ($resultado['success']) {
        // Registrar actividad
        $datos_pago = $resultado['datos_pago'];
        registrarActividad('FINALIZAR_SERVICIO', 
            "Placa: {$datos_pago['placa']}, Total: S/. {$datos_pago['importe']}");
        
        echo json_encode($resultado);
    } else {
        echo json_encode($resultado);
    }
    
} catch (Exception $e) {
    error_log("Error en parar_servicio.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>