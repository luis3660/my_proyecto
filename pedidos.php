<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

$action = $_GET['action'] ?? 'listar';
$mensaje = '';

// Procesar formulario de nuevo pedido
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_pedido'])) {
    try {
        $pdo->beginTransaction();
        
        // Determinar cliente_id (si es cliente, usar su propio ID)
        if ($_SESSION['rol'] == 'cliente') {
            $cliente_id = $_SESSION['cliente_id'];
        } else {
            $cliente_id = $_POST['cliente_id'];
        }
        
        // Insertar pedido
        $stmt = $pdo->prepare("INSERT INTO pedidos (cliente_id, estado, total, observaciones) VALUES (?, ?, ?, ?)");
        $stmt->execute([$cliente_id, 'pendiente', $_POST['total'], $_POST['observaciones']]);
        $pedido_id = $pdo->lastInsertId();
        
        // Insertar detalles del pedido
        foreach ($_POST['productos'] as $producto_id => $cantidad) {
            if ($cantidad > 0) {
                $stmt = $pdo->prepare("SELECT precio_unitario FROM productos WHERE id = ?");
                $stmt->execute([$producto_id]);
                $producto = $stmt->fetch();
                
                $subtotal = $producto['precio_unitario'] * $cantidad;
                
                $stmt = $pdo->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$pedido_id, $producto_id, $cantidad, $producto['precio_unitario'], $subtotal]);
                
                // Actualizar inventario
                $stmt = $pdo->prepare("UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?");
                $stmt->execute([$cantidad, $producto_id]);
                
                // Registrar movimiento de inventario
                $stmt = $pdo->prepare("INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, descripcion, referencia_id) VALUES (?, 'salida', ?, 'Salida por pedido #{$pedido_id}', ?)");
                $stmt->execute([$producto_id, $cantidad, $pedido_id]);
            }
        }
        
        $pdo->commit();
        $mensaje = 'Pedido creado exitosamente';
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = 'Error al crear pedido: ' . $e->getMessage();
    }
}

// Actualizar estado de pedido
if (isset($_GET['cambiar_estado'])) {
    $nuevo_estado = $_GET['nuevo_estado'];
    $pedido_id = $_GET['cambiar_estado'];
    
    $stmt = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $pedido_id]);
    $mensaje = 'Estado del pedido actualizado';
}

