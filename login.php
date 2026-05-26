<?php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();
    
    if ($user && $user['password'] == $password) {
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['cliente_id'] = $user['cliente_id'] ?? null;
        header('Location: index.php');
        exit();
    } else {
        $error = 'Usuario o contraseña incorrectos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FastFood</title>
    <link rel="stylesheet" href="style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        
        * {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fff5f5 0%, #fffbeb 100%);
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('./assets/images/logo.png');
            background-repeat: no-repeat;
            background-position: center;
            background-size: 100%;
            opacity: 1;
            filter: brightness(0.5) contrast(1.5);
            z-index: 0;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            padding: 48px;
            box-shadow: 0 10px 40px rgba(239, 68, 68, 0.15);
            max-width: 440px;
            width: 100%;
            border: 2px solid #fff7ed;
            position: relative;
            z-index: 1;
        }

        .login-content {
            position: relative;
            z-index: 1;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 36px;
        }

        .login-logo-image {
            width: 250px;
            height: 250px;
            background: linear-gradient(135deg, #ffffff 0%, #fef9c3 100%);
            border-radius: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 35px;
            box-shadow: 0 20px 60px rgba(239, 68, 68, 0.6), 0 0 100px rgba(255, 255, 255, 0.8);
            animation: pulse-glow 3s ease-in-out infinite;
            overflow: hidden;
            border: 6px solid rgba(255, 255, 255, 0.9);
            position: relative;
        }

        .login-logo-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 25px;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 1;
        }

        .login-logo-image:hover img {
            transform: scale(1.05) rotate(2deg);
        }

        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 20px 60px rgba(239, 68, 68, 0.6), 0 0 100px rgba(255, 255, 255, 0.8);
            }
            50% {
                box-shadow: 0 25px 80px rgba(239, 68, 68, 0.7), 0 0 120px rgba(255, 255, 255, 1);
            }
        }

        .login-logo h1 {
            font-size: 4em;
            margin-bottom: 18px;
            color: #ef4444;
            font-weight: 800;
            text-shadow: 2px 2px 24px rgba(239, 68, 68, 0.35);
        }

        .login-logo p {
            color: #64748b;
            font-size: 1em;
            font-weight: 500;
        }

        .login-form .form-group {
            margin-bottom: 24px;
        }

        .login-form .form-group label {
            font-weight: 600;
            color: #374151;
            font-size: 0.9em;
            margin-bottom: 10px;
            display: block;
        }

        .login-form .form-group input {
            padding: 14px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.95em;
            transition: all 0.3s ease;
            background: #ffffff;
            width: 100%;
            font-weight: 400;
        }

        .login-form .form-group input:focus {
            outline: none;
            border-color: #ef4444;
            background: white;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
            color: white;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .login-btn:hover {
            background: linear-gradient(135deg, #dc2626 0%, #ea580c 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }

        .login-footer {
            text-align: center;
            margin-top: 28px;
            padding-top: 28px;
            border-top: 2px solid #f3f4f6;
        }

        .login-footer p {
            color: #64748b;
            font-size: 0.9em;
            font-weight: 500;
        }

        .login-footer a {
            color: #ef4444;
            text-decoration: none;
            font-weight: 600;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .test-accounts {
            margin-top: 28px;
            padding: 20px;
            background: linear-gradient(135deg, #fff7ed 0%, #fef3c7 100%);
            border-radius: 12px;
            border: 2px solid #f97316;
            font-size: 0.85em;
        }

        .test-accounts strong {
            color: #c2410c;
            font-size: 0.95em;
            display: block;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .test-accounts p {
            color: #64748b;
            margin: 6px 0;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-content">
                <div class="login-logo">
                    <h1>FastFood</h1>
                    <p>Sistema de Pedidos</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin-bottom: 25px;"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label>Usuario:</label>
                        <input type="text" name="usuario" required placeholder="Ingresa tu usuario">
                    </div>
                    
                    <div class="form-group">
                        <label>Contraseña:</label>
                        <input type="password" name="password" required placeholder="Ingresa tu contraseña">
                    </div>
                    
                    <button type="submit" class="login-btn">Iniciar Sesión</button>
                </form>
                
                <div class="login-footer">
                    <p>¿Eres nuevo cliente? <a href="registro.php">Regístrate aquí</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
