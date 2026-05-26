<?php
session_start();
require_once 'config.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrarse'])) {
    $nombre = $_POST['nombre'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    // Validaciones
    if ($password != $password_confirm) {
        $error = 'Las contraseñas no coinciden';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Verificar si el usuario ya existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
            $stmt->execute([$usuario]);
            if ($stmt->fetch()) {
                throw new Exception('El usuario ya existe');
            }
            
            // Insertar cliente
            $stmt = $pdo->prepare("INSERT INTO clientes (nombre, direccion, telefono, email) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nombre, $direccion, $telefono, $email]);
            $cliente_id = $pdo->lastInsertId();
            
            // Insertar usuario con rol cliente
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, usuario, password, rol, cliente_id) VALUES (?, ?, ?, 'cliente', ?)");
            $stmt->execute([$nombre, $usuario, $password, $cliente_id]);
            
            $pdo->commit();
            $mensaje = 'Registro exitoso. Ahora puedes iniciar sesión.';
            
            // Limpiar el formulario
            $_POST = array();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error al registrarse: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - FastFood</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 500px; margin-top: 50px; position: relative; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #fff5f5 0%, #fffbeb 100%); padding: 20px; overflow: hidden;">
        <div class="container" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-image: url('./assets/images/logo.png'); background-repeat: no-repeat; background-position: center; background-size: 100%; opacity: 1; filter: brightness(0.5) contrast(1.5); z-index: 0;"></div>
        <div class="content" style="position: relative; z-index: 1;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="text-align: center; color: #ef4444; font-weight: 800; font-size: 3.5em; text-shadow: 2px 2px 24px rgba(239, 68, 68, 0.35);">Registro de Cliente</h2>
                <p style="text-align: center; margin-bottom: 20px; color: #64748b; font-weight: 500; font-size: 1.2em;">FastFood</p>
            </div>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Nombre Completo:</label>
                    <input type="text" name="nombre" required value="<?php echo $_POST['nombre'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Dirección:</label>
                    <input type="text" name="direccion" value="<?php echo $_POST['direccion'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Teléfono:</label>
                    <input type="text" name="telefono" value="<?php echo $_POST['telefono'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Usuario:</label>
                    <input type="text" name="usuario" required value="<?php echo $_POST['usuario'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Contraseña:</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>Confirmar Contraseña:</label>
                    <input type="password" name="password_confirm" required>
                </div>
                
                <button type="submit" name="registrarse" class="btn btn-primary" style="width: 100%;">Registrarse</button>
            </form>
            
            <div style="text-align: center; margin-top: 20px;">
                <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
            </div>
        </div>
    </div>
</body>
</html>
