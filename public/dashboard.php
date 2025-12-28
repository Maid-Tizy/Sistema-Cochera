<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/services/servicios.php';

requiereAutenticacion();

$usuario = obtenerUsuarioActual();
$conteo = obtenerConteoEspacios();
$espacios = obtenerEspacios();
$recaudacion_dia = obtenerRecaudacionDia();

/* ===============================
   🔹 ESTADÍSTICAS SOLO ADMIN
=============================== */
$rec_hoy = ejecutarConsulta(
    "SELECT COALESCE(SUM(importe),0) total 
     FROM pagos 
     WHERE DATE(fecha_pago)=CURDATE()"
)?->fetch()['total'] ?? 0;

$rec_mes = ejecutarConsulta(
    "SELECT COALESCE(SUM(importe),0) total 
     FROM pagos 
     WHERE MONTH(fecha_pago)=MONTH(CURDATE())
       AND YEAR(fecha_pago)=YEAR(CURDATE())"
)?->fetch()['total'] ?? 0;

$rec_total = ejecutarConsulta(
    "SELECT COALESCE(SUM(importe),0) total 
     FROM pagos"
)?->fetch()['total'] ?? 0;

$servicios_hoy = ejecutarConsulta(
    "SELECT COUNT(*) total 
     FROM alquiler 
     WHERE DATE(fecha_ingreso)=CURDATE()"
)?->fetch()['total'] ?? 0;


$meta_dia = 500;     // meta diaria estimada
$meta_mes = 12000;  // meta mensual estimada
$capacidad_servicios = $conteo['total'] * 3; // rotación estimada

function porcentaje($valor, $max)
{
    if ($max <= 0 || $valor <= 0) return 0;
    return min(100, round(($valor / $max) * 100));
}

$porc_ocupacion = porcentaje($conteo['ocupados'], $conteo['total']);
$porc_hoy = porcentaje($rec_hoy, $meta_dia);
$porc_mes = porcentaje($rec_mes, $meta_mes);
$porc_servicios = porcentaje($servicios_hoy, $capacidad_servicios);



