<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Control de Cochera</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Estilos inline originales */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container { background: white; border-radius: 15px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); overflow: hidden; max-width: 400px; width: 100%; }
        .login-header { background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; text-align: center; padding: 2rem; }
        .login-body { padding: 2rem; }
        .form-control:focus { border-color: #4CAF50; box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25); }
        .btn-login { background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); border: none; border-radius: 25px; padding: 12px 30px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4); }
        .alert { border-radius: 10px; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-car fa-3x mb-3"></i>
            <h3>Sistema de Cochera</h3>
            <p class="mb-0">Iniciar Sesión</p>
        </div>
        
        <div class="login-body">
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
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="usuario" class="form-label">
                        <i class="fas fa-user me-2"></i>Usuario
                    </label>
                    <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Ingrese su usuario" required value="<?php echo isset($_POST['usuario']) ? limpiarCadena($_POST['usuario']) : ''; ?>">
                </div>
                
                <div class="mb-4">
                    <label for="clave" class="form-label">
                        <i class="fas fa-lock me-2"></i>Contraseña
                    </label>
                    <input type="password" class="form-control" id="clave" name="clave" placeholder="Ingrese su contraseña" required>
                </div>
                
                <button type="submit" class="btn btn-login btn-primary w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Iniciar Sesión
                </button>
            </form>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Contacte al administrador para obtener acceso
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
