<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/utils.php';

/** Obtiene todos los espacios con su estado actual y posible alquiler activo */
function obtenerEspacios()
{
    $sql = "SELECT e.*, a.id_alquiler, a.placa, a.fecha_ingreso, a.hora_ingreso,
                   IF(a.id_alquiler IS NOT NULL, 'O', e.estado) estado_actual
            FROM espacios e
            LEFT JOIN alquiler a ON e.id_espacio=a.id_espacio AND a.estado='A' AND a.fecha_salida IS NULL
            ORDER BY e.id_espacio";
    $stmt = ejecutarConsulta($sql);              // Ejecuta la consulta
    return $stmt ? $stmt->fetchAll() : [];       // Devuelve todos los resultados o vacío
}

/** Cuenta total de espacios y cuántos están ocupados */
function obtenerConteoEspacios()
{
    $sql = "SELECT COUNT(*) total,
                   SUM(a.id_alquiler IS NOT NULL) ocupados
            FROM espacios e
            LEFT JOIN alquiler a ON e.id_espacio=a.id_espacio AND a.estado='A' AND a.fecha_salida IS NULL";
    $r = ejecutarConsulta($sql)?->fetch() ?: ['total' => 0, 'ocupados' => 0];
    return ['ocupados' => (int)$r['ocupados'], 'total' => (int)$r['total']];
}

/** Devuelve datos de un espacio, incluyendo el alquiler activo si existe */
function obtenerDetalleEspacio($id)
{
    $sql = "SELECT e.*,a.*,
                   DATE_FORMAT(a.fecha_ingreso,'%d/%m/%Y') fecha_ingreso_formato,
                   TIME_FORMAT(a.hora_ingreso,'%H:%i') hora_ingreso_formato
            FROM espacios e
            LEFT JOIN alquiler a ON e.id_espacio=a.id_espacio AND a.estado='A' AND a.fecha_salida IS NULL
            WHERE e.id_espacio=?";
    $stmt = ejecutarConsulta($sql, [$id]);
    return $stmt?->fetch() ?: false;
}

