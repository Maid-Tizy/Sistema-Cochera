<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/db.php';
$usuario = obtenerUsuarioActual();
requiereAutenticacion();
if (!esAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Acceso denegado.';
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verificarTokenCSRF($token)) {
        $msg = 'Token inválido.';
    } else {

        if ($accion === 'create') {

            $nombres   = trim($_POST['nombres']);
            $apellidos = trim($_POST['apellidos']);
            $formUsuario = trim($_POST['usuario']);
            $clave     = trim($_POST['clave']);
            $cargo     = $_POST['cargo'] ?? 'B';
            $estado    = $_POST['estado'] ?? 'A';

            if ($nombres && $formUsuario && $clave) {
                $sql = "INSERT INTO usuarios (nombres, apellidos, usuario, clave, cargo, estado, fecha_registro)
                        VALUES (?,?,?,?,?,?, NOW())";

                $r = ejecutarConsulta($sql, [
                    $nombres,
                    $apellidos,
                    $formUsuario,
                    $clave,
                    $cargo,
                    $estado
                ]);
            } else {
            }
        } elseif ($accion === 'update') {

            $id         = (int)$_POST['id_usuario'];
            $nombres    = trim($_POST['nombres']);
            $apellidos  = trim($_POST['apellidos']);
            $formUsuario = trim($_POST['usuario']);
            $clave      = trim($_POST['clave']);
            $cargo      = $_POST['cargo'];
            $estado     = $_POST['estado'];

            if ($id && $nombres && $formUsuario) {

                if ($clave !== '') {
                    $sql = "UPDATE usuarios
                            SET nombres=?, apellidos=?, usuario=?, clave=?, cargo=?, estado=?
                            WHERE id_usuario=?";
                    $params = [
                        $nombres,
                        $apellidos,
                        $formUsuario,
                        $clave,
                        $cargo,
                        $estado,
                        $id
                    ];
                } else {
                    $sql = "UPDATE usuarios
                            SET nombres=?, apellidos=?, usuario=?, cargo=?, estado=?
                            WHERE id_usuario=?";
                    $params = [
                        $nombres,
                        $apellidos,
                        $formUsuario,
                        $cargo,
                        $estado,
                        $id
                    ];
                }

                $r = ejecutarConsulta($sql, $params);
                $msg = $r ? 'Usuario actualizado correctamente.' : 'Error al actualizar usuario.';
            }
        } elseif ($accion === 'delete') {

            $id = (int)$_POST['id_usuario'];

            if ($id) {
                $r = ejecutarConsulta("DELETE FROM usuarios WHERE id_usuario=?", [$id]);
                $msg = $r ? 'Usuario eliminado.' : 'Error al eliminar.';
            }
        }
        header("Location: usuarios.php");
        exit;
    }
}

$stmt = ejecutarConsulta("SELECT * FROM usuarios ORDER BY id_usuario");
$usuarios = $stmt ? $stmt->fetchAll() : [];
$csrf = generarTokenCSRF();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de Cochera</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="assets/css/dashboard.css" rel="stylesheet">
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

    <!-- TÍTULO GENERAL -->
    <h2 class="mb-4">
        <i class="fas fa-users me-2"></i>Administración de Usuarios
    </h2>

    <?php if ($msg): ?>
        <div class="alert alert-info"><?= $msg ?></div>
    <?php endif; ?>

    <!-- SECCIÓN: FORMULARIO USUARIO -->
    <h4 class="mb-2">Formulario Usuario</h4>
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="post" id="userForm">
                <input type="hidden" name="accion" id="accion" value="create">
                <input type="hidden" name="id_usuario" id="id_usuario">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                <div class="row g-3">
                    <!-- FILA 1 -->
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="nombres" name="nombres" placeholder="Nombres" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="apellidos" name="apellidos" placeholder="Apellidos">
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Usuario" required>
                    </div>

                    <!-- FILA 2 -->
                    <div class="col-md-4">
                        <input type="password" class="form-control" id="clave" name="clave" placeholder="Contraseña">
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" id="toggleClave">
                            <label class="form-check-label" for="toggleClave">Mostrar</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="cargo" name="cargo">
                            <option value="B">Básico</option>
                            <option value="A">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="estado" name="estado">
                            <option value="A">Activo</option>
                            <option value="I">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3 text-end">
                    <button id="btnSubmit" class="btn btn-primary me-2">
                        <i class="fas fa-plus me-1"></i> Crear Usuario
                    </button>
                    <button type="button" id="btnCancel" class="btn btn-secondary d-none" onclick="cancelarEdicion()">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SECCIÓN: TABLA DE USUARIOS -->
    <h4 class="mb-2">Tabla de Usuarios</h4>
    <div class="card shadow-sm">
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Cargo</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['nombres'] . ' ' . $u['apellidos']) ?></td>
                                <td><?= htmlspecialchars($u['usuario']) ?></td>
                                <td>
                                    <span class="badge <?= $u['cargo']=='A' ? 'bg-primary' : 'bg-secondary' ?>">
                                        <?= $u['cargo']=='A' ? 'Admin' : 'Básico' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $u['estado']=='A' ? 'bg-success' : ($u['estado']=='I' ? 'bg-warning text-dark' : 'bg-dark') ?>">
                                        <?= $u['estado'] ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1" 
                                        onclick='editar(
                                            <?= $u["id_usuario"] ?>,
                                            <?= json_encode($u["nombres"]) ?>,
                                            <?= json_encode($u["apellidos"]) ?>,
                                            <?= json_encode($u["usuario"]) ?>,
                                            <?= json_encode($u["cargo"]) ?>,
                                            <?= json_encode($u["estado"]) ?>
                                        )'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteModal"
                                        data-id="<?= $u['id_usuario'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- MODAL DE CONFIRMACIÓN ELIMINAR -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" id="deleteForm" action="usuarios.php">
            <input type="hidden" name="accion" value="delete">
            <input type="hidden" name="id_usuario" id="delete_id">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    ¿Está seguro que desea eliminar este usuario?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Cargar el ID del usuario al modal
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        document.getElementById('delete_id').value = id;
    });
</script>

<script>
    

    // Cancelar edición
    function cancelarEdicion() {
        document.getElementById('accion').value = 'create';
        document.getElementById('id_usuario').value = '';
        document.getElementById('userForm').reset();
        document.getElementById('btnSubmit').innerHTML = '<i class="fas fa-plus me-1"></i> Crear Usuario';
        document.getElementById('btnCancel').classList.add('d-none');
    }

    // Cargar datos para edición
    function editar(id, n, a, u, c, e) {
        document.getElementById('accion').value = 'update';
        document.getElementById('id_usuario').value = id;
        document.getElementById('nombres').value = n;
        document.getElementById('apellidos').value = a;
        document.getElementById('usuario').value = u;
        document.getElementById('cargo').value = c;
        document.getElementById('estado').value = e;
        document.getElementById('btnSubmit').innerHTML = '<i class="fas fa-save me-1"></i> Guardar Cambios';
        document.getElementById('btnCancel').classList.remove('d-none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
</script>


    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>
</html>