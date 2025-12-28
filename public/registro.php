<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Cochera</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <link href="assets/css/dashboard.css" rel="stylesheet">

</head>


<body>
    <?php
    require_once '../app/includes/auth.php';
    require_once '../app/services/servicios.php';

    // Verificar autenticación
    requiereAutenticacion();

    $usuario = obtenerUsuarioActual();

    // Obtener filtros
    $fecha_inicio = $_GET['fecha_inicio'] ?? '';
    $fecha_fin = $_GET['fecha_fin'] ?? '';
    $cajero_seleccionado = $_GET['cajero'] ?? null; // filtro por cajero

    // Si no hay filtros, usar fecha actual
    if (empty($fecha_inicio) && empty($fecha_fin)) {
        $fecha_inicio = date('Y-m-d');
        $fecha_fin = date('Y-m-d');
    }

    // Si es admin, obtener lista de usuarios para el select
    $usuarios = esAdmin() ? obtenerTodosUsuarios() : [];

    // Obtener servicios (filtrados según tipo de usuario y posible filtro por cajero)
    $servicios = obtenerHistorialServicios(
        $fecha_inicio,
        $fecha_fin,
        $usuario['id_usuario'],
        esAdmin(),
        $cajero_seleccionado
    );

    // Calcular total recaudado
    $total_recaudado = 0;
    foreach ($servicios as $servicio) {
        $total_recaudado += $servicio['importe'];
    }
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
    <div class="container-fluid py-4 px-2 px-md-3">

        <!-- Filtros -->
        <div class="card mb-4 shadow-sm no-print">
            <div class="card-body">
                <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filtros de Búsqueda</h5>
                <form method="GET" action="" class="row g-3 align-items-end">

                    <!-- Fecha Inicio -->
                    <div class="col-md-3">
                        <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= $fecha_inicio; ?>">
                    </div>

                    <!-- Fecha Fin -->
                    <div class="col-md-3">
                        <label for="fecha_fin" class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?= $fecha_fin; ?>">
                    </div>

                    <!-- Cajero (solo admin) -->
                    <?php if (esAdmin()): ?>
                        <div class="col-md-3">
                            <label for="cajero" class="form-label">Cajero</label>
                            <select class="form-select" id="cajero" name="cajero">
                                <option value="">Todos</option>
                                <?php foreach ($usuarios as $u): ?>
                                    <option value="<?= $u['id_usuario']; ?>" <?= ($cajero_seleccionado == $u['id_usuario']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['nombre_completo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Botones -->
                    <div class="col-md-3 d-flex gap-2 flex-column flex-md-row">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-search me-1"></i> Filtrar
                        </button>
                        <a href="registro.php" class="btn btn-secondary flex-fill">
                            <i class="fas fa-refresh me-1"></i> Limpiar
                        </a>
                    </div>

                </form>
            </div>
        </div>

        <!-- Tabla de Servicios -->
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Registro de Servicios
                    <?php if ($fecha_inicio && $fecha_fin): ?>
                        <small class="ms-2">(<?= formatearFecha($fecha_inicio, 'd/m/Y'); ?> - <?= formatearFecha($fecha_fin, 'd/m/Y'); ?>)</small>
                    <?php endif; ?>
                </h5>
                <div class="no-print">
                    <button type="button" class="btn btn-light btn-sm me-2" onclick="imprimirReporte()">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>
                    <button type="button" class="btn btn-success btn-sm me-2" onclick="descargarReporte()">
                        <i class="fas fa-file-csv me-1"></i> CSV
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="descargarReportePDF()">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </button>
                </div>
            </div>

            <div class="card-body p-3">
                <?php if (empty($servicios)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay servicios registrados</h5>
                        <p class="text-muted">No se encontraron servicios en el rango de fechas seleccionado.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle mb-0 text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Placa</th>
                                    <th>Cochera</th>
                                    <th>Fecha Registro</th>
                                    <th>Hora Inicio</th>
                                    <th>Fecha Salida</th>
                                    <th>Hora Salida</th>
                                    <th>Duración</th>
                                    <th>Precio/Hora</th>
                                    <th>Total</th>
                                    <th>Fecha Pago</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($servicios as $servicio): ?>
                                    <tr>
                                        <td><?= $servicio['id_alquiler']; ?></td>
                                        <td><strong><?= $servicio['placa']; ?></strong></td>
                                        <td><span class="badge bg-primary"><?= $servicio['codigo']; ?></span></td>
                                        <td><?= formatearFecha($servicio['fecha_ingreso'], 'd/m/Y'); ?></td>
                                        <td><?= date('H:i', strtotime($servicio['hora_ingreso'])); ?></td>
                                        <td><?= formatearFecha($servicio['fecha_salida'], 'd/m/Y'); ?></td>
                                        <td><?= date('H:i', strtotime($servicio['hora_salida'])); ?></td>
                                        <td><span class="badge bg-info"><?= number_format($servicio['duracion']); ?>m</span></td>
                                        <td>S/ <?= number_format(3, 2) ?></td>
                                        <td><strong class="text-success">S/. <?= number_format($servicio['importe'], 2); ?></strong></td>
                                        <td><?= formatearFecha($servicio['fecha_pago'], 'd/m/Y H:i'); ?></td>
                                        <td><?= $servicio['usuario_nombres'] . ' ' . $servicio['usuario_apellidos']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Resumen de Totales -->
        <?php if (!empty($servicios)): ?>
            <div class="card shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h6>Total de Servicios: <?= count($servicios); ?></h6>
                        <p class="mb-0">Período: <?= formatearFecha($fecha_inicio, 'd/m/Y'); ?> - <?= formatearFecha($fecha_fin, 'd/m/Y'); ?></p>
                    </div>
                    <div class="text-end">
                        <?php $fondo_caja = 100; ?>
                        <h5>Total Recaudado: S/. <?= number_format($total_recaudado + $fondo_caja, 2); ?></h5>
                        <small>Total Recaudado S/. <?= number_format($total_recaudado, 2); ?></small><br>
                        <small>Fondo de caja: S/. <?= number_format($fondo_caja, 2); ?></small>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function imprimirReporte() {
            window.print();
        }

        function descargarReporte() {
            // Generar reporte en formato CSV
            const servicios = <?php echo json_encode($servicios); ?>;

            if (servicios.length === 0) {
                alert('No hay datos para descargar');
                return;
            }

            let csv = 'ID,Placa,Cochera,Fecha Registro,Hora Inicio,Fecha Salida,Hora Salida,Duración (h),Precio/Hora,Total,Fecha Pago\n';

            servicios.forEach(servicio => {
                const precioHora = (servicio.importe / servicio.duracion).toFixed(2);
                csv += `${servicio.id_alquiler},"${servicio.placa}",${servicio.id_espacio},"${servicio.fecha_ingreso}","${servicio.hora_ingreso}","${servicio.fecha_salida}","${servicio.hora_salida}",${servicio.duracion},${precioHora},${servicio.importe},"${servicio.fecha_pago}"\n`;
            });

            // Agregar totales
            csv += `\n,,,,,,,,,<?php echo $total_recaudado; ?>,TOTAL RECAUDADO`;

            // Crear y descargar archivo
            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `reporte_servicios_${new Date().getTime()}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function descargarReportePDF() {
            const servicios = <?php echo json_encode($servicios); ?>;
            const totalRecaudado = <?php echo $total_recaudado; ?>;
            const fechaInicio = '<?php echo $fecha_inicio ? formatearFecha($fecha_inicio, 'd/m/Y') : ''; ?>';
            const fechaFin = '<?php echo $fecha_fin ? formatearFecha($fecha_fin, 'd/m/Y') : ''; ?>';

            if (servicios.length === 0) {
                alert('No hay datos para generar el PDF');
                return;
            }

            const {
                jsPDF
            } = window.jspdf;
            const pdf = new jsPDF('l', 'mm', 'a4'); // Landscape para más espacio

            // Encabezado del reporte
            pdf.setFontSize(18);
            pdf.setFont(undefined, 'bold');
            pdf.text('SISTEMA DE CONTROL DE COCHERA', pdf.internal.pageSize.getWidth() / 2, 20, {
                align: 'center'
            });

            pdf.setFontSize(14);
            pdf.text('REPORTE DE SERVICIOS', pdf.internal.pageSize.getWidth() / 2, 30, {
                align: 'center'
            });

            // Información del período
            pdf.setFontSize(10);
            pdf.setFont(undefined, 'normal');
            let periodoTexto = 'Período: ';
            if (fechaInicio && fechaFin) {
                periodoTexto += `${fechaInicio} - ${fechaFin}`;
            } else {
                periodoTexto += 'Todos los registros';
            }
            pdf.text(periodoTexto, pdf.internal.pageSize.getWidth() / 2, 40, {
                align: 'center'
            });

            pdf.text(`Fecha de generación: ${new Date().toLocaleDateString('es-PE')} ${new Date().toLocaleTimeString('es-PE', {hour: '2-digit', minute: '2-digit'})}`, pdf.internal.pageSize.getWidth() / 2, 46, {
                align: 'center'
            });

            // Preparar datos para la tabla
            const headers = ['ID', 'Placa', 'Cochera', 'F. Registro', 'H. Inicio', 'F. Salida', 'H. Salida', 'Duración', 'P/Hora', 'Total'];
            const data = servicios.map(servicio => [
                servicio.id_alquiler,
                servicio.placa,
                `Cochera ${servicio.id_espacio}`,
                new Date(servicio.fecha_ingreso).toLocaleDateString('es-PE'),
                servicio.hora_ingreso.substring(0, 5),
                new Date(servicio.fecha_salida).toLocaleDateString('es-PE'),
                servicio.hora_salida.substring(0, 5),
                `${parseFloat(servicio.duracion).toFixed(2)}h`,
                `S/. ${(servicio.importe / servicio.duracion).toFixed(2)}`,
                `S/. ${parseFloat(servicio.importe).toFixed(2)}`
            ]);

            // Generar tabla con autoTable
            pdf.autoTable({
                head: [headers],
                body: data,
                startY: 55,
                styles: {
                    fontSize: 8,
                    cellPadding: 2,
                },
                headStyles: {
                    fillColor: [0, 123, 255],
                    textColor: 255,
                    fontStyle: 'bold'
                },
                alternateRowStyles: {
                    fillColor: [248, 249, 250]
                },
                columnStyles: {
                    0: {
                        halign: 'center',
                        cellWidth: 15
                    }, // ID
                    1: {
                        halign: 'center',
                        cellWidth: 25
                    }, // Placa
                    2: {
                        halign: 'center',
                        cellWidth: 25
                    }, // Cochera
                    3: {
                        halign: 'center',
                        cellWidth: 25
                    }, // F. Registro
                    4: {
                        halign: 'center',
                        cellWidth: 20
                    }, // H. Inicio
                    5: {
                        halign: 'center',
                        cellWidth: 25
                    }, // F. Salida
                    6: {
                        halign: 'center',
                        cellWidth: 20
                    }, // H. Salida
                    7: {
                        halign: 'center',
                        cellWidth: 25
                    }, // Duración
                    8: {
                        halign: 'right',
                        cellWidth: 25
                    }, // P/Hora
                    9: {
                        halign: 'right',
                        cellWidth: 30
                    } // Total
                },
                margin: {
                    left: 15,
                    right: 15
                }
            });

            // Agregar resumen al final
            const finalY = pdf.lastAutoTable.finalY + 15;

            // Fondo para el resumen
            pdf.setFillColor(40, 167, 69);
            pdf.rect(15, finalY - 5, pdf.internal.pageSize.getWidth() - 30, 25, 'F');

            // Texto del resumen
            pdf.setTextColor(255, 255, 255);
            pdf.setFontSize(12);
            pdf.setFont(undefined, 'bold');
            pdf.text(`Total de Servicios: ${servicios.length}`, 25, finalY + 5);
            pdf.text(`TOTAL RECAUDADO: S/. ${totalRecaudado.toFixed(2)}`, pdf.internal.pageSize.getWidth() - 25, finalY + 5, {
                align: 'right'
            });

            if (fechaInicio && fechaFin) {
                pdf.setFontSize(10);
                pdf.setFont(undefined, 'normal');
                pdf.text(`Período: ${fechaInicio} - ${fechaFin}`, 25, finalY + 15);
            }

            // Pie de página
            pdf.setTextColor(128, 128, 128);
            pdf.setFontSize(8);
            pdf.text('Sistema de Control de Cochera - Reporte generado automáticamente', pdf.internal.pageSize.getWidth() / 2, pdf.internal.pageSize.getHeight() - 10, {
                align: 'center'
            });

            // Descargar el PDF
            const nombreArchivo = `reporte_servicios_${fechaInicio ? fechaInicio.replace(/\//g, '-') : 'completo'}_${new Date().getTime()}.pdf`;
            pdf.save(nombreArchivo);
        }

        // Auto-envío del formulario cuando cambian las fechas
        document.getElementById('fecha_inicio').addEventListener('change', function() {
            if (document.getElementById('fecha_fin').value) {
                document.querySelector('form').submit();
            }
        });

        document.getElementById('fecha_fin').addEventListener('change', function() {
            if (document.getElementById('fecha_inicio').value) {
                document.querySelector('form').submit();
            }
        });
    </script>
</body>

</html>