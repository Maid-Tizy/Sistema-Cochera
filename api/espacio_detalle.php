<?php
/**
 * API: Obtener detalles de un espacio específico
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

if (!isset($input['id_espacio']) || !is_numeric($input['id_espacio'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de espacio inválido']);
    exit;
}

$id_espacio = (int)$input['id_espacio'];

try {
    $espacio = obtenerDetalleEspacio($id_espacio);
    
    if (!$espacio) {
        echo json_encode(['success' => false, 'message' => 'Espacio no encontrado']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'espacio' => $espacio
    ]);
    
} catch (Exception $e) {
    error_log("Error en espacio_detalle.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>