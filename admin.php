<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

// Verificar si es admin
if ($_SESSION['rol'] != 'admin') {
    header('Location: index.php');
    exit();
}

$action = $_GET['action'] ?? 'dashboard';
$mensaje = '';

// Agregar cliente
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_cliente'])) {
    $stmt = $pdo->prepare("INSERT INTO clientes (nombre, direccion, telefono, email) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_POST['nombre'],
        $_POST['direccion'],
        $_POST['telefono'],
        $_POST['email']
    ]);
    $mensaje = 'Cliente agregado exitosamente';
}

// Agregar usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_usuario'])) {
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, usuario, password, rol) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_POST['nombre'],
        $_POST['usuario'],
        $_POST['password'],
        $_POST['rol']
    ]);
    $mensaje = 'Usuario agregado exitosamente';
}

// Eliminar cliente
if (isset($_GET['eliminar_cliente'])) {
    $cliente_id = $_GET['eliminar_cliente'];
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $mensaje = 'Cliente eliminado';
}

// Eliminar usuario
if (isset($_GET['eliminar_usuario'])) {
    $usuario_id = $_GET['eliminar_usuario'];
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $mensaje = 'Usuario eliminado';
}

$clientes = $pdo->query("SELECT * FROM clientes ORDER BY nombre")->fetchAll();
$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY nombre")->fetchAll();

// Estadísticas
$total_pedidos = $pdo->query("SELECT COUNT(*) as total FROM pedidos")->fetch()['total'];
$total_ventas = $pdo->query("SELECT SUM(total) as total FROM pedidos WHERE estado = 'entregado'")->fetch()['total'];
$total_productos = $pdo->query("SELECT COUNT(*) as total FROM productos")->fetch()['total'];
$total_clientes = $pdo->query("SELECT COUNT(*) as total FROM clientes")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración - FastFood</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">☰</button>
    <div class="sidebar-overlay" onclick="toggleMobileMenu()"></div>
    <div class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-placeholder">
                <img src="assets/images/logo.png" alt="FastFood Logo" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
        </div>
        <ul class="sidebar-nav">
            <li><a href="index.php">Inicio</a></li>
            <li><a href="pedidos.php">Pedidos</a></li>
            <li><a href="inventario.php">Inventario</a></li>
            <li><a href="distribucion.php">Distribución</a></li>
            <li><a href="admin.php" class="active">Administración</a></li>
            <li><a href="logout.php">Salir</a></li>
        </ul>
    </div>

    <div class="main-content">
        <header>
            <div class="header-logo">
                <img src="assets/images/logo.png" alt="FastFood Logo">
            </div>
            <div class="header-content">
                <h1>FastFood</h1>
                <p>Panel de Administración</p>
            </div>
        </header>

        <div class="content">
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <?php if ($action == 'dashboard'): ?>
                <h2>Dashboard Administrativo</h2>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo $total_pedidos; ?></h3>
                        <p>Total Pedidos</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Bs. <?php echo number_format($total_ventas ?: 0, 2); ?></h3>
                        <p>Total Ventas</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php echo $total_productos; ?></h3>
                        <p>Productos</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php echo $total_clientes; ?></h3>
                        <p>Clientes</p>
                    </div>
                </div>

                <div style="margin-top: 30px;">
                    <h3>Acciones Administrativas</h3>
                    <div style="margin-top: 15px;">
                        <a href="admin.php?action=clientes" class="btn btn-primary">Gestionar Clientes</a>
                        <a href="admin.php?action=usuarios" class="btn btn-success">Gestionar Usuarios</a>
                    </div>
                </div>

            <?php elseif ($action == 'clientes'): ?>
                <h2>Gestión de Clientes</h2>
                
                <div style="margin-bottom: 20px;">
                    <a href="admin.php?action=nuevo_cliente" class="btn btn-primary">Agregar Cliente</a>
                    <a href="admin.php" class="btn btn-warning">Volver</a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Dirección</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?php echo $cliente['id']; ?></td>
                                <td><?php echo $cliente['nombre']; ?></td>
                                <td><?php echo $cliente['direccion']; ?></td>
                                <td><?php echo $cliente['telefono']; ?></td>
                                <td><?php echo $cliente['email']; ?></td>
                                <td>
                                    <a href="admin.php?eliminar_cliente=<?php echo $cliente['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="return confirm('¿Eliminar este cliente?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php elseif ($action == 'nuevo_cliente'): ?>
                <h2>➕ Agregar Nuevo Cliente</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Nombre:</label>
                        <input type="text" name="nombre" required>
                    </div>

                    <div class="form-group">
                        <label>Dirección:</label>
                        <input type="text" name="direccion">
                    </div>

                    <div class="form-group">
                        <label>Teléfono:</label>
                        <input type="text" name="telefono">
                    </div>

                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email">
                    </div>

                    <button type="submit" name="agregar_cliente" class="btn btn-primary">Guardar Cliente</button>
                    <a href="admin.php?action=clientes" class="btn btn-warning">Cancelar</a>
                </form>

            <?php elseif ($action == 'usuarios'): ?>
                <h2>Gestión de Usuarios</h2>
                
                <div style="margin-bottom: 20px;">
                    <a href="admin.php?action=nuevo_usuario" class="btn btn-primary">Agregar Usuario</a>
                    <a href="admin.php" class="btn btn-warning">Volver</a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo $usuario['id']; ?></td>
                                <td><?php echo $usuario['nombre']; ?></td>
                                <td><?php echo $usuario['usuario']; ?></td>
                                <td><?php echo $usuario['rol']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($usuario['fecha_creacion'])); ?></td>
                                <td>
                                    <?php if ($usuario['id'] != 1): ?>
                                        <a href="admin.php?eliminar_usuario=<?php echo $usuario['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="return confirm('¿Eliminar este usuario?');">Eliminar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php elseif ($action == 'nuevo_usuario'): ?>
                <h2>➕ Agregar Nuevo Usuario</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Nombre Completo:</label>
                        <input type="text" name="nombre" required>
                    </div>

                    <div class="form-group">
                        <label>Usuario:</label>
                        <input type="text" name="usuario" required>
                    </div>

                    <div class="form-group">
                        <label>Contraseña:</label>
                        <input type="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label>Rol:</label>
                        <select name="rol" required>
                            <option value="admin">Administrador</option>
                            <option value="gerente">Gerente</option>
                            <option value="operador">Operador</option>
                        </select>
                    </div>

                    <button type="submit" name="agregar_usuario" class="btn btn-primary">Guardar Usuario</button>
                    <a href="admin.php?action=usuarios" class="btn btn-warning">Cancelar</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
    </div>

    <script>
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        // Close menu when clicking on a link
        document.querySelectorAll('.sidebar-nav a').forEach(link => {
            link.addEventListener('click', () => {
                const sidebar = document.querySelector('.sidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        });
    </script>
</body>
</html>
