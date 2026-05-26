<?php
session_start();
require_once 'config.php';

// Verificar si hay sesión activa
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FastFood - Sistema de Pedidos</title>
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
            <li><a href="index.php" class="active">Inicio</a></li>
            <li><a href="pedidos.php"><?php echo $_SESSION['rol'] == 'cliente' ? 'Mis Pedidos' : 'Pedidos'; ?></a></li>
            <?php if ($_SESSION['rol'] == 'cliente'): ?>
                <li><a href="mis-estadisticas.php">Mis Estadísticas</a></li>
            <?php else: ?>
                <li><a href="inventario.php">Inventario</a></li>
                <li><a href="distribucion.php">Distribución</a></li>
                <li><a href="admin.php">Administración</a></li>
            <?php endif; ?>
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
                <p>Delicioso comida rápida a tu alcance</p>
            </div>
        </header>

        <div class="content">
            <?php if ($_SESSION['rol'] == 'cliente'): ?>
                <h2>Bienvenido, <?php echo $_SESSION['nombre']; ?></h2>
                
                <!-- Sección de Productos -->
                <h2>Nuestro Menú Destacado</h2>
                <div class="productos-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin: 25px 0;">
                    <?php
                    // Mostrar 3 productos en móvil, 6 en escritorio
                    $limit = 6;
                    ?>
                    <?php
                    $productos_destacados = $pdo->query("SELECT * FROM productos WHERE stock_actual > 0 ORDER BY nombre LIMIT 6")->fetchAll();
                    
                    // Emojis según categoría
                    $emojis = [
                        'hamburguesa' => '🍔',
                        'papas' => '🍟',
                        'bebida' => '🥤',
                        'pollo' => '🍗',
                        'pizza' => '🍕',
                        'postre' => '🍰',
                        'default' => '🍽️'
                    ];
                    
                    foreach ($productos_destacados as $index => $producto):
                        $imagen = $producto['imagen'] ?? 'assets/images/productos/default.jpg';
                        $categoria = strtolower($producto['categoria'] ?? '');
                        $emoji = '🍽️';
                        foreach ($emojis as $key => $val) {
                            if (strpos($categoria, $key) !== false) {
                                $emoji = $val;
                                break;
                            }
                        }
                        // Si el nombre contiene ciertas palabras, asignar emoji
                        if (stripos($producto['nombre'], 'hamburguesa') !== false || stripos($producto['nombre'], 'burger') !== false) $emoji = '🍔';
                        elseif (stripos($producto['nombre'], 'papas') !== false || stripos($producto['nombre'], 'fritas') !== false) $emoji = '🍟';
                        elseif (stripos($producto['nombre'], 'bebida') !== false || stripos($producto['nombre'], 'refresco') !== false || stripos($producto['nombre'], 'gaseosa') !== false) $emoji = '🥤';
                        elseif (stripos($producto['nombre'], 'pollo') !== false) $emoji = '🍗';
                        elseif (stripos($producto['nombre'], 'pizza') !== false) $emoji = '🍕';
                        elseif (stripos($producto['nombre'], 'helado') !== false || stripos($producto['nombre'], 'postre') !== false) $emoji = '🍰';
                    ?>
                        <div class="product-card producto-item" data-index="<?php echo $index; ?>" style="text-align: center;">
                            <div style="background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%); border-radius: 15px; padding: 20px; margin-bottom: 15px; overflow: hidden;">
                                <img src="<?php echo $imagen; ?>" alt="<?php echo $producto['nombre']; ?>" style="width: 100%; height: 150px; object-fit: cover; border-radius: 10px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <span style="font-size: 4em; display: none;"><?php echo $emoji; ?></span>
                            </div>
                            <h4 style="font-size: 1.2em; margin-bottom: 10px;"><?php echo $producto['nombre']; ?></h4>
                            <p style="color: #6b7280; font-size: 0.9em; margin-bottom: 15px; min-height: 40px;"><?php echo $producto['descripcion'] ?: 'Delicioso producto de FastFood'; ?></p>
                            <div style="background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%); padding: 15px; border-radius: 10px; margin-bottom: 15px;">
                                <span class="price" style="font-size: 1.5em;">Bs. <?php echo number_format($producto['precio_unitario'], 2); ?></span>
                                <br>
                                <span class="stock" style="font-size: 0.85em;">Stock: <?php echo $producto['stock_actual']; ?> unidades</span>
                            </div>
                            <a href="pedidos.php?action=nuevo" class="btn btn-primary" style="width: 100%; text-align: center; padding: 15px;">🛒 Agregar al Pedido</a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="text-align: center; margin-top: 30px; padding: 20px; background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%); border-radius: 15px;">
                    <p style="margin-bottom: 15px; font-size: 1.1em; color: #1f2937;">¿No encuentras lo que buscas?</p>
                    <a href="pedidos.php?action=nuevo" class="btn btn-warning">📋 Ver Menú Completo</a>
                </div>

                <h2>Mis Últimos Pedidos</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $cliente_id = $_SESSION['cliente_id'];
                        $stmt = $pdo->prepare("
                            SELECT p.*
                            FROM pedidos p
                            WHERE p.cliente_id = ?
                            ORDER BY p.fecha_pedido DESC
                            LIMIT 5
                        ");
                        $stmt->execute([$cliente_id]);
                        while ($row = $stmt->fetch()) {
                            $estado_class = 'status-' . $row['estado'];
                            echo "<tr>";
                            echo "<td>#{$row['id']}</td>";
                            echo "<td>" . date('d/m/Y H:i', strtotime($row['fecha_pedido'])) . "</td>";
                            echo "<td>Bs. " . number_format($row['total'], 2) . "</td>";
                            echo "<td><span class='status-badge {$estado_class}'>{$row['estado']}</span></td>";
                            echo "<td>
                                <a href='pedidos.php?action=ver&id={$row['id']}' class='btn btn-primary' style='padding: 5px 10px; font-size: 12px;'>Ver</a>
                            </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            <?php else: ?>
                <h2>Bienvenido al Sistema de Gestión</h2>
                
                <div class="stats-grid">
                    <?php
                    // Estadísticas rápidas
                    $total_pedidos = $pdo->query("SELECT COUNT(*) as total FROM pedidos")->fetch()['total'];
                    $pedidos_pendientes = $pdo->query("SELECT COUNT(*) as total FROM pedidos WHERE estado = 'pendiente'")->fetch()['total'];
                    $total_productos = $pdo->query("SELECT COUNT(*) as total FROM productos")->fetch()['total'];
                    $stock_bajo = $pdo->query("SELECT COUNT(*) as total FROM productos WHERE stock_actual < stock_minimo")->fetch()['total'];
                    ?>
                    
                    <div class="stat-card">
                        <h3><?php echo $total_pedidos; ?></h3>
                        <p>Total Pedidos</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php echo $pedidos_pendientes; ?></h3>
                        <p>Pendientes</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php echo $total_productos; ?></h3>
                        <p>Productos</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php echo $stock_bajo; ?></h3>
                        <p>Stock Bajo</p>
                    </div>
                </div>

                <h2>Acciones Rápidas</h2>
                <div style="margin-top: 20px;">
                    <a href="pedidos.php?action=nuevo" class="btn btn-primary">➕ Nuevo Pedido</a>
                    <a href="inventario.php?action=nuevo" class="btn btn-success">➕ Agregar Producto</a>
                    <a href="distribucion.php" class="btn btn-warning">🚚 Ver Distribuciones</a>
                    <a href="admin.php" class="btn btn-primary">⚙️ Administración</a>
                </div>

                <h2>Últimos Pedidos</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("
                            SELECT p.*, c.nombre as cliente_nombre 
                            FROM pedidos p 
                            LEFT JOIN clientes c ON p.cliente_id = c.id 
                            ORDER BY p.fecha_pedido DESC 
                            LIMIT 5
                        ");
                        while ($row = $stmt->fetch()) {
                            $estado_class = 'status-' . $row['estado'];
                            echo "<tr>";
                            echo "<td>#{$row['id']}</td>";
                            echo "<td>{$row['cliente_nombre']}</td>";
                            echo "<td>" . date('d/m/Y H:i', strtotime($row['fecha_pedido'])) . "</td>";
                            echo "<td>Bs. " . number_format($row['total'], 2) . "</td>";
                            echo "<td><span class='status-badge {$estado_class}'>{$row['estado']}</span></td>";
                            echo "<td>
                                <a href='pedidos.php?action=ver&id={$row['id']}' class='btn btn-primary' style='padding: 5px 10px; font-size: 12px;'>Ver</a>
                            </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    </div>

    <footer style="text-align: center; padding: 20px; margin-top: 20px; color: #666;">
        <p>&copy; 2026 FastFood - Sistema de Gestión</p>
    </footer>

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
