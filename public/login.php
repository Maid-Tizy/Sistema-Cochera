<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Cochera</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-500 to-purple-600">

<div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">

    <!-- HEADER -->
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white text-center p-8">
        <i class="fas fa-car text-5xl mb-3"></i>
        <h3 class="text-2xl font-bold">Sistema de Cochera</h3>
        <p class="opacity-90">Iniciar Sesión</p>
    </div>

    <!-- BODY -->
    <div class="p-8">

        <?php
        require_once __DIR__ . '/../app/includes/auth.php';

        $mensaje = '';
        $tipo_mensaje = '';

        if (estaAutenticado()) {
            header('Location: dashboard.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $usuario = limpiarCadena($_POST['usuario'] ?? '');
            $clave = $_POST['clave'] ?? '';

            if (empty($usuario) || empty($clave)) {
                $mensaje = 'Por favor complete todos los campos';
                $tipo_mensaje = 'danger';
            } else {
                $resultado = iniciarSesion($usuario, $clave);

                if ($resultado['success']) {
                    registrarActividad('LOGIN', 'Inicio de sesión exitoso');
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $mensaje = $resultado['message'];
                    $tipo_mensaje = 'danger';
                }
            }
        }
        ?>

        <?php if ($mensaje): ?>
            <div class="mb-4 p-4 rounded-lg bg-red-100 text-red-700 flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-5">

            <div>
                <label for="usuario" class="block mb-1 font-medium text-gray-700">
                    <i class="fas fa-user mr-2"></i>Usuario
                </label>
                <input
                    type="text"
                    id="usuario"
                    name="usuario"
                    required
                    value="<?= isset($_POST['usuario']) ? limpiarCadena($_POST['usuario']) : '' ?>"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none"
                >
            </div>

            <div>
                <label for="clave" class="block mb-1 font-medium text-gray-700">
                    <i class="fas fa-lock mr-2"></i>Contraseña
                </label>
                <input
                    type="password"
                    id="clave"
                    name="clave"
                    required
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none"
                >
            </div>

            <button
                type="submit"
                class="w-full bg-green-500 hover:bg-green-600 text-white py-3 rounded-full font-semibold transition transform hover:-translate-y-1"
            >
                <i class="fas fa-sign-in-alt mr-2"></i>
                Iniciar Sesión
            </button>

        </form>

        <div class="text-center mt-6 text-sm text-gray-500">
            <i class="fas fa-info-circle mr-1"></i>
            Contacte al administrador para obtener acceso
        </div>

    </div>
</div>
</body>
</html>
