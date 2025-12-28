<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/db.php';
require_once __DIR__ . '/../app/includes/utils.php';

$usuario = obtenerUsuarioActual();
requiereAutenticacion();
if (!esAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $token  = $_POST['csrf_token'] ?? '';

    if (!verificarTokenCSRF($token)) {
        $msg = 'Token CSRF inválido';
    } else {

        if ($accion === 'create') {
            $codigo = generarCodigo(4);
            $estado = $_POST['estado'] ?? 'A';

            ejecutarConsulta(
                "INSERT INTO espacios (codigo, estado) VALUES (?,?)",
                [$codigo, $estado]
            );
            $msg = 'Espacio creado correctamente';
        }

        if ($accion === 'set_estado') {
            $id     = (int)$_POST['id_espacio'];
            $estado = $_POST['estado_sel'];

            // verificar si está ocupado
            $stmt = ejecutarConsulta(
                "SELECT COUNT(*) c FROM alquiler WHERE id_espacio = ? AND estado = 'A'",
                [$id]
            );
            $ocupado = $stmt && $stmt->fetch()['c'] > 0;

            if ($ocupado) {
                $msg = 'No se puede modificar un espacio ocupado';
            } else {
                ejecutarConsulta(
                    "UPDATE espacios SET estado=? WHERE id_espacio=?",
                    [$estado, $id]
                );
                $msg = 'Estado actualizado';
            }
        }
    }
}

/* ===== CONSULTA ===== */
$stmt = ejecutarConsulta("
    SELECT 
        e.id_espacio,
        e.codigo,
        e.estado,
        a.placa,
        CASE WHEN a.id_alquiler IS NULL THEN 0 ELSE 1 END AS ocupado
    FROM espacios e
    LEFT JOIN alquiler a 
        ON a.id_espacio = e.id_espacio
       AND a.estado = 'A'
    ORDER BY e.id_espacio
");

$espacios = $stmt ? $stmt->fetchAll() : [];
$csrf = generarTokenCSRF();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de Cochera</title>

    <!-- CDNs ORIGINALES -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>

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


    <div class="container py-4">

        <h3 class="mb-3">
            <i class="fas fa-parking me-2"></i>Administración de Espacios
        </h3>

        <?php if ($msg): ?>
            <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- CREAR ESPACIO -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="post" class="row g-3 align-items-end">
                    <input type="hidden" name="accion" value="create">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                    <div class="col-md-4">
                        <label class="form-label">Estado inicial</label>
                        <select name="estado" class="form-select">
                            <option value="A">Activo</option>
                            <option value="R">Reparación</option>
                            <option value="I">Inhabilitado</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button class="btn btn-success w-100">
                            <i class="fas fa-plus me-1"></i>Crear espacio
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TARJETAS -->
        <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-3">

            <?php foreach ($espacios as $e):

                if ($e['ocupado']) {
                    // 🔴 ROJO — OCUPADO
                    $border = 'border-danger';
                    $bg     = 'bg-danger text-white';
                    $texto  = 'Ocupado';
                    $icono  = 'fa-car';
                } elseif ($e['estado'] === 'R') {
                    // 🟡 AMARILLO — REPARACIÓN
                    $border = 'border-warning';
                    $bg     = 'bg-warning text-dark';
                    $texto  = 'Reparación';
                    $icono  = 'fa-tools';
                } elseif ($e['estado'] === 'I') {
                    // ⚫ GRIS — INHABILITADO
                    $border = 'border-secondary';
                    $bg     = 'bg-secondary text-white';
                    $texto  = 'Inhabilitado';
                    $icono  = 'fa-ban';
                } else {
                    // 🟢 VERDE — DISPONIBLE
                    $border = 'border-success';
                    $bg     = 'bg-success text-white';
                    $texto  = 'Disponible';
                    $icono  = 'fa-parking';
                }

            ?>

                <div class="col">
                    <div class="card <?= $border ?> text-center shadow-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#esp<?= $e['id_espacio'] ?>">

                        <div class="card-body <?= $bg ?> p-2 rounded">
                            <i class="fas <?= $icono ?> fa-lg mb-1"></i>
                            <div class="fw-bold"><?= htmlspecialchars($e['codigo']) ?></div>
                            <small class="d-block"><?= $texto ?></small>
                        </div>

                    </div>
                </div>


                <!-- MODAL -->
                <div class="modal fade" id="esp<?= $e['id_espacio'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-sm modal-dialog-centered">
                        <div class="modal-content">

                            <div class="modal-header">
                                <h6 class="modal-title">Espacio <?= $e['codigo'] ?></h6>
                                <button class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">

                                <?php if ($e['ocupado']): ?>
                                    <div class="alert alert-danger text-center">
                                        Ocupado<br>
                                        <strong><?= htmlspecialchars($e['placa']) ?></strong>
                                    </div>
                                <?php endif; ?>

                                <form method="post">
                                    <input type="hidden" name="accion" value="set_estado">
                                    <input type="hidden" name="id_espacio" value="<?= $e['id_espacio'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                                    <select name="estado_sel" class="form-select mb-2" <?= $e['ocupado'] ? 'disabled' : '' ?>>
                                        <option value="A" <?= $e['estado'] == 'A' ? 'selected' : '' ?>>Activo</option>
                                        <option value="R" <?= $e['estado'] == 'R' ? 'selected' : '' ?>>Reparación</option>
                                        <option value="I" <?= $e['estado'] == 'I' ? 'selected' : '' ?>>Inhabilitado</option>
                                    </select>

                                    <button class="btn btn-primary w-100" <?= $e['ocupado'] ? 'disabled' : '' ?>>
                                        Guardar
                                    </button>
                                </form>

                            </div>

                        </div>
                    </div>
                </div>

            <?php endforeach; ?>

        </div>

    </div>

    <!-- JS ORIGINALES -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>

</html>