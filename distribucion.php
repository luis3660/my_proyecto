<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

$action = $_GET['action'] ?? 'listar';
$mensaje = '';

// Crear nueva distribución
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_distribucion'])) {
    $stmt = $pdo->prepare("INSERT INTO distribuciones (pedido_id, estado, direccion_entrega, responsable, observaciones) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['pedido_id'],
        'preparando',
        $_POST['direccion_entrega'],
        $_POST['responsable'],
        $_POST['observaciones']
    ]);
    
    // Actualizar estado del pedido
    $stmt = $pdo->prepare("UPDATE pedidos SET estado = 'enviando' WHERE id = ?");
    $stmt->execute([$_POST['pedido_id']]);
    
    $mensaje = 'Distribución creada exitosamente';
}

// Actualizar estado de distribución
if (isset($_GET['cambiar_estado'])) {
    $nuevo_estado = $_GET['nuevo_estado'];
    $distribucion_id = $_GET['cambiar_estado'];
    
    $stmt = $pdo->prepare("UPDATE distribuciones SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $distribucion_id]);
    
    // Si se entregó, actualizar el pedido
    if ($nuevo_estado == 'entregado') {
        $stmt = $pdo->prepare("SELECT pedido_id FROM distribuciones WHERE id = ?");
        $stmt->execute([$distribucion_id]);
        $dist = $stmt->fetch();
        
        $stmt = $pdo->prepare("UPDATE pedidos SET estado = 'entregado' WHERE id = ?");
        $stmt->execute([$dist['pedido_id']]);
    }
    
    $mensaje = 'Estado de distribución actualizado';
}

// Obtener pedidos sin distribución
$pedidos_sin_distribucion = $pdo->query("
    SELECT p.*, c.nombre as cliente_nombre, c.direccion as cliente_direccion 
    FROM pedidos p 
    LEFT JOIN clientes c ON p.cliente_id = c.id 
    WHERE p.estado IN ('procesando', 'enviando') 
    AND p.id NOT IN (SELECT pedido_id FROM distribuciones)
    ORDER BY p.fecha_pedido DESC
")->fetchAll();

// Obtener distribuciones
$distribuciones = $pdo->query("
    SELECT d.*, p.id as pedido_id, p.total as pedido_total, c.nombre as cliente_nombre 
    FROM distribuciones d 
    LEFT JOIN pedidos p ON d.pedido_id = p.id 
    LEFT JOIN clientes c ON p.cliente_id = c.id 
    ORDER BY d.fecha_envio DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Distribución - FastFood</title>
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
            <li><a href="distribucion.php" class="active">Distribución</a></li>
            <li><a href="admin.php">Administración</a></li>
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
                <p>Gestión de Distribución</p>
            </div>
        </header>

        <div class="content">
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <?php if ($action == 'nuevo'): ?>
                <h2>Nueva Distribución</h2>
                
                <?php if (empty($pedidos_sin_distribucion)): ?>
                    <div class="alert alert-info">No hay pedidos pendientes de distribución.</div>
                    <a href="distribucion.php" class="btn btn-primary">Volver</a>
                <?php else: ?>
                    <form method="POST">
                        <div class="form-group">
                            <label>Seleccionar Pedido:</label>
                            <select name="pedido_id" required onchange="cargarDatosPedido(this.value)">
                                <option value="">Seleccione un pedido</option>
                                <?php foreach ($pedidos_sin_distribucion as $pedido): ?>
                                    <option value="<?php echo $pedido['id']; ?>" 
                                            data-cliente="<?php echo $pedido['cliente_nombre']; ?>"
                                            data-direccion="<?php echo $pedido['cliente_direccion']; ?>"
                                            data-total="<?php echo $pedido['total']; ?>">
                                        #<?php echo $pedido['id']; ?> - <?php echo $pedido['cliente_nombre']; ?> (Bs. <?php echo number_format($pedido['total'], 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Dirección de Entrega:</label>
                            <input type="text" name="direccion_entrega" id="direccion_entrega" required>
                        </div>

                        <div class="form-group">
                            <label>Responsable de Entrega:</label>
                            <input type="text" name="responsable" required>
                        </div>

                        <div class="form-group">
                            <label>Observaciones:</label>
                            <textarea name="observaciones"></textarea>
                        </div>

                        <button type="submit" name="crear_distribucion" class="btn btn-primary">Crear Distribución</button>
                        <a href="distribucion.php" class="btn btn-warning">Cancelar</a>
                    </form>

                    <script>
                        function cargarDatosPedido(pedidoId) {
                            const select = document.querySelector('select[name="pedido_id"]');
                            const option = select.querySelector(`option[value="${pedidoId}"]`);
                            
                            if (option) {
                                document.getElementById('direccion_entrega').value = option.dataset.direccion || '';
                            }
                        }
                    </script>
                <?php endif; ?>

            <?php else: ?>
                <h2>Distribuciones Activas</h2>
                
                <div style="margin-bottom: 20px;">
                    <a href="distribucion.php?action=nuevo" class="btn btn-primary">Nueva Distribución</a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Dirección</th>
                            <th>Responsable</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($distribuciones)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">No hay distribuciones registradas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($distribuciones as $dist): ?>
                                <?php
                                $estado_class = 'status-' . $dist['estado'];
                                ?>
                                <tr>
                                    <td><?php echo $dist['id']; ?></td>
                                    <td>#<?php echo $dist['pedido_id']; ?></td>
                                    <td><?php echo $dist['cliente_nombre']; ?></td>
                                    <td><?php echo $dist['direccion_entrega']; ?></td>
                                    <td><?php echo $dist['responsable']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($dist['fecha_envio'])); ?></td>
                                    <td><span class="status-badge <?php echo $estado_class; ?>"><?php echo $dist['estado']; ?></span></td>
                                    <td class="actions">
                                        <?php if ($dist['estado'] == 'preparando'): ?>
                                            <a href="distribucion.php?cambiar_estado=<?php echo $dist['id']; ?>&nuevo_estado=en_ruta" class="btn btn-primary">En Ruta</a>
                                        <?php endif; ?>
                                        <?php if ($dist['estado'] == 'en_ruta'): ?>
                                            <a href="distribucion.php?cambiar_estado=<?php echo $dist['id']; ?>&nuevo_estado=entregado" class="btn btn-success">Entregado</a>
                                        <?php endif; ?>
                                        <a href="pedidos.php?action=ver&id=<?php echo $dist['pedido_id']; ?>" class="btn btn-warning">Ver Pedido</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
