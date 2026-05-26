<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['rol'] != 'cliente') {
    header('Location: index.php');
    exit();
}

$cliente_id = $_SESSION['cliente_id'];

// Estadísticas del cliente
$mis_pedidos = $pdo->prepare("SELECT COUNT(*) as total FROM pedidos WHERE cliente_id = ?");
$mis_pedidos->execute([$cliente_id]);
$total_pedidos = $mis_pedidos->fetch()['total'];

$pedidos_activos = $pdo->prepare("SELECT COUNT(*) as total FROM pedidos WHERE cliente_id = ? AND estado IN ('pendiente', 'procesando', 'enviando')");
$pedidos_activos->execute([$cliente_id]);
$pedidos_pendientes = $pedidos_activos->fetch()['total'];

$total_gastado = $pdo->prepare("SELECT SUM(total) as total FROM pedidos WHERE cliente_id = ? AND estado = 'entregado'");
$total_gastado->execute([$cliente_id]);
$gastado = $total_gastado->fetch()['total'] ?: 0;

// Pedidos por estado
$pedidos_por_estado = $pdo->prepare("
    SELECT estado, COUNT(*) as total 
    FROM pedidos 
    WHERE cliente_id = ? 
    GROUP BY estado
");
$pedidos_por_estado->execute([$cliente_id]);
$estados = $pedidos_por_estado->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Estadísticas - FastFood</title>
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
            <li><a href="pedidos.php">Mis Pedidos</a></li>
            <li><a href="mis-estadisticas.php" class="active">Mis Estadísticas</a></li>
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
                <p>Mis Estadísticas</p>
            </div>
        </header>

        <div class="content">
            <h2>Tu Actividad</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $total_pedidos; ?></h3>
                    <p>Mis Pedidos</p>
                </div>
                
                <div class="stat-card">
                    <h3><?php echo $pedidos_pendientes; ?></h3>
                    <p>Activos</p>
                </div>
                
                <div class="stat-card">
                    <h3>Bs. <?php echo number_format($gastado, 2); ?></h3>
                    <p>Total Gastado</p>
                </div>
            </div>

            <h2>Pedidos por Estado</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 25px 0;">
                <?php foreach ($estados as $estado): ?>
                    <div class="stat-card" style="padding: 20px;">
                        <h3><?php echo $estado['total']; ?></h3>
                        <p><?php echo ucfirst($estado['estado']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <a href="index.php" class="btn btn-primary">🏠 Volver al Inicio</a>
                <a href="pedidos.php?action=nuevo" class="btn btn-warning">➕ Hacer Pedido</a>
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
