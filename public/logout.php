<?php
require_once __DIR__ . '/../app/includes/auth.php';

if (estaAutenticado()) {
    registrarActividad('LOGOUT', 'Cierre de sesión');
}

cerrarSesion();
header('Location: login.php?mensaje=logout');
exit;
?>