// Obtener datos
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY nombre")->fetchAll();
$productos = $pdo->query("SELECT * FROM productos ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - FastFood</title>
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
            <li><a href="pedidos.php" class="active"><?php echo $_SESSION['rol'] == 'cliente' ? 'Mis Pedidos' : 'Pedidos'; ?></a></li>
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
                <p>Gestión de Pedidos</p>
            </div>
        </header>

        <div class="content">
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <?php if ($action == 'nuevo'): ?>
                <h2><?php echo $_SESSION['rol'] == 'cliente' ? 'Hacer Pedido' : 'Nuevo Pedido'; ?></h2>
                <form method="POST">
                    <?php if ($_SESSION['rol'] != 'cliente'): ?>
                    <div class="form-group">
                        <label>Cliente:</label>
                        <select name="cliente_id" required>
                            <option value="">Seleccione un cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['id']; ?>"><?php echo $cliente['nombre']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="cliente_id" value="<?php echo $_SESSION['cliente_id']; ?>">
                    <p><strong>Cliente:</strong> <?php echo $_SESSION['nombre']; ?></p>
                    <?php endif; ?>

                    <h3>🍔 Nuestro Menú</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin: 25px 0;">
                        <?php foreach ($productos as $producto): 
                            $imagen = $producto['imagen'] ?? 'assets/images/productos/default.jpg';
                        ?>
                            <div class="product-card">
                                <div style="background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%); border-radius: 12px; padding: 15px; margin-bottom: 15px; overflow: hidden; text-align: center;">
                                    <img src="<?php echo $imagen; ?>" alt="<?php echo $producto['nombre']; ?>" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px;" onerror="this.style.display='none';">
                                </div>
                                <h4><?php echo $producto['nombre']; ?></h4>
                                <p style="color: #636e72; font-size: 0.9em; margin-bottom: 10px;"><?php echo $producto['descripcion'] ?: 'Delicioso producto de FastFood'; ?></p>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <span class="price">Bs. <?php echo number_format($producto['precio_unitario'], 2); ?></span>
                                    <span class="stock">Stock: <?php echo $producto['stock_actual']; ?></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <label style="flex: 1;">Cantidad:</label>
                                    <input type="number" name="productos[<?php echo $producto['id']; ?>]" min="0" max="<?php echo $producto['stock_actual']; ?>" value="0" style="width: 80px; padding: 8px; border: 2px solid #eee; border-radius: 8px;">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-summary">
                        <h3>💰 Total del Pedido</h3>
                        <div class="total">Bs. <span id="total-display">0.00</span></div>
                        <input type="hidden" name="total" id="total" step="0.01" readonly>
                    </div>

                    <div class="form-group">
                        <label>Observaciones:</label>
                        <textarea name="observaciones"></textarea>
                    </div>

                    <button type="submit" name="guardar_pedido" class="btn btn-primary">Guardar Pedido</button>
                    <a href="pedidos.php" class="btn btn-warning">Cancelar</a>
                </form>

                <script>
                    // Calcular total automáticamente
                    document.querySelectorAll('input[name^="productos"]').forEach(input => {
                        input.addEventListener('change', calcularTotal);
                        input.addEventListener('input', calcularTotal);
                    });

                    function calcularTotal() {
                        let total = 0;
                        document.querySelectorAll('input[name^="productos"]').forEach(input => {
                            const card = input.closest('.product-card');
                            const priceElement = card.querySelector('.price');
                            const precio = parseFloat(priceElement.textContent.replace('Bs. ', '').replace(',', ''));
                            const cantidad = parseInt(input.value) || 0;
                            total += precio * cantidad;
                        });
                        document.getElementById('total').value = total.toFixed(2);
                        document.getElementById('total-display').textContent = total.toFixed(2);
                    }
                </script>

            <?php elseif ($action == 'ver' && isset($_GET['id'])): ?>
                <?php
                $pedido_id = $_GET['id'];
                $stmt = $pdo->prepare("SELECT p.*, c.nombre as cliente_nombre FROM pedidos p LEFT JOIN clientes c ON p.cliente_id = c.id WHERE p.id = ?");
                $stmt->execute([$pedido_id]);
                $pedido = $stmt->fetch();
                
                $stmt = $pdo->prepare("SELECT dp.*, pr.nombre as producto_nombre FROM detalle_pedidos dp LEFT JOIN productos pr ON dp.producto_id = pr.id WHERE dp.pedido_id = ?");
                $stmt->execute([$pedido_id]);
                $detalles = $stmt->fetchAll();
                ?>
                <h2>Detalle del Pedido #<?php echo $pedido['id']; ?></h2>
                
                <div style="margin-bottom: 20px;">
                    <p><strong>Cliente:</strong> <?php echo $pedido['cliente_nombre']; ?></p>
                    <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></p>
                    <p><strong>Estado:</strong> <span class="status-badge status-<?php echo $pedido['estado']; ?>"><?php echo $pedido['estado']; ?></span></p>
                    <p><strong>Total:</strong> Bs. <?php echo number_format($pedido['total'], 2); ?></p>
                    <p><strong>Observaciones:</strong> <?php echo $pedido['observaciones'] ?: 'Ninguna'; ?></p>
                </div>

                <h3>Productos del Pedido</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalles as $detalle): ?>
                            <tr>
                                <td><?php echo $detalle['producto_nombre']; ?></td>
                                <td><?php echo $detalle['cantidad']; ?></td>
                                <td>Bs. <?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                                <td>Bs. <?php echo number_format($detalle['subtotal'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($_SESSION['rol'] != 'cliente'): ?>
                <div style="margin-top: 20px;">
                    <h3>Cambiar Estado</h3>
                    <a href="pedidos.php?cambiar_estado=<?php echo $pedido_id; ?>&nuevo_estado=procesando" class="btn btn-warning">Procesando</a>
                    <a href="pedidos.php?cambiar_estado=<?php echo $pedido_id; ?>&nuevo_estado=enviando" class="btn btn-primary">Enviando</a>
                    <a href="pedidos.php?cambiar_estado=<?php echo $pedido_id; ?>&nuevo_estado=entregado" class="btn btn-success">Entregado</a>
                    <a href="pedidos.php?cambiar_estado=<?php echo $pedido_id; ?>&nuevo_estado=cancelado" class="btn btn-danger">Cancelar</a>
                </div>
                <?php endif; ?>

                <a href="pedidos.php" class="btn btn-primary" style="margin-top: 20px;">Volver</a>

            <?php else: ?>
                <h2><?php echo $_SESSION['rol'] == 'cliente' ? '📦 Mis Pedidos' : '📦 Lista de Pedidos'; ?></h2>
                
                <div style="margin-bottom: 20px;">
                    <a href="pedidos.php?action=nuevo" class="btn btn-primary"><?php echo $_SESSION['rol'] == 'cliente' ? '➕ Hacer Pedido' : '➕ Nuevo Pedido'; ?></a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <?php if ($_SESSION['rol'] != 'cliente'): ?>
                            <th>Cliente</th>
                            <?php endif; ?>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($_SESSION['rol'] == 'cliente') {
                            $stmt = $pdo->prepare("
                                SELECT p.* 
                                FROM pedidos p 
                                WHERE p.cliente_id = ?
                                ORDER BY p.fecha_pedido DESC
                            ");
                            $stmt->execute([$_SESSION['cliente_id']]);
                        } else {
                            $stmt = $pdo->query("
                                SELECT p.*, c.nombre as cliente_nombre 
                                FROM pedidos p 
                                LEFT JOIN clientes c ON p.cliente_id = c.id 
                                ORDER BY p.fecha_pedido DESC
                            ");
                        }
                        
                        while ($row = $stmt->fetch()) {
                            $estado_class = 'status-' . $row['estado'];
                            echo "<tr>";
                            echo "<td>#{$row['id']}</td>";
                            if ($_SESSION['rol'] != 'cliente') {
                                echo "<td>{$row['cliente_nombre']}</td>";
                            }
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