$ranking_cajeros = ejecutarConsulta("
    SELECT 
        u.id_usuario,
        CONCAT(u.nombres,' ',u.apellidos) cajero,
        COUNT(p.id_pago) servicios,
        SUM(p.importe) total
    FROM pagos p
    JOIN alquiler a USING(id_alquiler)
    JOIN usuarios u ON a.id_usuario = u.id_usuario
    WHERE DATE(p.fecha_pago)=CURDATE()
    GROUP BY u.id_usuario
    ORDER BY total DESC
")->fetchAll();

$cajero_top = $ranking_cajeros[0] ?? null;

$total_dia = array_sum(array_column($ranking_cajeros, 'total'));


$promedio_servicio = ($cajero_top && $cajero_top['servicios'] > 0)
    ? round($cajero_top['total'] / $cajero_top['servicios'], 2)
    : 0;

$participacion = ($total_dia > 0 && $cajero_top)
    ? porcentaje($cajero_top['total'], $total_dia)
    : 0;

?>



<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Cochera</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>
<style>
/* Tarjeta estadística uniforme */
.stat-card {
    height: 100%;
    min-height: 260px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

/* Zona superior (icono o círculo) */
.stat-top {
    height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Círculo estadístico */
.stat-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* Círculo interno */
.stat-circle::after {
    content: "";
    width: 85px;
    height: 85px;
    background: #fff;
    border-radius: 50%;
    position: absolute;
}

/* Texto central */
.stat-circle span {
    position: relative;
    z-index: 2;
    font-size: 1.1rem;
    line-height: 1;
}

/* Texto inferior */
.stat-label {
    text-align: center;
    margin-top: 10px;
    font-weight: 600;
    min-height: 40px;
}

/* Subtexto */
.stat-sub {
    font-size: 0.85rem;
    color: #6c757d;
    min-height: 18px;
}

</style>

<body>


    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-car me-2"></i>
                Sistema de Cochera
            </a>

            <div class="navbar-nav ms-3">
                <?php if (esAdmin()): // Mostrar menús sólo para admin 
                ?>
                    <a class="nav-link text-white" href="usuarios.php">
                        <i class="fas fa-users-cog me-1"></i>Usuarios
                    </a>
                    <a class="nav-link text-white" href="espacios.php">
                        <i class="fas fa-th-large me-1"></i>Espacios
                    </a>
                <?php endif; ?>
            </div>
            <div class="navbar-nav ms-3">
                <?php if (!esAdmin()): ?>
                    <a class="nav-link text-white" href="servicio.php">
                        <i class="fas fa-users-cog me-1"></i>En Servicio
                    </a>
                <?php endif; ?>
                <a class="nav-link text-white" href="registro.php">
                    <i class="fas fa-th-large me-1"></i>Registro
                </a>
            </div>

            <div class="navbar-nav ms-auto">
                <!-- Icono/Nombre del usuario abre modal de datos -->
                <a class="nav-link text-white" href="#" data-bs-toggle="modal" data-bs-target="#usuarioModal">
                    <i class="fas fa-user-circle me-1"></i>
                    <?= $usuario['nombre']; ?>
                </a>
            </div>

        </div>
    </nav>

    <!-- MODAL USUARIO -->
    <div class="modal fade" id="usuarioModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content shadow-lg border-0">

                <!-- Header -->
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="fas fa-id-badge me-2"></i>
                        Perfil de Usuario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">

                    <!-- Avatar -->
                    <div class="text-center mb-3">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center"
                            style="width:110px;height:110px;">
                            <i class="fas fa-user fa-4x text-primary"></i>
                        </div>
                    </div>

                    <!-- Nombre -->
                    <h5 class="text-center fw-bold mb-1">
                        <?= htmlspecialchars($usuario['nombre']); ?>
                    </h5>

                    <!-- Rol -->
                    <div class="text-center mb-3">
                        <span class="badge <?= $usuario['cargo'] === 'A' ? 'bg-success' : 'bg-secondary' ?> px-3 py-2">
                            <i class="fas fa-user-shield me-1"></i>
                            <?= $usuario['cargo'] === 'A' ? 'Administrador' : 'Usuario' ?>
                        </span>
                    </div>

                    <!-- Info -->
                    <div class="card border-0 bg-light">
                        <div class="card-body">

                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-user text-primary me-2"></i>
                                <span class="fw-semibold">Usuario:</span>
                                <span class="ms-auto"><?= htmlspecialchars($usuario['usuario']) ?></span>
                            </div>

                            <div class="d-flex align-items-center">
                                <i class="fas fa-circle-check text-success me-2"></i>
                                <span class="fw-semibold">Estado:</span>
                                <span class="ms-auto">Activo</span>
                            </div>

                        </div>
                    </div>

                </div>

                <!-- Footer -->
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>

                    <a href="logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt me-1"></i> Cerrar sesión
                    </a>
                </div>

            </div>
        </div>
    </div>


    <!-- Contenido Principal -->
    <div class="container-fluid py-4">
        <!-- Indicadores Superiores -->
        <div class="row mb-4">

            <!-- Espacios Ocupados -->
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-0">
                                    <?php echo $conteo['ocupados']; ?>/<?php echo $conteo['total']; ?>
                                </h4>
                                <small>Espacios Ocupados</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-car fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Espacios Libres -->
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-0">
                                    <?php echo $conteo['total'] - $conteo['ocupados']; ?>
                                </h4>
                                <small>Espacios Libres</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-parking fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Caja -->
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-0">
                                    S/. <?php echo number_format($recaudacion_dia + 100, 2); ?>
                                </h4>
                                <small>Total caja + fondo inicial (S/100.00)</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-money-bill-wave fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <?php if (esAdmin()): ?>
            <div class="row mb-4 text-center">

                <!-- Ocupación -->
                <div class="col-md-3">
                    <div class="card p-3 shadow-sm">
                        <div class="d-flex justify-content-center">
                            <div class="stat-circle"
                                style="background: conic-gradient(#0d6efd <?= $porc_ocupacion ?>%, #e9ecef 0);">
                                <span><?= $porc_ocupacion ?>%</span>
                            </div>
                        </div>
                        <div class="stat-label">Ocupación Actual</div>
                    </div>
                </div>

                <!-- Ingresos Hoy -->
                <div class="col-md-3">
                    <div class="card p-3 shadow-sm">
                        <div class="d-flex justify-content-center">
                            <div class="stat-circle"
                                style="background: conic-gradient(#198754 <?= $porc_hoy ?>%, #e9ecef 0);">
                                <span>S/ <?= number_format($rec_hoy, 2) ?></span>
                            </div>
                        </div>
                        <div class="stat-label">
                            Ingresos Hoy<br>
                            <small class="text-muted"><?= $porc_hoy ?>% de meta</small>
                        </div>
                    </div>
                </div>

                <!-- Ingresos del Mes -->
                <div class="col-md-3">
                    <div class="card p-3 shadow-sm">
                        <div class="d-flex justify-content-center">
                            <div class="stat-circle"
                                style="background: conic-gradient(#6f42c1 <?= $porc_mes ?>%, #e9ecef 0);">
                                <span>S/ <?= number_format($rec_mes, 0) ?></span>
                            </div>
                        </div>
                        <div class="stat-label">
                            Ingresos del Mes<br>
                            <small class="text-muted"><?= $porc_mes ?>% de meta</small>
                        </div>
                    </div>
                </div>

                <!-- Servicios -->
                <div class="col-md-3">
                    <div class="card p-3 shadow-sm">
                        <div class="d-flex justify-content-center">
                            <div class="stat-circle"
                                style="background: conic-gradient(#fd7e14 <?= $porc_servicios ?>%, #e9ecef 0);">
                                <span><?= $servicios_hoy ?></span>
                            </div>
                        </div>
                        <div class="stat-label">
                            Servicios Hoy<br>
                            <small class="text-muted"><?= $porc_servicios ?>% capacidad</small>
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>
        <?php if (esAdmin()): ?>
            <div class="row mb-4 text-center">

                <!-- Cajero Top -->
                <div class="col-md-3">
                    <div class="card p-3 shadow-sm h-100 d-flex justify-content-between">
                        <div class="stat-top">
                            <i class="fas fa-user-tie fa-2x text-primary"></i>
                        </div>
                        <div class="stat-title">Cajero líder</div>
                        <div class="stat-value fw-bold">
                            <?= $cajero_top['cajero'] ?? '—' ?>
                        </div>
                        <div class="stat-sub">
                            S/ <?= number_format($cajero_top['total'] ?? 0, 2) ?>
                        </div>
                    </div>
                </div>

                <!-- Servicios del líder -->
                <div class="col-md-3">
                    <div class="card p-3 shadow-sm h-100 d-flex justify-content-between">
                        <div class="stat-top">
                            <i class="fas fa-car-side fa-2x text-success"></i>
                        </div>
                        <div class="stat-title">Servicios (líder)</div>
                        <div class="stat-value display-6">
                            <?= $cajero_top['servicios'] ?? 0 ?>
                        </div>
                        <div class="stat-sub">&nbsp;</div>
                    </div>
                </div>

                <!-- Promedio -->
                <div class="col-md-3">
                    <div class="card p-3 shadow-sm h-100 d-flex justify-content-between">
                        <div class="stat-top">
                            <i class="fas fa-calculator fa-2x text-warning"></i>
                        </div>
                        <div class="stat-title">Promedio / Servicio</div>
                        <div class="stat-value display-6">
                            S/ <?= number_format($promedio_servicio, 2) ?>
                        </div>
                        <div class="stat-sub">&nbsp;</div>
                    </div>
                </div>

                <!-- Participación -->
                <div class="col-md-3">
                    <div class="card p-3 shadow-sm h-100 d-flex justify-content-between">
                        <div class="stat-top d-flex justify-content-center">
                            <div class="stat-circle"
                                style="background: conic-gradient(#dc3545 <?= $participacion ?>%, #e9ecef 0);">
                                <span><?= $participacion ?>%</span>
                            </div>
                        </div>
                        <div class="stat-title">Participación</div>
                        <div class="stat-value fw-bold">del total diario</div>
                        <div class="stat-sub">&nbsp;</div>
                    </div>
                </div>

            </div>
        <?php endif; ?>



        <!-- Grid de Espacios Libres -->
        <?php if (!esAdmin()): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-th-large me-2"></i>
                                Espacios Disponibles
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="espacios-grid">
                                <?php foreach ($espacios as $espacio): ?>
                                    <?php if ($espacio['estado_actual'] === 'A'): // Solo libres 
                                    ?>
                                        <?php
                                        $clase_estado = 'espacio-libre';
                                        $icono_estado = 'fas fa-parking';
                                        $texto_estado = 'Disponible';
                                        ?>
                                        <div class="espacio-item <?php echo $clase_estado; ?>"
                                            data-id="<?php echo $espacio['id_espacio']; ?>"
                                            data-estado="<?php echo $espacio['estado_actual']; ?>"
                                            onclick="abrirVentanaEspacio(<?php echo $espacio['id_espacio']; ?>)">
                                            <div class="espacio-numero">
                                                Espacio <?php echo $espacio['codigo']; ?>
                                            </div>
                                            <div class="espacio-icono">
                                                <i class="<?php echo $icono_estado; ?>"></i>
                                            </div>
                                            <div class="espacio-estado">
                                                <?php echo $texto_estado; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para Gestión de Espacio -->
    <div class="modal fade" id="modalEspacio" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-car me-2"></i>
                        <span id="tituloModal">Cochera</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formServicio">
                        <input type="hidden" id="idEspacio" name="id_espacio">
                        <input type="hidden" id="idAlquiler" name="id_alquiler">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-car me-1"></i>Placa del Vehículo
                                    </label>
                                    <input type="text" class="form-control" id="placa" name="placa"
                                        placeholder="Ej: ABC-123" required autocomplete="off"
                                        maxlength="7" pattern="[A-Z]{3}-[0-9]{3}" title="Formato: ABC-123">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-qrcode me-1"></i>Código
                                    </label>
                                    <input type="text" class="form-control" id="codigo" name="codigo" readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-money-bill me-1"></i>Precio por Hora (S/.)
                                    </label>
                                    <input type="number" class="form-control" id="precioHora" name="precio_hora"
                                        value="<?php echo PRECIO_HORA_DEFAULT; ?>" readonly>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-calendar me-1"></i>Fecha de Registro
                                    </label>
                                    <input type="text" class="form-control" id="fechaRegistro" readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-clock me-1"></i>Hora de Ingreso
                                    </label>
                                    <input type="text" class="form-control" id="horaIngreso" readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-calendar-check me-1"></i>Fecha de Salida
                                    </label>
                                    <input type="text" class="form-control" id="fechaSalida" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-clock me-1"></i>Hora de Salida
                                    </label>
                                    <input type="text" class="form-control" id="horaSalida" readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-hourglass-half me-1"></i>Duración (minutos)
                                    </label>
                                    <input type="text" class="form-control" id="duracion" readonly>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-calculator me-1"></i>Total a Pagar (S/.)
                                    </label>
                                    <input type="text" class="form-control total-pagar" id="totalPagar" readonly>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnIniciar"
                        onclick="iniciarServicio(true); setTimeout(() => location.reload(), 1000);">
                        <i class="fas fa-play me-1"></i>Iniciar
                    </button>



                    <button type="button" class="btn btn-primary" id="btnImprimirEntrada" style="display: none;" onclick="imprimirEntrada()">
                        <i class="fas fa-print me-1"></i>Imprimir Entrada
                    </button>
                    <button type="button" class="btn btn-danger" id="btnParar" onclick="pararServicio(true)" style="display: none;">
                        <i class="fas fa-stop me-1"></i>Parar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnImprimirSalida" style="display: none;" onclick="imprimirSalida()">
                        <i class="fas fa-print me-1"></i>Imprimir Salida
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal para Búsqueda por Placa -->
    <div class="modal fade" id="modalBuscarPlaca" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-search me-2"></i>
                        Buscar Servicio por Placa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Ingrese la placa del vehículo:</label>
                        <input type="text" class="form-control" id="placaBusqueda"
                            placeholder="Ej: ABC-123" style="text-transform: uppercase;" autocomplete="off"
                            maxlength="7" pattern="[A-Z]{3}-[0-9]{3}" title="Formato: ABC-123">
                    </div>
                    <div id="alertaBusqueda" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="buscarPorPlaca()">
                        <i class="fas fa-search me-1"></i>Buscar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('#placa, #placaBusqueda').forEach(input => {
            input.addEventListener('input', e => {
                let v = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                if (v.length > 3) v = v.slice(0, 3) + '-' + v.slice(3);
                e.target.value = v.slice(0, 7);
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js" defer></script>
</body>

</html>