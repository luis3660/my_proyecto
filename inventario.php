<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

$action = $_GET['action'] ?? 'listar';
$mensaje = '';

// Procesar formulario de nuevo producto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_producto'])) {
    // Manejar subida de imagen
    $imagen = 'assets/images/productos/default.jpg';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $upload_dir = 'assets/images/productos/';
        $file_extension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $file_name = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $upload_path)) {
                $imagen = $upload_path;
            }
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO productos (nombre, descripcion, categoria, precio_unitario, stock_actual, stock_minimo, unidad_medida, imagen) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['nombre'],
        $_POST['descripcion'],
        $_POST['categoria'],
        $_POST['precio_unitario'],
        $_POST['stock_actual'],
        $_POST['stock_minimo'],
        $_POST['unidad_medida'],
        $imagen
    ]);
    
    // Registrar movimiento de inventario
    $producto_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, descripcion) VALUES (?, 'entrada', ?, 'Ingreso inicial')");
    $stmt->execute([$producto_id, $_POST['stock_actual']]);
    
    $mensaje = 'Producto agregado exitosamente';
}

// Procesar formulario de edición de producto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar_producto'])) {
    $producto_id = $_POST['producto_id'];
    
    // Manejar subida de imagen si se proporciona una nueva
    $imagen = $_POST['imagen_actual'];
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $upload_dir = 'assets/images/productos/';
        $file_extension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $file_name = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $upload_path)) {
                $imagen = $upload_path;
            }
        }
    }
    
    $stmt = $pdo->prepare("UPDATE productos SET nombre = ?, descripcion = ?, categoria = ?, precio_unitario = ?, stock_actual = ?, stock_minimo = ?, unidad_medida = ?, imagen = ? WHERE id = ?");
    $stmt->execute([
        $_POST['nombre'],
        $_POST['descripcion'],
        $_POST['categoria'],
        $_POST['precio_unitario'],
        $_POST['stock_actual'],
        $_POST['stock_minimo'],
        $_POST['unidad_medida'],
        $imagen,
        $producto_id
    ]);
    
    $mensaje = 'Producto actualizado exitosamente';
}

// Actualizar stock
if (isset($_GET['actualizar_stock'])) {
    $producto_id = $_GET['actualizar_stock'];
    $cantidad = $_GET['cantidad'];
    $tipo = $_GET['tipo'];
    
    if ($tipo == 'entrada') {
        $stmt = $pdo->prepare("UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?");
        $stmt->execute([$cantidad, $producto_id]);
        $stmt = $pdo->prepare("INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, descripcion) VALUES (?, 'entrada', ?, 'Ajuste manual')");
        $stmt->execute([$producto_id, $cantidad]);
    } else {
        $stmt = $pdo->prepare("UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?");
        $stmt->execute([$cantidad, $producto_id]);
        $stmt = $pdo->prepare("INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, descripcion) VALUES (?, 'salida', ?, 'Ajuste manual')");
        $stmt->execute([$producto_id, $cantidad]);
    }
    
    $mensaje = 'Stock actualizado';
}

// Eliminar producto
if (isset($_GET['eliminar'])) {
    $producto_id = $_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->execute([$producto_id]);
    $mensaje = 'Producto eliminado';
}

