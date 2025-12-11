<?php
/**
 * DB: Conexión a la base de datos y funciones básicas
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'cochera');
define('DB_USER', 'root');
define('DB_PASS', 'Arroz123');
define('DB_CHARSET', 'utf8mb4');

// Configuración del sistema (constantes compartidas)
define('PRECIO_HORA_DEFAULT', 3.00);
define('TIMEZONE', 'America/Lima');

date_default_timezone_set(TIMEZONE);

$pdo = null;
$conexion_error = null;

function getConexion() {
    global $pdo, $conexion_error;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
            $conexion_error = null;

        } catch (PDOException $e) {
            $conexion_error = "Error de conexión: " . $e->getMessage();
            $pdo = null;
        }
    }

    return $pdo;
}

function ejecutarConsulta($sql, $params = []) {
    try {
        $pdo = getConexion();
        if (!$pdo) return false;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;

    } catch (PDOException $e) {
        error_log("Error en consulta: " . $e->getMessage());
        return false;
    }
}

?>
