<?php
/**
 * UTIL: Funciones utilitarias compartidas
 */

/**
 * Genera un código aleatorio para los espacios
 */
function generarCodigo($longitud = 4) {
    $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $codigo = '';
    for ($i = 0; $i < $longitud; $i++) {
        $codigo .= $caracteres[rand(0, strlen($caracteres) - 1)];
    }
    return $codigo;
}

/**
 * Calcula la diferencia en horas entre dos fechas
 */
function calcularHoras($fecha_inicio, $fecha_fin) {
    $inicio = new DateTime($fecha_inicio);
    $fin = new DateTime($fecha_fin);
    $diferencia = $fin->diff($inicio);

    $horas = $diferencia->h + ($diferencia->days * 24);
    $minutos = $diferencia->i / 60;

    return round($horas + $minutos, 2);
}

/**
 * Formatea una fecha para mostrar
 */
function formatearFecha($fecha, $formato = 'd/m/Y H:i') {
    return date($formato, strtotime($fecha));
}

/**
 * Sanitiza una cadena para prevenir XSS
 */
function limpiarCadena($cadena) {
    return htmlspecialchars(trim($cadena), ENT_QUOTES, 'UTF-8');
}

?>
