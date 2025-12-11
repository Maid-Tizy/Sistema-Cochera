<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/db.php';
require_once __DIR__ . '/../app/includes/utils.php';
$usuario = obtenerUsuarioActual();
requiereAutenticacion();
if (!esAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Acceso denegado. Sólo administradores.';
    exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    if (!verificarTokenCSRF($token)) {
        $msg = 'Token CSRF inválido.';
    } else {
        if ($accion === 'create') {
            $codigo = generarCodigo(4);
            $estado = $_POST['estado'] ?? 'A';
            $r = ejecutarConsulta("INSERT INTO espacios (codigo,estado) VALUES (?,?)", [$codigo, $estado]);
            $msg = $r ? 'Espacio creado correctamente.' : 'Error al crear el espacio.';
        } elseif ($accion === 'update') {
            $id = (int)($_POST['id_espacio'] ?? 0);
            $codigo = trim($_POST['codigo'] ?? '');
            $estado = trim($_POST['estado'] ?? 'A');
            if ($id) {
                $r = ejecutarConsulta("UPDATE espacios SET codigo=?,estado=? WHERE id_espacio=?", [$codigo, $estado, $id]);
                $msg = $r ? 'Espacio actualizado.' : 'No se pudo actualizar.';
            }
        } elseif ($accion === 'set_estado') {
            $id = (int)($_POST['id_espacio'] ?? 0);
            $estado = trim($_POST['estado_sel'] ?? 'A');
            if ($id) {
                $r = ejecutarConsulta("UPDATE espacios SET estado=? WHERE id_espacio=?", [$estado, $id]);
                $msg = $r ? 'Estado actualizado.' : 'No se pudo cambiar el estado.';
            }
        } elseif ($accion === 'delete') {
            $id = (int)($_POST['id_espacio'] ?? 0);
            if ($id) {
                $r = ejecutarConsulta("UPDATE espacios SET estado='E' WHERE id_espacio=?", [$id]);
                $msg = $r ? 'Espacio marcado como eliminado.' : 'Error al eliminar.';
            }
        }
    }
}

$stmt = ejecutarConsulta("SELECT id_espacio,codigo,estado FROM espacios ORDER BY id_espacio");
$espacios = $stmt ? $stmt->fetchAll() : [];
$csrf = generarTokenCSRF();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administración de Espacios</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- ESTILO TIPO DASHBOARD -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        h3 {
            font-weight: bold;
            color: #333;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-3px);
        }

        .card-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .table thead {
            background-color: #e9ecef;
            font-weight: 600;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 10px;
            transition: .3s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 .15rem rgba(0, 123, 255, .3);
        }

        .btn {
            border-radius: 10px;
            padding: 8px 16px;
            font-weight: 600;
            transition: .3s;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .badge {
            font-size: 0.9rem;
            padding: 6px 10px;
            border-radius: 8px;
        }

        .alert {
            border-radius: 10px;
            font-weight: 600;
        }
    </style>
</head>

<body>
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
    <div class="container py-4">

        <!-- ENCABEZADO -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fas fa-parking me-2"></i> Administración de Espacios</h3>
           
        </div>

        <!-- MENSAJE -->
        <?php if ($msg): ?>
            <div class="alert alert-info shadow-sm">
                <?= htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <!-- CREAR ESPACIO -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-plus-circle me-2"></i> Crear nuevo espacio
            </div>

            <div class="card-body">
                <form method="post" class="row g-3">
                    <input type="hidden" name="accion" value="create">
                    <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">

                    <div class="col-md-4">
                        <label class="form-label">Estado inicial</label>
                        <select name="estado" class="form-select">
                            <option value="A">Activo</option>
                            <option value="I">Inhabilitado</option>
                            <option value="R">Reparación</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-success w-100">
                            <i class="fas fa-check me-1"></i> Crear espacio
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- LISTA -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list me-2"></i> Lista de espacios
            </div>

            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Código</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($espacios as $e): ?>
                                <tr>
                                    <td><?= $e['id_espacio']; ?></td>
                                    <td><?= htmlspecialchars($e['codigo']); ?></td>

                                    <td>
                                        <span class="badge 
                                        <?= $e['estado'] == 'A' ? 'bg-success' : ($e['estado'] == 'I' ? 'bg-warning text-dark' : ($e['estado'] == 'R' ? 'bg-info text-dark' : 'bg-danger')); ?>
                                    ">
                                            <?= $e['estado']; ?>
                                        </span>
                                    </td>

                                    <td>

                                        <!-- CAMBIAR ESTADO -->
                                        <form method="post" class="d-inline-flex">
                                            <input type="hidden" name="accion" value="set_estado">
                                            <input type="hidden" name="id_espacio" value="<?= $e['id_espacio']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">

                                            <select name="estado_sel" class="form-select form-select-sm me-1">
                                                <option value="A" <?= $e['estado'] == 'A' ? 'selected' : ''; ?>>Activo</option>
                                                <option value="I" <?= $e['estado'] == 'I' ? 'selected' : ''; ?>>Inhabilitado</option>
                                                <option value="R" <?= $e['estado'] == 'R' ? 'selected' : ''; ?>>Reparación</option>
                                                <option value="E" <?= $e['estado'] == 'E' ? 'selected' : ''; ?>>Eliminado</option>
                                            </select>

                                            <button class="btn btn-sm btn-primary">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </form>

                                        <!-- ELIMINAR -->
                                        <form method="post"
                                            class="d-inline-block ms-1"
                                            onsubmit="return confirm('¿Marcar este espacio como eliminado?')">
                                            <input type="hidden" name="accion" value="delete">
                                            <input type="hidden" name="id_espacio" value="<?= $e['id_espacio']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">

                                            <button class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>

            </div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>

</html>