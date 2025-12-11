<?php
/**
 * API: Buscar servicio activo por placa
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

if (!isset($input['placa']) || empty(trim($input['placa']))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Placa requerida']);
    exit;
}

$placa = strtoupper(trim($input['placa']));

try {
    $alquiler = buscarPorPlaca($placa);
    
    if ($alquiler) {
        // Registrar actividad
        registrarActividad('BUSCAR_POR_PLACA', "Placa: {$placa} - Encontrada");
        
        echo json_encode([
            'success' => true,
            'alquiler' => $alquiler
        ]);
    } else {
        // Registrar actividad
        registrarActividad('BUSCAR_POR_PLACA', "Placa: {$placa} - No encontrada");
        
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró un servicio activo con esa placa'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en buscar_por_placa.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>