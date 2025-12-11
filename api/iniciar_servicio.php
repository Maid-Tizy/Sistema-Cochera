<?php
/**
 * API: Iniciar un nuevo servicio de alquiler
 */

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
if (!isset($input['id_espacio']) || !is_numeric($input['id_espacio'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de espacio inválido']);
    exit;
}

if (!isset($input['placa']) || empty(trim($input['placa']))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Placa requerida']);
    exit;
}

if (!isset($input['precio_hora']) || !is_numeric($input['precio_hora']) || $input['precio_hora'] <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Precio por hora inválido']);
    exit;
}

$id_espacio = (int)$input['id_espacio'];
$placa = strtoupper(trim($input['placa']));
$precio_hora = (float)$input['precio_hora'];

try {
    $usuario = obtenerUsuarioActual();
    $id_usuario = isset($usuario['id_usuario']) ? $usuario['id_usuario'] : null;
    $resultado = iniciarServicio($id_espacio, $placa, $precio_hora, $id_usuario);
    
    if ($resultado['success']) {
        // Registrar actividad
        registrarActividad('INICIAR_SERVICIO', "Espacio {$id_espacio}, Placa: {$placa}");
        
        echo json_encode([
            'success' => true,
            'message' => $resultado['message'],
            'id_alquiler' => $resultado['id_alquiler'],
            'codigo' => $resultado['codigo']
        ]);
    } else {
        echo json_encode($resultado);
    }
    
} catch (Exception $e) {
    error_log("Error en iniciar_servicio.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>