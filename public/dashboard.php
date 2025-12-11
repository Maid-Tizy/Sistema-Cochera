<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espacios Disponibles - Sistema de Cochera</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>

<body>
    <?php
    require_once __DIR__ . '/../app/includes/auth.php';
    require_once __DIR__ . '/../app/services/servicios.php';


    requiereAutenticacion();

    $usuario = obtenerUsuarioActual();
    $conteo = obtenerConteoEspacios();
    $espacios = obtenerEspacios();
    $recaudacion_dia = obtenerRecaudacionDia();
    ?>

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

                <a class="nav-link text-white" href="servicio.php">
                    <i class="fas fa-users-cog me-1"></i>En Servicio
                </a>
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
    <div class="modal fade" id="usuarioModal" tabindex="-1" aria-labelledby="usuarioModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-3">

                <!-- Icono de usuario -->
                <div class="mb-3">
                    <i class="fas fa-user-circle fa-5x text-primary"></i>
                </div>

                <!-- Título -->
                <h5 class="modal-title mb-3" id="usuarioModalLabel">Información del Usuario</h5>

                <!-- Cuerpo del modal -->
                <div class="modal-body">

                    <p class="fs-5"><strong>Usuario:</strong> <?= htmlspecialchars($usuario['usuario']) ?></p>
                    <p class="fs-5"><strong>Cargo:</strong> <?= $usuario['cargo'] === 'A' ? 'Administrador' : 'Básico' ?></p>

                    <p class="fs-1"><strong>Bienvenido al sistema</strong></p>

                </div>

                <!-- Footer -->
                <div class="modal-footer justify-content-center">
                    <a href="logout.php" class="btn btn-danger me-2">
                        <i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- Contenido Principal -->
    <div class="container-fluid py-4">
        <!-- Indicadores Superiores -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-0"><?php echo $conteo['ocupados']; ?>/<?php echo $conteo['total']; ?></h4>
                                <small>Espacios Ocupados</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-car fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-0"><?php echo $conteo['total'] - $conteo['ocupados']; ?></h4>
                                <small>Espacios Libres</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-parking fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-0">S/. <?php echo number_format($recaudacion_dia + 100, 2); ?></h4>
                                <small>Total caja + fondo inicial (S/100.00)</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-money-bill-wave fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <button class="btn btn-light btn-sm w-100" id="btnPararServicio">
                                    <i class="fas fa-search me-1"></i>
                                    Parar Servicio
                                </button>
                                <small class="d-block mt-1">Buscar por placa</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid de Espacios Libres -->
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
                                            Cochera <?php echo $espacio['id_espacio']; ?>
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