$productos = $pdo->query("SELECT * FROM productos ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inventario - FastFood</title>
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
            <li><a href="inventario.php" class="active">Inventario</a></li>
            <li><a href="distribucion.php">Distribución</a></li>
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
                <p>Gestión de Inventario</p>
            </div>
        </header>

        <div class="content">
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <?php if ($action == 'nuevo'): ?>
                <h2>Agregar Nuevo Producto</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Nombre del Producto:</label>
                        <input type="text" name="nombre" required>
                    </div>

                    <div class="form-group">
                        <label>Descripción:</label>
                        <textarea name="descripcion"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Categoría:</label>
                        <select name="categoria" required>
                            <option value="Comida">Comida</option>
                            <option value="Bebida">Bebida</option>
                            <option value="Acompañamiento">Acompañamiento</option>
                            <option value="Postre">Postre</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Precio Unitario (Bs.):</label>
                        <input type="number" name="precio_unitario" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label>Stock Actual:</label>
                        <input type="number" name="stock_actual" required>
                    </div>

                    <div class="form-group">
                        <label>Stock Mínimo:</label>
                        <input type="number" name="stock_minimo" value="10" required>
                    </div>

                    <div class="form-group">
                        <label>Unidad de Medida:</label>
                        <select name="unidad_medida">
                            <option value="unidad">Unidad</option>
                            <option value="porción">Porción</option>
                            <option value="kg">Kilogramo</option>
                            <option value="litro">Litro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Imagen del Producto:</label>
                        <input type="file" name="imagen" accept="image/*">
                        <small style="color: #64748b; margin-top: 5px; display: block;">Formatos aceptados: JPG, PNG, GIF, WebP</small>
                    </div>

                    <button type="submit" name="guardar_producto" class="btn btn-primary">Guardar Producto</button>
                    <a href="inventario.php" class="btn btn-warning">Cancelar</a>
                </form>

            <?php elseif ($action == 'editar'):
                $producto_id = $_GET['id'];
                $producto = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
                $producto->execute([$producto_id]);
                $producto = $producto->fetch();
                ?>
                <h2>✏️ Editar Producto</h2>
                
                <?php if ($mensaje): ?>
                    <div class="alert alert-success"><?php echo $mensaje; ?></div>
                <?php endif; ?>
                
                <form method="POST" class="login-form" enctype="multipart/form-data" style="max-width: 600px;">
                    <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                    <input type="hidden" name="imagen_actual" value="<?php echo $producto['imagen']; ?>">
                    
                    <div class="form-group">
                        <label>Nombre del Producto:</label>
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Descripción:</label>
                        <textarea name="descripcion" rows="3" required><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Categoría:</label>
                        <select name="categoria" required>
                            <option value="Comida" <?php echo $producto['categoria'] == 'Comida' ? 'selected' : ''; ?>>Comida</option>
                            <option value="Bebida" <?php echo $producto['categoria'] == 'Bebida' ? 'selected' : ''; ?>>Bebida</option>
                            <option value="Acompañamiento" <?php echo $producto['categoria'] == 'Acompañamiento' ? 'selected' : ''; ?>>Acompañamiento</option>
                            <option value="Postre" <?php echo $producto['categoria'] == 'Postre' ? 'selected' : ''; ?>>Postre</option>
                            <option value="Otro" <?php echo $producto['categoria'] == 'Otro' ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Precio Unitario (Bs.):</label>
                        <input type="number" name="precio_unitario" step="0.01" value="<?php echo $producto['precio_unitario']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Stock Actual:</label>
                        <input type="number" name="stock_actual" value="<?php echo $producto['stock_actual']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Stock Mínimo:</label>
                        <input type="number" name="stock_minimo" value="<?php echo $producto['stock_minimo']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Unidad de Medida:</label>
                        <select name="unidad_medida">
                            <option value="unidad" <?php echo $producto['unidad_medida'] == 'unidad' ? 'selected' : ''; ?>>Unidad</option>
                            <option value="porción" <?php echo $producto['unidad_medida'] == 'porción' ? 'selected' : ''; ?>>Porción</option>
                            <option value="kg" <?php echo $producto['unidad_medida'] == 'kg' ? 'selected' : ''; ?>>Kilogramo</option>
                            <option value="litro" <?php echo $producto['unidad_medida'] == 'litro' ? 'selected' : ''; ?>>Litro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Imagen Actual:</label>
                        <?php if (isset($producto['imagen']) && $producto['imagen'] && $producto['imagen'] != 'assets/images/productos/default.jpg'): ?>
                            <img src="<?php echo $producto['imagen']; ?>" alt="Imagen actual" style="max-width: 100px; max-height: 100px; margin-bottom: 10px; border-radius: 8px;">
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Nueva Imagen del Producto (opcional):</label>
                        <input type="file" name="imagen" accept="image/*">
                        <small style="color: #64748b; margin-top: 5px; display: block;">Deje vacío para mantener la imagen actual. Formatos aceptados: JPG, PNG, GIF, WebP</small>
                    </div>

                    <button type="submit" name="editar_producto" class="btn btn-primary">Actualizar Producto</button>
                    <a href="inventario.php" class="btn btn-warning">Cancelar</a>
                </form>

            <?php elseif ($action == 'movimientos'): ?>
                <h2>Movimientos de Inventario</h2>
                
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("
                            SELECT mi.*, p.nombre as producto_nombre 
                            FROM movimientos_inventario mi 
                            LEFT JOIN productos p ON mi.producto_id = p.id 
                            ORDER BY mi.fecha_movimiento DESC 
                            LIMIT 50
                        ");
                        while ($row = $stmt->fetch()) {
                            $tipo_class = $row['tipo_movimiento'] == 'entrada' ? 'status-entregado' : 'status-cancelado';
                            echo "<tr>";
                            echo "<td>" . date('d/m/Y H:i', strtotime($row['fecha_movimiento'])) . "</td>";
                            echo "<td>{$row['producto_nombre']}</td>";
                            echo "<td><span class='status-badge {$tipo_class}'>{$row['tipo_movimiento']}</span></td>";
                            echo "<td>{$row['cantidad']}</td>";
                            echo "<td>{$row['descripcion']}</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <a href="inventario.php" class="btn btn-primary" style="margin-top: 20px;">Volver</a>

            <?php else: ?>
                <h2>📦 Inventario de Productos</h2>
                
                <div style="margin-bottom: 20px;">
                    <a href="inventario.php?action=nuevo" class="btn btn-primary">➕ Agregar Producto</a>
                    <a href="inventario.php?action=movimientos" class="btn btn-warning">📊 Ver Movimientos</a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <?php
                            $estado = $producto['stock_actual'] < $producto['stock_minimo'] ? 'Bajo' : 'OK';
                            $estado_class = $estado == 'Bajo' ? 'status-cancelado' : 'status-entregado';
                            ?>
                            <tr>
                                <td><?php echo $producto['id']; ?></td>
                                <td><?php echo $producto['nombre']; ?></td>
                                <td><?php echo $producto['categoria']; ?></td>
                                <td>Bs. <?php echo number_format($producto['precio_unitario'], 2); ?></td>
                                <td><?php echo $producto['stock_actual']; ?> <?php echo $producto['unidad_medida']; ?></td>
                                <td><span class="status-badge <?php echo $estado_class; ?>"><?php echo $estado; ?></span></td>
                                <td class="actions">
                                    <a href="inventario.php?action=editar&id=<?php echo $producto['id']; ?>" class="btn btn-primary">✏️ Editar</a>
                                    <a href="inventario.php?actualizar_stock=<?php echo $producto['id']; ?>&cantidad=10&tipo=entrada" class="btn btn-success">+10</a>
                                    <a href="inventario.php?actualizar_stock=<?php echo $producto['id']; ?>&cantidad=10&tipo=salida" class="btn btn-danger">-10</a>
                                    <a href="inventario.php?eliminar=<?php echo $producto['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Eliminar este producto?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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
