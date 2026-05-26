-- Base de datos para FastFood Bolivia S.R.L.
-- Sistema de Gestión de Pedidos, Inventarios y Distribución

CREATE DATABASE IF NOT EXISTS fastfood_bolivia;
USE fastfood_bolivia;

-- Eliminar tablas existentes (en orden inverso por foreign keys)
DROP TABLE IF EXISTS movimientos_inventario;
DROP TABLE IF EXISTS distribuciones;
DROP TABLE IF EXISTS detalle_pedidos;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS clientes;

-- Tabla de clientes (debe crearse antes de usuarios para la foreign key)
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    direccion VARCHAR(200),
    telefono VARCHAR(20),
    email VARCHAR(100),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'gerente', 'operador', 'cliente') DEFAULT 'operador',
    cliente_id INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

-- Tabla de productos/inventario
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    categoria VARCHAR(50),
    precio_unitario DECIMAL(10, 2) NOT NULL,
    stock_actual INT DEFAULT 0,
    stock_minimo INT DEFAULT 10,
    unidad_medida VARCHAR(20) DEFAULT 'unidad',
    imagen VARCHAR(255) DEFAULT 'assets/images/productos/default.jpg',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de pedidos
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT,
    fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'procesando', 'enviando', 'entregado', 'cancelado') DEFAULT 'pendiente',
    total DECIMAL(10, 2) NOT NULL,
    observaciones TEXT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

-- Tabla de detalles de pedidos
CREATE TABLE detalle_pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- Tabla de distribuciones/envíos
CREATE TABLE distribuciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('preparando', 'en_ruta', 'entregado', 'cancelado') DEFAULT 'preparando',
    direccion_entrega VARCHAR(200),
    responsable VARCHAR(100),
    observaciones TEXT,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
);

-- Tabla de movimientos de inventario
CREATE TABLE movimientos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    tipo_movimiento ENUM('entrada', 'salida') NOT NULL,
    cantidad INT NOT NULL,
    fecha_movimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    descripcion TEXT,
    referencia_id INT,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- Insertar datos de prueba
INSERT INTO usuarios (nombre, usuario, password, rol) VALUES
('Administrador', 'admin', 'admin123', 'admin'),
('Gerente Ventas', 'gerente', 'gerente123', 'gerente'),
('Operador 1', 'operador', 'operador123', 'operador');

INSERT INTO clientes (nombre, direccion, telefono, email) VALUES
('Juan Perez', 'Av. Principal #123', '78945612', 'juan@email.com'),
('Maria Lopez', 'Calle Secundaria #456', '78945613', 'maria@email.com'),
('Carlos Mamani', 'Zona Sur #789', '78945614', 'carlos@email.com');

-- Insertar usuarios para clientes (vinculados a clientes)
INSERT INTO usuarios (nombre, usuario, password, rol, cliente_id) VALUES
('Juan Perez', 'juan', 'juan123', 'cliente', 1),
('Maria Lopez', 'maria', 'maria123', 'cliente', 2),
('Carlos Mamani', 'carlos', 'carlos123', 'cliente', 3);

INSERT INTO productos (nombre, descripcion, categoria, precio_unitario, stock_actual, stock_minimo, unidad_medida, imagen) VALUES
('Hamburguesa Simple', 'Hamburguesa con carne y lechuga', 'Comida', 25.00, 50, 10, 'unidad', 'assets/images/productos/hamburguesa.jpg'),
('Hamburguesa Doble', 'Hamburguesa con doble carne', 'Comida', 35.00, 30, 10, 'unidad', 'assets/images/productos/hamburguesa-doble.jpg'),
('Papas Fritas', 'Porción de papas fritas', 'Acompañamiento', 10.00, 100, 20, 'porción', 'assets/images/productos/papas.jpg'),
('Gaseosa 500ml', 'Bebida gaseosa', 'Bebida', 8.00, 80, 15, 'unidad', 'assets/images/productos/gaseosa.jpg'),
('Pollo Frito', 'Pollo frito con papas', 'Comida', 30.00, 40, 10, 'unidad', 'assets/images/productos/pollo.jpg'),
('Ensalada', 'Ensalada mixta', 'Comida', 15.00, 25, 10, 'unidad', 'assets/images/productos/ensalada.jpg');