/** Inicia un nuevo servicio (alquiler) para un espacio */
function iniciarServicio($id, $placa, $precio_hora = null, $id_usuario = null)
{
    try {
        $pdo = getConexion();
        if (!$pdo) return ['success' => false, 'message' => 'Error de conexión'];
        $e = obtenerDetalleEspacio($id);                                 // Verifica que el espacio exista
        if (!$e) return ['success' => false, 'message' => 'Espacio no encontrado'];
        if ($e['id_alquiler']) return ['success' => false, 'message' => 'El espacio ya está ocupado'];
        // Genera código único
        do {
            $c = generarCodigo();
            $v = ejecutarConsulta("SELECT id_alquiler FROM alquiler WHERE codigo=?", [$c]);
        } while ($v && $v->fetch());
        if ($precio_hora === null) $precio_hora = PRECIO_HORA_DEFAULT;
        $pdo->beginTransaction();                                       // Inicia transacción
        // Registra el nuevo alquiler
        $pdo->prepare("INSERT INTO alquiler (id_espacio,codigo,estado,fecha_ingreso,hora_ingreso,placa,id_usuario)
                   VALUES (?,?, 'A',CURDATE(),NOW(),?,?)")->execute([$id, $c, $placa, $id_usuario]);
        $id_a = $pdo->lastInsertId();                                    // Guarda el id insertado
        // Marca el espacio como ocupado
        $pdo->prepare("UPDATE espacios SET estado='O',codigo=? WHERE id_espacio=?")->execute([$c, $id]);
        $pdo->commit();                                                // Confirma cambios
        return ['success' => true, 'message' => 'Servicio iniciado correctamente', 'id_alquiler' => $id_a, 'codigo' => $c];
    } catch (Exception $e) {
        if (isset($pdo)) $pdo->rollBack();                               // Revierte si hay error
        error_log($e->getMessage());
        return ['success' => false, 'message' => 'Error al iniciar el servicio'];
    }
}

/** Finaliza un servicio activo, calcula duración en minutos con redondeo CEILING a 60min, y registra pago */
function finalizarServicio($id, $ph)
{
    try {
        $pdo = getConexion();
        if (!$pdo) return ['success' => false, 'message' => 'Error de conexión'];
        // Busca alquiler activo
        $a = ejecutarConsulta("SELECT a.*,e.id_espacio FROM alquiler a JOIN espacios e USING(id_espacio)
                             WHERE a.id_alquiler=? AND a.estado='A' AND a.fecha_salida IS NULL", [$id])?->fetch();
        if (!$a) return ['success' => false, 'message' => 'Alquiler no encontrado o ya finalizado'];
        $pdo->beginTransaction();
        // Marca la salida
        $pdo->prepare("UPDATE alquiler SET fecha_salida=CURDATE(),hora_salida=NOW(),estado='F' WHERE id_alquiler=?")
            ->execute([$id]);
        // Obtener fechas y horas actualizadas directamente
        $alquiler = ejecutarConsulta("SELECT fecha_ingreso, hora_ingreso, fecha_salida, hora_salida FROM alquiler WHERE id_alquiler=?", [$id])?->fetch();
        // Si la fecha de ingreso y salida son iguales, solo restar las horas
        if ($alquiler['fecha_ingreso'] === $alquiler['fecha_salida']) {
            $minutos_reales = (int)ejecutarConsulta("SELECT TIMESTAMPDIFF(MINUTE, ?, ?) AS min_dur", [$alquiler['hora_ingreso'], $alquiler['hora_salida']])?->fetch()['min_dur'];
        } else {
            $fecha_ingreso = $alquiler['fecha_ingreso'] . ' ' . $alquiler['hora_ingreso'];
            $fecha_salida = $alquiler['fecha_salida'] . ' ' . $alquiler['hora_salida'];
            $minutos_reales = (int)ejecutarConsulta("SELECT TIMESTAMPDIFF(MINUTE, ?, ?) AS min_dur", [$fecha_ingreso, $fecha_salida])?->fetch()['min_dur'];
        }
        // Aplica redondeo CEILING al múltiplo de 60 más cercano hacia arriba (mínimo 60 minutos)
        $minutos_cobrados = max(60, ceil($minutos_reales / 60) * 60);
        // Calcula horas cobradas (minutos_cobrados / 60)
        $horas_cobradas = $minutos_cobrados / 60;
        // Calcula importe
        $imp = $horas_cobradas * $ph;
        // Inserta registro de pago (guardando minutos reales para historial)
        $pdo->prepare("INSERT INTO pagos (id_alquiler,duracion,importe,fecha_pago)
                       VALUES (?,?,?,NOW())")->execute([$id, $minutos_cobrados, $imp]);
        // Libera el espacio
        $pdo->prepare("UPDATE espacios SET estado='A' WHERE id_espacio=?")->execute([$a['id_espacio']]);
        $pdo->commit();
        // Devuelve datos del pago
        return ['success' => true, 'message' => 'Servicio finalizado correctamente', 'datos_pago' => [
            'minutos_reales' => $minutos_reales,
            'minutos_cobrados' => $minutos_cobrados,
            'horas_cobradas' => $horas_cobradas,
            'precio_hora' => $ph,
            'importe' => round($imp, 2),
            'placa' => $a['placa'],
            'codigo' => $a['codigo'],
            'fecha_ingreso' => $a['fecha_ingreso'],
            'hora_ingreso' => $a['hora_ingreso'],
            'fecha_salida' => date('Y-m-d'),
            'hora_salida' => date('H:i:s')
        ]];
    } catch (Exception $e) {
        if (isset($pdo)) $pdo->rollBack();
        error_log($e->getMessage());
        return ['success' => false, 'message' => 'Error al finalizar el servicio'];
    }
}

/** Busca un alquiler activo por número de placa */
function buscarPorPlaca($placa)
{
    $sql = "SELECT a.*,e.id_espacio FROM alquiler a JOIN espacios e USING(id_espacio)
          WHERE a.placa=? AND a.estado='A' AND a.fecha_salida IS NULL
          ORDER BY a.fecha_ingreso DESC,a.hora_ingreso DESC LIMIT 1";
    $stmt = ejecutarConsulta($sql, [$placa]);
    return $stmt?->fetch() ?: false;
}

/** Calcula la suma total recaudada del día */
function obtenerRecaudacionDia()
{
    $r = ejecutarConsulta("SELECT COALESCE(SUM(importe),0) total FROM pagos WHERE DATE(fecha_pago)=CURDATE()")?->fetch() ?: ['total' => 0];
    return (float)$r['total'];
}

/** Devuelve historial de servicios finalizados, filtrable por fecha */
function obtenerHistorialServicios($fi = null, $ff = null, $id_usuario = null, $es_admin = false)
{
    $sql = "SELECT a.id_alquiler,a.placa,a.codigo,a.fecha_ingreso,a.hora_ingreso,a.fecha_salida,a.hora_salida,
                   p.duracion,p.importe,p.fecha_pago,e.id_espacio,
                   u.nombres AS usuario_nombres, u.apellidos AS usuario_apellidos
               FROM alquiler a
               JOIN pagos p USING(id_alquiler)
               JOIN espacios e USING(id_espacio)
               LEFT JOIN usuarios u ON a.id_usuario = u.id_usuario
               WHERE a.estado='F'";
    $p = [];
    // Filtrado por usuario si NO es administrador
    if (!$es_admin && $id_usuario) {
        $sql .= " AND a.id_usuario=?";
        $p[] = $id_usuario;
    }
    // Filtrado opcional por rango de fechas
    if ($fi && $ff) {
        $sql .= " AND DATE(p.fecha_pago) BETWEEN ? AND ?";
        $p[] = $fi;
        $p[] = $ff;
    } elseif ($fi) {
        $sql .= " AND DATE(p.fecha_pago)>=?";
        $p[] = $fi;
    } elseif ($ff) {
        $sql .= " AND DATE(p.fecha_pago)<=?";
        $p[] = $ff;
    }
    $sql .= " ORDER BY p.fecha_pago DESC";
    $s = ejecutarConsulta($sql, $p);
    return $s?->fetchAll() ?: [];
}
