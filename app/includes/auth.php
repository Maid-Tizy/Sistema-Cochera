<?php
/**
 * AUTH: Funciones de autenticación y manejo de sesiones
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function iniciarSesion($usuario, $clave) {
    $sql = "SELECT id_usuario, nombres, apellidos, usuario, clave, cargo, estado 
            FROM usuarios 
            WHERE usuario = ? AND estado = 'A'";

    $stmt = ejecutarConsulta($sql, [$usuario]);

    if (!$stmt) {
        return ['success' => false, 'message' => 'Error en la consulta'];
    }

    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'Usuario no encontrado o inactivo'];
    }

    if ($user['clave'] !== $clave) {
        return ['success' => false, 'message' => 'Contraseña incorrecta'];
    }

    $_SESSION['user_id'] = $user['id_usuario'];
    $_SESSION['user_nombre'] = $user['nombres'] . ' ' . $user['apellidos'];
    $_SESSION['user_usuario'] = $user['usuario'];
    $_SESSION['user_cargo'] = $user['cargo'];
    $_SESSION['login_time'] = time();

    return [
        'success' => true,
        'message' => 'Sesión iniciada correctamente',
        'user_data' => $user
    ];
}

function estaAutenticado() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function obtenerUsuarioActual() {
    if (!estaAutenticado()) return null;

    return [
        'id_usuario' => $_SESSION['user_id'],
        'nombre' => $_SESSION['user_nombre'],
        'usuario' => $_SESSION['user_usuario'],
        'cargo' => $_SESSION['user_cargo']
    ];
}

function cerrarSesion() {
    if (session_status() == PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }
}

function requiereAutenticacion($redirect_url = 'login.php') {
    if (!estaAutenticado()) {
        header("Location: $redirect_url");
        exit;
    }
}

function esAdmin() {
    return estaAutenticado() && $_SESSION['user_cargo'] === 'A';
}

function registrarActividad($accion, $detalle = '') {
    if (!estaAutenticado()) return;

    $sql = "INSERT INTO actividad_usuarios (id_usuario, accion, detalle, fecha) 
            VALUES (?, ?, ?, NOW())";

    ejecutarConsulta($sql, [$_SESSION['user_id'], $accion, $detalle]);
}

function generarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verificarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

?>