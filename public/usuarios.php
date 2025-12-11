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
                $r = ejecutarConsulta("UPDATE usuarios SET estado='E' WHERE id_usuario=?", [$id]);
                $msg = $r ? 'Usuario eliminado.' : 'Error al eliminar.';
            }
        }
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
    <title>Administración de Usuarios</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Header */
        .page-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 30px 0;
        }

        .page-header h2 {
            font-weight: 800;
            color: #2b3e50;
        }

        /* Cards */
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 3px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: fadeIn 0.4s ease;
        }

        .card-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            font-size: 1.15rem;
            font-weight: 600;
            padding: 15px;
        }

        /* Tabla */
        .table thead {
            background: #e9ecef;
            font-weight: 600;
        }

        .table-hover tbody tr:hover {
            background: #f1f7ff;
        }

        /* Inputs */
        .form-control,
        .form-select {
            border-radius: 10px;
            padding: 10px 14px;
            border: 2px solid #e9ecef;
            transition: .3s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 .2rem rgba(0, 123, 255, .25);
        }

        /* Botones */
        .btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 10px 18px;
            transition: .2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
        }

        .btn-icon {
            padding: 6px 10px;
        }

        /* Animación */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Panel de edición */
        #editCard {
            border-left: 5px solid #0d6efd;
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

        <div class="page-header">
            <i class="fas fa-users fa-2x text-primary"></i>
            <h2>Administración de Usuarios</h2>

        </div>

        <?php if ($msg): ?>
            <div class="alert alert-info shadow-sm"><?= $msg ?></div>
        <?php endif; ?>

        <!-- CREAR USUARIO -->
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-user-plus me-2"></i> Crear Usuario</div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <input type="hidden" name="accion" value="create">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                    <div class="col-md-4">
                        <input class="form-control" name="nombres" placeholder="Nombres" required>
                    </div>

                    <div class="col-md-4">
                        <input class="form-control" name="apellidos" placeholder="Apellidos">
                    </div>

                    <div class="col-md-3">
                        <input class="form-control" name="usuario" placeholder="Usuario" required>
                    </div>

                    <!-- Campo contraseña con ocultamiento -->
                    <div class="col-md-3">
                        <input type="password" class="form-control" name="clave" id="clave" placeholder="Contraseña" required>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" id="toggleClave">
                            <label class="form-check-label" for="toggleClave">
                                Mostrar contraseña
                            </label>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <select class="form-select" name="cargo">
                            <option value="B">Básico</option>
                            <option value="A">Administrador</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select class="form-select" name="estado">
                            <option value="A">Activo</option>
                            <option value="I">Inactivo</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-grid">
                        <button class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Crear Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- LISTADO -->
        <div class="card">
            <div class="card-header"><i class="fas fa-table me-2"></i> Usuarios Registrados</div>
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
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
                                    <td><?= $u["id_usuario"] ?></td>
                                    <td><?= htmlspecialchars($u["nombres"] . " " . $u["apellidos"]) ?></td>
                                    <td><?= htmlspecialchars($u["usuario"]) ?></td>

                                    <td>
                                        <span class="badge bg-<?= $u["cargo"] == "A" ? "primary" : "secondary" ?>">
                                            <?= $u["cargo"] == "A" ? "Admin" : "Básico" ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge bg-<?= $u["estado"] == "A" ? "success" : ($u["estado"] == "I" ? "warning" : "dark") ?>">
                                            <?= $u["estado"] ?>
                                        </span>
                                    </td>

                                    <td class="text-center">

                                        <button class="btn btn-outline-primary btn-sm btn-icon me-1"
                                            onclick="editar(
                                        <?= $u['id_usuario'] ?>,
                                        '<?= addslashes($u['nombres']) ?>',
                                        '<?= addslashes($u['apellidos']) ?>',
                                        '<?= addslashes($u['usuario']) ?>',
                                        '<?= $u['cargo'] ?>',
                                        '<?= $u['estado'] ?>'
                                    )">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <form method="post" style="display:inline-block"
                                            onsubmit="return confirm('¿Eliminar usuario?')">
                                            <input type="hidden" name="accion" value="delete">
                                            <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                                            <button class="btn btn-outline-danger btn-sm btn-icon">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>

                                    </td>
                                </tr>
                            <?php endforeach ?>

                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <!-- PANEL DE EDICIÓN -->
        <div class="card mt-4" id="editCard" style="display:none;">
            <div class="card-header"><i class="fas fa-pen me-2"></i> Editar Usuario</div>
            <div class="card-body">

                <form method="post" class="row g-3">
                    <input type="hidden" name="accion" value="update">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="id_usuario" id="edit_id">

                    <div class="col-md-4">
                        <input id="edit_nombres" class="form-control" name="nombres" required>
                    </div>

                    <div class="col-md-4">
                        <input id="edit_apellidos" class="form-control" name="apellidos">
                    </div>

                    <div class="col-md-3">
                        <input id="edit_usuario" class="form-control" name="usuario" required>
                    </div>

                    <div class="col-md-3">
                        <input
                            type="password"
                            class="form-control"
                            name="clave"
                            id="edit_clave"
                            placeholder="Contraseña"
                            required>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" id="toggleEditClave">
                            <label class="form-check-label" for="toggleEditClave">
                                Mostrar contraseña
                            </label>
                        </div>
                    </div>


                    <div class="col-md-2">
                        <select id="edit_cargo" class="form-select" name="cargo">
                            <option value="A">Admin</option>
                            <option value="B">Básico</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select id="edit_estado" class="form-select" name="estado">
                            <option value="A">Activo</option>
                            <option value="I">Inactivo</option>
                            <option value="E">Eliminado</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-grid">
                        <button class="btn btn-success">
                            <i class="fas fa-save me-1"></i> Guardar
                        </button>
                    </div>

                    <div class="col-md-3 d-grid">
                        <button type="button" class="btn btn-secondary"
                            onclick="document.getElementById('editCard').style.display='none';">
                            Cancelar
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
    <script>
        function editar(id, n, a, u, c, e) {
            document.getElementById("editCard").style.display = "block";
            document.getElementById("edit_id").value = id;
            document.getElementById("edit_nombres").value = n;
            document.getElementById("edit_apellidos").value = a;
            document.getElementById("edit_usuario").value = u;
            document.getElementById("edit_cargo").value = c;
            document.getElementById("edit_estado").value = e;
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    </script>
    <script>
        const inputClave = document.getElementById('clave');
        const toggle = document.getElementById('toggleClave');

        toggle.addEventListener('change', () => {
            inputClave.type = toggle.checked ? 'text' : 'password';
        });
    </script>
    <script>
        document.getElementById('toggleEditClave').addEventListener('change', function() {
            const input = document.getElementById('edit_clave');
            input.type = this.checked ? 'text' : 'password';
        });
    </script>

</body>

</html>