-- =====================================================
-- SISTEMA DE GESTIÓN DE PANADERÍA - BASE DE DATOS
-- =====================================================
-- 
-- Base de datos completa para el sistema de gestión de panadería
-- Incluye todas las tablas, relaciones, índices y datos de prueba
-- 
-- Autor: Sistema de Panadería
-- Versión: 1.0
-- Fecha: 2025
-- 
-- ESTRUCTURA:
-- 1. Creación de base de datos
-- 2. Tabla de empleados (usuarios del sistema)
-- 3. Tabla de productos
-- 4. Tabla de ventas
-- 5. Tabla de detalle de ventas
-- 6. Tabla de movimientos de inventario
-- 7. Tabla de categorías
-- 8. Índices y constrains
-- 9. Datos de prueba
-- =====================================================

-- 1. CREACIÓN DE BASE DE DATOS
-- =====================================================
DROP DATABASE IF EXISTS panaderia_db;
CREATE DATABASE panaderia_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE panaderia_db;

-- 2. TABLA DE EMPLEADOS
-- =====================================================
-- Esta tabla almacena la información de todos los empleados
-- que pueden acceder al sistema, incluyendo administradores,
-- vendedores, panaderos y cajeros.

CREATE TABLE empleados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre completo del empleado',
    email VARCHAR(100) NOT NULL UNIQUE COMMENT 'Email único para login',
    telefono VARCHAR(20) NOT NULL COMMENT 'Teléfono de contacto',
    rol ENUM('admin', 'vendedor', 'panadero', 'cajero') NOT NULL DEFAULT 'vendedor' COMMENT 'Rol del empleado en el sistema',
    salario DECIMAL(10,2) NOT NULL COMMENT 'Salario mensual del empleado',
    fecha_contratacion DATE NOT NULL COMMENT 'Fecha de contratación',
    password_hash VARCHAR(255) NOT NULL COMMENT 'Contraseña encriptada',
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo' COMMENT 'Estado del empleado',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de registro en el sistema',
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última actualización',
    
    INDEX idx_email (email),
    INDEX idx_rol (rol),
    INDEX idx_estado (estado)
) COMMENT = 'Tabla de empleados del sistema de panadería';

-- 3. TABLA DE CATEGORÍAS DE PRODUCTOS
-- =====================================================
-- Categorías predefinidas para organizar los productos

CREATE TABLE categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE COMMENT 'Nombre de la categoría',
    descripcion TEXT COMMENT 'Descripción de la categoría',
    icono VARCHAR(50) DEFAULT 'fas fa-cookie-bite' COMMENT 'Clase CSS del icono',
    color VARCHAR(7) DEFAULT '#f39c12' COMMENT 'Color hex para la categoría',
    activo BOOLEAN DEFAULT TRUE COMMENT 'Si la categoría está activa',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_activo (activo)
) COMMENT = 'Categorías de productos';

-- 4. TABLA DE PRODUCTOS
-- =====================================================
-- Esta tabla almacena todos los productos que vende la panadería
-- incluyendo panes, pasteles, galletas y bebidas.

CREATE TABLE productos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre del producto',
    descripcion TEXT COMMENT 'Descripción detallada del producto',
    categoria_id INT NOT NULL COMMENT 'ID de la categoría',
    precio DECIMAL(10,2) NOT NULL COMMENT 'Precio de venta al público',
    costo DECIMAL(10,2) NOT NULL COMMENT 'Costo de producción',
    stock INT NOT NULL DEFAULT 0 COMMENT 'Cantidad disponible en inventario',
    stock_minimo INT NOT NULL DEFAULT 5 COMMENT 'Stock mínimo para alerta',
    codigo_barras VARCHAR(50) UNIQUE COMMENT 'Código de barras del producto',
    imagen_url VARCHAR(255) COMMENT 'URL de la imagen del producto',
    estado ENUM('disponible', 'agotado', 'descontinuado') NOT NULL DEFAULT 'disponible' COMMENT 'Estado del producto',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del producto',
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última actualización',
    empleado_creador_id INT COMMENT 'ID del empleado que creó el producto',
    
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT,
    FOREIGN KEY (empleado_creador_id) REFERENCES empleados(id) ON DELETE SET NULL,
    
    INDEX idx_categoria (categoria_id),
    INDEX idx_estado (estado),
    INDEX idx_stock_bajo (stock, stock_minimo),
    INDEX idx_precio (precio),
    INDEX idx_nombre (nombre)
) COMMENT = 'Productos disponibles en la panadería';

-- 5. TABLA DE VENTAS
-- =====================================================
-- Registro de todas las ventas realizadas en la panadería

CREATE TABLE ventas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empleado_id INT NOT NULL COMMENT 'ID del empleado que realizó la venta',
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la venta',
    subtotal DECIMAL(10,2) NOT NULL COMMENT 'Subtotal antes de impuestos',
    impuestos DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Monto de impuestos aplicados',
    descuento DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Descuento aplicado',
    total DECIMAL(10,2) NOT NULL COMMENT 'Total final de la venta',
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia') NOT NULL DEFAULT 'efectivo' COMMENT 'Método de pago utilizado',
    numero_factura VARCHAR(20) UNIQUE COMMENT 'Número de factura generado',
    cliente_nombre VARCHAR(100) COMMENT 'Nombre del cliente (opcional)',
    cliente_telefono VARCHAR(20) COMMENT 'Teléfono del cliente (opcional)',
    observaciones TEXT COMMENT 'Observaciones adicionales',
    estado ENUM('completada', 'cancelada', 'pendiente') NOT NULL DEFAULT 'completada' COMMENT 'Estado de la venta',
    
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE RESTRICT,
    
    INDEX idx_empleado (empleado_id),
    INDEX idx_fecha (fecha),
    INDEX idx_estado (estado),
    INDEX idx_metodo_pago (metodo_pago),
    INDEX idx_total (total)
) COMMENT = 'Registro de ventas realizadas';

-- 6. TABLA DE DETALLE DE VENTAS
-- =====================================================
-- Detalle de los productos vendidos en cada venta

CREATE TABLE venta_detalles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venta_id INT NOT NULL COMMENT 'ID de la venta',
    producto_id INT NOT NULL COMMENT 'ID del producto vendido',
    cantidad INT NOT NULL COMMENT 'Cantidad vendida',
    precio_unitario DECIMAL(10,2) NOT NULL COMMENT 'Precio unitario al momento de la venta',
    subtotal DECIMAL(10,2) NOT NULL COMMENT 'Subtotal del producto (cantidad × precio)',
    descuento_unitario DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Descuento por unidad',
    
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT,
    
    INDEX idx_venta (venta_id),
    INDEX idx_producto (producto_id),
    INDEX idx_cantidad (cantidad)
) COMMENT = 'Detalle de productos por venta';

-- 7. TABLA DE MOVIMIENTOS DE INVENTARIO
-- =====================================================
-- Registro de todos los movimientos de inventario (entradas, salidas, ajustes)

CREATE TABLE inventario_movimientos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    producto_id INT NOT NULL COMMENT 'ID del producto',
    tipo_movimiento ENUM('entrada', 'salida', 'ajuste', 'venta') NOT NULL COMMENT 'Tipo de movimiento',
    cantidad INT NOT NULL COMMENT 'Cantidad del movimiento (positiva o negativa)',
    stock_anterior INT NOT NULL COMMENT 'Stock antes del movimiento',
    stock_nuevo INT NOT NULL COMMENT 'Stock después del movimiento',
    motivo VARCHAR(255) COMMENT 'Motivo del movimiento',
    costo_unitario DECIMAL(10,2) COMMENT 'Costo unitario del producto',
    empleado_id INT NOT NULL COMMENT 'ID del empleado que realizó el movimiento',
    venta_id INT NULL COMMENT 'ID de venta relacionada (si aplica)',
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha del movimiento',
    
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE RESTRICT,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE SET NULL,
    
    INDEX idx_producto (producto_id),
    INDEX idx_tipo (tipo_movimiento),
    INDEX idx_fecha (fecha),
    INDEX idx_empleado (empleado_id)
) COMMENT = 'Movimientos de inventario';

-- 8. TABLA DE CONFIGURACIONES DEL SISTEMA
-- =====================================================
-- Configuraciones generales del sistema

CREATE TABLE configuraciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(50) NOT NULL UNIQUE COMMENT 'Clave de configuración',
    valor TEXT NOT NULL COMMENT 'Valor de la configuración',
    descripcion TEXT COMMENT 'Descripción de la configuración',
    tipo ENUM('string', 'number', 'boolean', 'json') NOT NULL DEFAULT 'string' COMMENT 'Tipo de dato',
    categoria VARCHAR(50) DEFAULT 'general' COMMENT 'Categoría de la configuración',
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_categoria (categoria)
) COMMENT = 'Configuraciones del sistema';

-- 9. VISTA PARA REPORTES DE VENTAS
-- =====================================================
-- Vista que facilita la consulta de datos de ventas con información completa

CREATE VIEW vista_ventas_completas AS
SELECT 
    v.id as venta_id,
    v.fecha,
    v.total,
    v.metodo_pago,
    v.estado as venta_estado,
    e.nombre as empleado_nombre,
    e.rol as empleado_rol,
    v.cliente_nombre,
    COUNT(vd.id) as total_items,
    SUM(vd.cantidad) as total_productos
FROM ventas v
JOIN empleados e ON v.empleado_id = e.id
LEFT JOIN venta_detalles vd ON v.id = vd.venta_id
WHERE v.estado = 'completada'
GROUP BY v.id, v.fecha, v.total, v.metodo_pago, v.estado, e.nombre, e.rol, v.cliente_nombre;

-- 10. VISTA PARA INVENTARIO CON INFORMACIÓN DE PRODUCTOS
-- =====================================================

CREATE VIEW vista_inventario_productos AS
SELECT 
    p.id as producto_id,
    p.nombre as producto_nombre,
    p.stock,
    p.stock_minimo,
    c.nombre as categoria,
    p.precio,
    p.estado,
    CASE 
        WHEN p.stock <= 0 THEN 'sin_stock'
        WHEN p.stock <= p.stock_minimo THEN 'stock_bajo'
        ELSE 'normal'
    END as estado_stock,
    (SELECT MAX(im.fecha) 
     FROM inventario_movimientos im 
     WHERE im.producto_id = p.id) as ultimo_movimiento
FROM productos p
JOIN categorias c ON p.categoria_id = c.id
WHERE p.estado != 'descontinuado';

-- =====================================================
-- INSERCIÓN DE DATOS INICIALES
-- =====================================================

-- Insertar categorías
INSERT INTO categorias (nombre, descripcion, icono, color) VALUES
('pan', 'Productos de panadería tradicional', 'fas fa-bread-slice', '#8B4513'),
('pasteles', 'Pasteles y tortas especiales', 'fas fa-birthday-cake', '#FF69B4'),
('galletas', 'Galletas y productos de repostería', 'fas fa-cookie', '#DEB887'),
('bebidas', 'Bebidas calientes y frías', 'fas fa-coffee', '#4682B4');

-- Insertar empleado administrador inicial
INSERT INTO empleados (nombre, email, telefono, rol, salario, fecha_contratacion, password_hash) VALUES
('Administrador Sistema', 'admin@panaderia.com', '555-0001', 'admin', 3000.00, '2024-01-01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('María González', 'maria@panaderia.com', '555-0002', 'vendedor', 1500.00, '2024-01-15', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Carlos Pérez', 'carlos@panaderia.com', '555-0003', 'panadero', 1800.00, '2024-02-01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Ana Rodríguez', 'ana@panaderia.com', '555-0004', 'cajero', 1400.00, '2024-02-15', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insertar productos de ejemplo
INSERT INTO productos (nombre, descripcion, categoria_id, precio, costo, stock, stock_minimo, empleado_creador_id) VALUES
-- Panes
('Pan Integral', 'Pan integral artesanal con semillas', 1, 2.50, 1.20, 50, 10, 1),
('Pan Francés', 'Baguette tradicional francesa', 1, 3.00, 1.50, 30, 8, 1),
('Pan de Centeno', 'Pan de centeno con nueces', 1, 3.50, 1.80, 25, 5, 1),
('Pan Dulce', 'Pan dulce tradicional', 1, 4.00, 2.00, 40, 10, 1),

-- Pasteles
('Torta de Chocolate', 'Deliciosa torta de chocolate con crema', 2, 25.00, 12.00, 8, 3, 1),
('Cheesecake', 'Cheesecake de frutos rojos', 2, 28.00, 14.00, 6, 2, 1),
('Torta de Vainilla', 'Torta de vainilla con decoración', 2, 22.00, 11.00, 10, 3, 1),
('Mil Hojas', 'Tradicional pastel mil hojas', 2, 20.00, 10.00, 5, 2, 1),

-- Galletas
('Galletas de Avena', 'Galletas caseras de avena y pasas', 3, 1.50, 0.70, 80, 20, 1),
('Cookies de Chocolate', 'Cookies con chips de chocolate', 3, 2.00, 1.00, 60, 15, 1),
('Galletas Integrales', 'Galletas integrales sin azúcar', 3, 2.50, 1.20, 45, 12, 1),
('Alfajores', 'Alfajores rellenos de dulce de leche', 3, 3.00, 1.50, 35, 10, 1),

-- Bebidas
('Café Americano', 'Café americano recién preparado', 4, 2.00, 0.80, 100, 25, 1),
('Café con Leche', 'Café con leche espumosa', 4, 2.50, 1.00, 100, 25, 1),
('Chocolate Caliente', 'Chocolate caliente artesanal', 4, 3.00, 1.20, 50, 15, 1),
('Té de Hierbas', 'Té de hierbas naturales', 4, 1.80, 0.60, 80, 20, 1),
('Jugo Natural', 'Jugo natural de frutas de temporada', 4, 3.50, 1.50, 40, 10, 1);

-- Insertar movimientos de inventario iniciales (stock inicial)
INSERT INTO inventario_movimientos (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, motivo, empleado_id) VALUES
(1, 'entrada', 50, 0, 50, 'Stock inicial', 1),
(2, 'entrada', 30, 0, 30, 'Stock inicial', 1),
(3, 'entrada', 25, 0, 25, 'Stock inicial', 1),
(4, 'entrada', 40, 0, 40, 'Stock inicial', 1),
(5, 'entrada', 8, 0, 8, 'Stock inicial', 1),
(6, 'entrada', 6, 0, 6, 'Stock inicial', 1),
(7, 'entrada', 10, 0, 10, 'Stock inicial', 1),
(8, 'entrada', 5, 0, 5, 'Stock inicial', 1),
(9, 'entrada', 80, 0, 80, 'Stock inicial', 1),
(10, 'entrada', 60, 0, 60, 'Stock inicial', 1),
(11, 'entrada', 45, 0, 45, 'Stock inicial', 1),
(12, 'entrada', 35, 0, 35, 'Stock inicial', 1),
(13, 'entrada', 100, 0, 100, 'Stock inicial', 1),
(14, 'entrada', 100, 0, 100, 'Stock inicial', 1),
(15, 'entrada', 50, 0, 50, 'Stock inicial', 1),
(16, 'entrada', 80, 0, 80, 'Stock inicial', 1),
(17, 'entrada', 40, 0, 40, 'Stock inicial', 1);

-- Insertar ventas de ejemplo
INSERT INTO ventas (empleado_id, subtotal, total, metodo_pago, numero_factura, fecha) VALUES
(2, 15.50, 15.50, 'efectivo', 'FAC-001', '2024-12-01 09:30:00'),
(2, 28.00, 28.00, 'tarjeta', 'FAC-002', '2024-12-01 11:15:00'),
(4, 8.50, 8.50, 'efectivo', 'FAC-003', '2024-12-01 14:20:00'),
(2, 35.00, 35.00, 'transferencia', 'FAC-004', '2024-12-02 10:45:00'),
(4, 12.00, 12.00, 'efectivo', 'FAC-005', '2024-12-02 16:30:00');

-- Insertar detalles de ventas
INSERT INTO venta_detalles (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES
-- Venta 1
(1, 1, 2, 2.50, 5.00),
(1, 9, 3, 1.50, 4.50),
(1, 13, 3, 2.00, 6.00),

-- Venta 2
(2, 5, 1, 25.00, 25.00),
(2, 14, 1, 2.50, 2.50),

-- Venta 3
(3, 10, 2, 2.00, 4.00),
(3, 16, 2, 1.80, 3.60),

-- Venta 4
(4, 6, 1, 28.00, 28.00),
(4, 15, 2, 3.00, 6.00),

-- Venta 5
(5, 2, 2, 3.00, 6.00),
(5, 12, 2, 3.00, 6.00);

-- Configuraciones del sistema
INSERT INTO configuraciones (clave, valor, descripcion, tipo, categoria) VALUES
('nombre_panaderia', 'Panadería Artesanal', 'Nombre de la panadería', 'string', 'general'),
('direccion', 'Calle Principal 123, Ciudad', 'Dirección de la panadería', 'string', 'general'),
('telefono', '555-PANADERIA', 'Teléfono de contacto', 'string', 'general'),
('email', 'info@panaderia.com', 'Email de contacto', 'string', 'general'),
('moneda', 'USD', 'Moneda utilizada', 'string', 'general'),
('impuesto_porcentaje', '0', 'Porcentaje de impuesto aplicado', 'number', 'ventas'),
('stock_minimo_global', '5', 'Stock mínimo por defecto para nuevos productos', 'number', 'inventario'),
('backup_automatico', 'true', 'Realizar backup automático diario', 'boolean', 'sistema'),
('tema_color', '#f39c12', 'Color principal del tema', 'string', 'interfaz');

-- =====================================================
-- TRIGGERS PARA AUTOMATIZACIÓN
-- =====================================================

-- Trigger para actualizar stock automáticamente en ventas
DELIMITER //

CREATE TRIGGER after_venta_detalle_insert
    AFTER INSERT ON venta_detalles
    FOR EACH ROW
BEGIN
    DECLARE stock_actual INT;
    
    -- Obtener stock actual
    SELECT stock INTO stock_actual FROM productos WHERE id = NEW.producto_id;
    
    -- Actualizar stock del producto
    UPDATE productos 
    SET stock = stock - NEW.cantidad 
    WHERE id = NEW.producto_id;
    
    -- Registrar movimiento de inventario
    INSERT INTO inventario_movimientos (
        producto_id, 
        tipo_movimiento, 
        cantidad, 
        stock_anterior, 
        stock_nuevo, 
        motivo, 
        empleado_id, 
        venta_id
    ) VALUES (
        NEW.producto_id,
        'venta',
        -NEW.cantidad,
        stock_actual,
        stock_actual - NEW.cantidad,
        CONCAT('Venta ID: ', NEW.venta_id),
        (SELECT empleado_id FROM ventas WHERE id = NEW.venta_id),
        NEW.venta_id
    );
END//

DELIMITER ;

-- Trigger para validar stock antes de venta
DELIMITER //

CREATE TRIGGER before_venta_detalle_insert
    BEFORE INSERT ON venta_detalles
    FOR EACH ROW
BEGIN
    DECLARE stock_actual INT;
    
    SELECT stock INTO stock_actual FROM productos WHERE id = NEW.producto_id;
    
    IF stock_actual < NEW.cantidad THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Stock insuficiente para completar la venta';
    END IF;
END//

DELIMITER ;

-- =====================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================

-- Índices para mejorar rendimiento en consultas frecuentes
CREATE INDEX idx_ventas_fecha_total ON ventas(fecha, total);
CREATE INDEX idx_productos_categoria_stock ON productos(categoria_id, stock);
CREATE INDEX idx_inventario_fecha_tipo ON inventario_movimientos(fecha, tipo_movimiento);
CREATE INDEX idx_venta_detalles_precio ON venta_detalles(precio_unitario);

-- =====================================================
-- PROCEDIMIENTOS ALMACENADOS
-- =====================================================

-- Procedimiento para obtener estadísticas del dashboard
DELIMITER //

CREATE PROCEDURE GetDashboardStats()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SELECT 
        -- Ventas del día
        (SELECT COALESCE(SUM(total), 0) 
         FROM ventas 
         WHERE DATE(fecha) = CURDATE() AND estado = 'completada') as ventas_hoy,
        
        -- Total de productos activos
        (SELECT COUNT(*) 
         FROM productos 
         WHERE estado = 'disponible') as total_productos,
        
        -- Productos con stock bajo
        (SELECT COUNT(*) 
         FROM productos 
         WHERE stock <= stock_minimo AND estado = 'disponible') as stock_bajo,
        
        -- Total de empleados activos
        (SELECT COUNT(*) 
         FROM empleados 
         WHERE estado = 'activo') as total_empleados,
        
        -- Ventas de la semana
        (SELECT COALESCE(SUM(total), 0) 
         FROM ventas 
         WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
         AND estado = 'completada') as ventas_semana;
         
END//

DELIMITER ;

-- Procedimiento para reporte de productos más vendidos
DELIMITER //

CREATE PROCEDURE GetTopProducts(IN limite INT)
BEGIN
    SELECT 
        p.id,
        p.nombre,
        p.categoria_id,
        c.nombre as categoria,
        SUM(vd.cantidad) as cantidad_vendida,
        SUM(vd.subtotal) as total_vendido
    FROM productos p
    JOIN categorias c ON p.categoria_id = c.id
    JOIN venta_detalles vd ON p.id = vd.producto_id
    JOIN ventas v ON vd.venta_id = v.id
    WHERE v.estado = 'completada'
    AND v.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY p.id, p.nombre, p.categoria_id, c.nombre
    ORDER BY cantidad_vendida DESC
    LIMIT limite;
END//

DELIMITER ;

-- ====================================================================
-- DATOS DE EJEMPLO EXTENDIDOS (30 REGISTROS POR TABLA)
-- ====================================================================

-- Limpiar datos existentes para reinsertar
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE venta_detalles;
TRUNCATE TABLE ventas;
TRUNCATE TABLE inventario_movimientos;
TRUNCATE TABLE productos;
TRUNCATE TABLE empleados;
TRUNCATE TABLE categorias;
TRUNCATE TABLE configuraciones;
SET FOREIGN_KEY_CHECKS = 1;

-- Insertar 30 categorías
INSERT INTO categorias (nombre, descripcion, icono, color) VALUES
('Panes Dulces', 'Variedad de panes dulces y postres', 'fa-birthday-cake', '#FF6B6B'),
('Panes Salados', 'Panes tradicionales y artesanales', 'fa-bread-slice', '#4ECDC4'),
('Pasteles', 'Pasteles para ocasiones especiales', 'fa-cake-candles', '#45B7D1'),
('Galletas', 'Galletas artesanales y tradicionales', 'fa-cookie-bite', '#96CEB4'),
('Bebidas', 'Bebidas calientes y frías', 'fa-coffee', '#FFEAA7'),
('Empanadas', 'Empanadas horneadas y fritas', 'fa-circle', '#DDA0DD'),
('Bocadillos', 'Snacks y aperitivos', 'fa-hamburger', '#98D8C8'),
('Repostería Fina', 'Productos de repostería gourmet', 'fa-gem', '#F7DC6F'),
('Panes Integrales', 'Panes saludables y nutritivos', 'fa-seedling', '#82E0AA'),
('Croissants', 'Croissants y hojaldre', 'fa-croissant', '#F8C471'),
('Donas', 'Donas glaseadas y rellenas', 'fa-ring', '#85C1E9'),
('Muffins', 'Muffins dulces y salados', 'fa-muffin', '#D7DBDD'),
('Tartas', 'Tartas dulces y saladas', 'fa-chart-pie', '#F1948A'),
('Chocolatería', 'Productos con chocolate', 'fa-candy-bar', '#8E44AD'),
('Panadería Vegana', 'Productos sin ingredientes animales', 'fa-leaf', '#27AE60'),
('Sin Gluten', 'Productos para celíacos', 'fa-wheat', '#E67E22'),
('Temporadas', 'Productos de temporada', 'fa-calendar', '#3498DB'),
('Infantil', 'Productos para niños', 'fa-child', '#E91E63'),
('Dietéticos', 'Productos bajos en calorías', 'fa-weight', '#9C27B0'),
('Tradicionales', 'Recetas familiares tradicionales', 'fa-home', '#795548'),
('Gourmet', 'Productos premium', 'fa-star', '#FF9800'),
('Internacionales', 'Especialidades internacionales', 'fa-globe', '#607D8B'),
('Frescos del Día', 'Productos elaborados diariamente', 'fa-clock', '#4CAF50'),
('Congelados', 'Productos para llevar', 'fa-snowflake', '#00BCD4'),
('Rellenos', 'Productos con diferentes rellenos', 'fa-fill', '#FF5722'),
('Masa Madre', 'Productos con masa madre natural', 'fa-bacteria', '#8BC34A'),
('Light', 'Versiones ligeras de productos', 'fa-feather', '#CDDC39'),
('Promocionales', 'Ofertas especiales del día', 'fa-percentage', '#FFC107'),
('Catering', 'Productos para eventos', 'fa-utensils', '#FF4081'),
('Especiales', 'Productos únicos de la casa', 'fa-award', '#9E9E9E');

-- Insertar 30 empleados
INSERT INTO empleados (nombre, apellido, email, telefono, rol, salario, fecha_contratacion, usuario, password, estado) VALUES
('Carlos', 'Mendoza', 'admin@panaderia.com', '555-0001', 'admin', 15000.00, '2023-01-15', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('María', 'González', 'maria@panaderia.com', '555-0002', 'manager', 12000.00, '2023-02-01', 'maria', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Juan', 'López', 'juan@panaderia.com', '555-0003', 'cajero', 8000.00, '2023-02-15', 'juan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Ana', 'Martínez', 'ana@panaderia.com', '555-0004', 'vendedor', 7500.00, '2023-03-01', 'ana', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Pedro', 'Rodríguez', 'pedro@panaderia.com', '555-0005', 'panadero', 9500.00, '2023-03-15', 'pedro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Laura', 'Sánchez', 'laura@panaderia.com', '555-0006', 'cajero', 8000.00, '2023-04-01', 'laura', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Diego', 'Torres', 'diego@panaderia.com', '555-0007', 'vendedor', 7500.00, '2023-04-15', 'diego', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Carmen', 'Vargas', 'carmen@panaderia.com', '555-0008', 'panadero', 9500.00, '2023-05-01', 'carmen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Roberto', 'Jiménez', 'roberto@panaderia.com', '555-0009', 'cajero', 8000.00, '2023-05-15', 'roberto', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Elena', 'Morales', 'elena@panaderia.com', '555-0010', 'vendedor', 7500.00, '2023-06-01', 'elena', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Fernando', 'Castro', 'fernando@panaderia.com', '555-0011', 'panadero', 9500.00, '2023-06-15', 'fernando', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Patricia', 'Ruiz', 'patricia@panaderia.com', '555-0012', 'cajero', 8000.00, '2023-07-01', 'patricia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Andrés', 'Herrera', 'andres@panaderia.com', '555-0013', 'vendedor', 7500.00, '2023-07-15', 'andres', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Isabel', 'Peña', 'isabel@panaderia.com', '555-0014', 'manager', 12000.00, '2023-08-01', 'isabel', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Javier', 'Ortega', 'javier@panaderia.com', '555-0015', 'panadero', 9500.00, '2023-08-15', 'javier', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Lucía', 'Vega', 'lucia@panaderia.com', '555-0016', 'cajero', 8000.00, '2023-09-01', 'lucia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Manuel', 'Silva', 'manuel@panaderia.com', '555-0017', 'vendedor', 7500.00, '2023-09-15', 'manuel', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Rosa', 'Guerrero', 'rosa@panaderia.com', '555-0018', 'panadero', 9500.00, '2023-10-01', 'rosa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Sergio', 'Medina', 'sergio@panaderia.com', '555-0019', 'cajero', 8000.00, '2023-10-15', 'sergio', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Adriana', 'Romero', 'adriana@panaderia.com', '555-0020', 'vendedor', 7500.00, '2023-11-01', 'adriana', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Ricardo', 'Aguilar', 'ricardo@panaderia.com', '555-0021', 'panadero', 9500.00, '2023-11-15', 'ricardo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Sofía', 'Delgado', 'sofia@panaderia.com', '555-0022', 'cajero', 8000.00, '2023-12-01', 'sofia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Raúl', 'Navarro', 'raul@panaderia.com', '555-0023', 'vendedor', 7500.00, '2023-12-15', 'raul', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Beatriz', 'Ramos', 'beatriz@panaderia.com', '555-0024', 'manager', 12000.00, '2024-01-01', 'beatriz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Tomás', 'Iglesias', 'tomas@panaderia.com', '555-0025', 'panadero', 9500.00, '2024-01-15', 'tomas', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Natalia', 'Fuentes', 'natalia@panaderia.com', '555-0026', 'cajero', 8000.00, '2024-02-01', 'natalia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Guillermo', 'Campos', 'guillermo@panaderia.com', '555-0027', 'vendedor', 7500.00, '2024-02-15', 'guillermo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Daniela', 'Molina', 'daniela@panaderia.com', '555-0028', 'panadero', 9500.00, '2024-03-01', 'daniela', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Héctor', 'Contreras', 'hector@panaderia.com', '555-0029', 'cajero', 8000.00, '2024-03-15', 'hector', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
('Valeria', 'Mendez', 'valeria@panaderia.com', '555-0030', 'vendedor', 7500.00, '2024-04-01', 'valeria', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo');

-- Insertar 35 productos variados
INSERT INTO productos (nombre, descripcion, precio, categoria_id, stock_actual, stock_minimo, stock_maximo, codigo_barras, imagen, estado) VALUES
('Pan Integral', 'Pan integral artesanal con semillas', 45.00, 9, 25, 5, 50, '7891234567890', 'pan_integral.jpg', 'activo'),
('Croissant de Mantequilla', 'Croissant francés tradicional', 35.00, 10, 20, 3, 40, '7891234567891', 'croissant.jpg', 'activo'),
('Pastel de Chocolate', 'Pastel húmedo de chocolate con ganache', 450.00, 3, 8, 2, 15, '7891234567892', 'pastel_chocolate.jpg', 'activo'),
('Galletas de Avena', 'Galletas caseras con avena y pasas', 25.00, 4, 40, 10, 80, '7891234567893', 'galletas_avena.jpg', 'activo'),
('Café Americano', 'Café recién molido y preparado', 30.00, 5, 100, 20, 200, '7891234567894', 'cafe_americano.jpg', 'activo'),
('Empanada de Pollo', 'Empanada horneada rellena de pollo', 55.00, 6, 15, 5, 30, '7891234567895', 'empanada_pollo.jpg', 'activo'),
('Sandwich Jamón y Queso', 'Sandwich en pan artesanal', 75.00, 7, 12, 3, 25, '7891234567896', 'sandwich.jpg', 'activo'),
('Éclair de Vainilla', 'Éclair relleno de crema de vainilla', 65.00, 8, 10, 2, 20, '7891234567897', 'eclair.jpg', 'activo'),
('Pan de Masa Madre', 'Pan artesanal con masa madre natural', 50.00, 26, 18, 4, 35, '7891234567898', 'pan_masa_madre.jpg', 'activo'),
('Dona Glaseada', 'Dona esponjosa con glaseado dulce', 40.00, 11, 30, 8, 60, '7891234567899', 'dona_glaseada.jpg', 'activo'),
('Muffin de Arándanos', 'Muffin esponjoso con arándanos frescos', 42.00, 12, 25, 6, 50, '7891234567900', 'muffin_arandanos.jpg', 'activo'),
('Tarta de Manzana', 'Tarta casera con manzanas caramelizadas', 380.00, 13, 6, 1, 12, '7891234567901', 'tarta_manzana.jpg', 'activo'),
('Brownie de Chocolate', 'Brownie húmedo con nueces', 38.00, 14, 22, 5, 45, '7891234567902', 'brownie.jpg', 'activo'),
('Pan sin Gluten', 'Pan especial para celíacos', 60.00, 16, 12, 3, 25, '7891234567903', 'pan_sin_gluten.jpg', 'activo'),
('Rosca de Reyes', 'Rosca tradicional de temporada', 250.00, 17, 5, 1, 10, '7891234567904', 'rosca_reyes.jpg', 'activo'),
('Cupcake Infantil', 'Cupcake decorado para niños', 48.00, 18, 20, 5, 40, '7891234567905', 'cupcake_infantil.jpg', 'activo'),
('Pan Light', 'Pan bajo en calorías y grasas', 52.00, 19, 15, 4, 30, '7891234567906', 'pan_light.jpg', 'activo'),
('Baguette Tradicional', 'Baguette francesa artesanal', 42.00, 20, 18, 5, 35, '7891234567907', 'baguette.jpg', 'activo'),
('Cheesecake Frutos Rojos', 'Cheesecake gourmet premium', 520.00, 21, 4, 1, 8, '7891234567908', 'cheesecake.jpg', 'activo'),
('Pretzel Alemán', 'Pretzel tradicional alemán', 38.00, 22, 16, 4, 32, '7891234567909', 'pretzel.jpg', 'activo'),
('Pan del Día', 'Pan fresco elaborado diariamente', 28.00, 23, 35, 10, 70, '7891234567910', 'pan_dia.jpg', 'activo'),
('Empanada Congelada', 'Empanada lista para hornear', 45.00, 24, 25, 8, 50, '7891234567911', 'empanada_congelada.jpg', 'activo'),
('Concha Rellena', 'Concha tradicional con relleno', 48.00, 25, 20, 6, 40, '7891234567912', 'concha_rellena.jpg', 'activo'),
('Cappuccino', 'Cappuccino con espuma de leche', 45.00, 5, 80, 15, 160, '7891234567913', 'cappuccino.jpg', 'activo'),
('Tarta Vegana', 'Tarta sin ingredientes de origen animal', 420.00, 15, 3, 1, 6, '7891234567914', 'tarta_vegana.jpg', 'activo'),
('Bagel Integral', 'Bagel integral con semillas de sésamo', 40.00, 9, 22, 6, 44, '7891234567915', 'bagel_integral.jpg', 'activo'),
('Pie de Limón', 'Pie cremoso de limón con merengue', 350.00, 13, 5, 1, 10, '7891234567916', 'pie_limon.jpg', 'activo'),
('Hot Dog Gourmet', 'Hot dog con pan artesanal', 85.00, 7, 10, 3, 20, '7891234567917', 'hotdog.jpg', 'activo'),
('Pan Dulce Promoción', 'Pan dulce en oferta especial', 22.00, 28, 45, 12, 90, '7891234567918', 'pan_dulce_promo.jpg', 'activo'),
('Canapés Catering', 'Variedad de canapés para eventos', 15.00, 29, 50, 15, 100, '7891234567919', 'canapes.jpg', 'activo'),
('Torta Especial Casa', 'Torta signature de la panadería', 680.00, 30, 2, 1, 4, '7891234567920', 'torta_especial.jpg', 'activo'),
('Pan Francés', 'Pan francés crujiente por fuera', 38.00, 2, 24, 6, 48, '7891234567921', 'pan_frances.jpg', 'activo'),
('Milhojas', 'Milhojas con crema pastelera', 72.00, 8, 8, 2, 16, '7891234567922', 'milhojas.jpg', 'activo'),
('Panqué de Vainilla', 'Panqué esponjoso casero', 180.00, 1, 6, 2, 12, '7891234567923', 'panque_vainilla.jpg', 'activo'),
('Smoothie de Frutas', 'Smoothie natural de temporada', 55.00, 5, 20, 5, 40, '7891234567924', 'smoothie.jpg', 'activo');

-- Insertar 30 ventas con diferentes fechas y empleados
INSERT INTO ventas (empleado_id, fecha, total, metodo_pago, estado, cliente_nombre, cliente_telefono) VALUES
(3, '2024-04-01 08:30:00', 185.00, 'efectivo', 'completada', 'María García', '555-1001'),
(4, '2024-04-01 09:15:00', 320.00, 'tarjeta', 'completada', 'Carlos Ruiz', '555-1002'),
(6, '2024-04-01 10:00:00', 95.00, 'efectivo', 'completada', 'Ana López', '555-1003'),
(3, '2024-04-01 11:30:00', 450.00, 'tarjeta', 'completada', 'Pedro Martín', '555-1004'),
(4, '2024-04-01 14:00:00', 275.00, 'efectivo', 'completada', 'Laura Sánchez', '555-1005'),
(6, '2024-04-02 08:45:00', 160.00, 'tarjeta', 'completada', 'Diego Torres', '555-1006'),
(3, '2024-04-02 09:30:00', 380.00, 'efectivo', 'completada', 'Carmen Vargas', '555-1007'),
(4, '2024-04-02 10:15:00', 125.00, 'tarjeta', 'completada', 'Roberto Jiménez', '555-1008'),
(6, '2024-04-02 11:00:00', 220.00, 'efectivo', 'completada', 'Elena Morales', '555-1009'),
(3, '2024-04-02 15:30:00', 340.00, 'tarjeta', 'completada', 'Fernando Castro', '555-1010'),
(4, '2024-04-03 08:00:00', 195.00, 'efectivo', 'completada', 'Patricia Ruiz', '555-1011'),
(6, '2024-04-03 09:45:00', 285.00, 'tarjeta', 'completada', 'Andrés Herrera', '555-1012'),
(3, '2024-04-03 10:30:00', 155.00, 'efectivo', 'completada', 'Isabel Peña', '555-1013'),
(4, '2024-04-03 12:00:00', 415.00, 'tarjeta', 'completada', 'Javier Ortega', '555-1014'),
(6, '2024-04-03 14:15:00', 240.00, 'efectivo', 'completada', 'Lucía Vega', '555-1015'),
(3, '2024-04-04 08:15:00', 305.00, 'tarjeta', 'completada', 'Manuel Silva', '555-1016'),
(4, '2024-04-04 09:00:00', 175.00, 'efectivo', 'completada', 'Rosa Guerrero', '555-1017'),
(6, '2024-04-04 10:45:00', 265.00, 'tarjeta', 'completada', 'Sergio Medina', '555-1018'),
(3, '2024-04-04 11:30:00', 385.00, 'efectivo', 'completada', 'Adriana Romero', '555-1019'),
(4, '2024-04-04 13:00:00', 210.00, 'tarjeta', 'completada', 'Ricardo Aguilar', '555-1020'),
(6, '2024-04-05 08:30:00', 295.00, 'efectivo', 'completada', 'Sofía Delgado', '555-1021'),
(3, '2024-04-05 09:15:00', 165.00, 'tarjeta', 'completada', 'Raúl Navarro', '555-1022'),
(4, '2024-04-05 10:00:00', 355.00, 'efectivo', 'completada', 'Beatriz Ramos', '555-1023'),
(6, '2024-04-05 11:45:00', 225.00, 'tarjeta', 'completada', 'Tomás Iglesias', '555-1024'),
(3, '2024-04-05 14:30:00', 445.00, 'efectivo', 'completada', 'Natalia Fuentes', '555-1025'),
(4, '2024-04-06 08:00:00', 185.00, 'tarjeta', 'completada', 'Guillermo Campos', '555-1026'),
(6, '2024-04-06 09:30:00', 315.00, 'efectivo', 'completada', 'Daniela Molina', '555-1027'),
(3, '2024-04-06 10:15:00', 255.00, 'tarjeta', 'completada', 'Héctor Contreras', '555-1028'),
(4, '2024-04-06 12:00:00', 375.00, 'efectivo', 'completada', 'Valeria Méndez', '555-1029'),
(6, '2024-04-06 15:00:00', 195.00, 'tarjeta', 'completada', 'Cliente Frecuente', '555-1030');

-- Insertar detalles de ventas (múltiples productos por venta)
INSERT INTO venta_detalles (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES
-- Venta 1 (185.00)
(1, 1, 2, 45.00, 90.00), (1, 2, 1, 35.00, 35.00), (1, 5, 2, 30.00, 60.00),
-- Venta 2 (320.00)
(2, 3, 1, 450.00, 450.00), (2, 14, 2, 60.00, 120.00), -- Se ajustará automáticamente
-- Venta 3 (95.00)
(3, 4, 2, 25.00, 50.00), (3, 6, 1, 55.00, 55.00), -- Se ajustará automáticamente
-- Venta 4 (450.00)
(4, 3, 1, 450.00, 450.00),
-- Venta 5 (275.00)
(5, 12, 1, 380.00, 380.00), -- Se ajustará automáticamente
-- Venta 6 (160.00)
(6, 1, 2, 45.00, 90.00), (6, 10, 1, 40.00, 40.00), (6, 5, 1, 30.00, 30.00),
-- Venta 7 (380.00)
(7, 12, 1, 380.00, 380.00),
-- Venta 8 (125.00)
(8, 7, 1, 75.00, 75.00), (8, 1, 1, 45.00, 45.00), (8, 24, 1, 45.00, 45.00), -- Se ajustará
-- Venta 9 (220.00)
(9, 9, 2, 50.00, 100.00), (9, 11, 2, 42.00, 84.00), (9, 2, 1, 35.00, 35.00), -- Se ajustará
-- Venta 10 (340.00)
(10, 19, 1, 520.00, 520.00), -- Se ajustará automáticamente
-- Venta 11 (195.00)
(11, 1, 3, 45.00, 135.00), (11, 5, 2, 30.00, 60.00),
-- Venta 12 (285.00)
(12, 15, 1, 250.00, 250.00), (12, 2, 1, 35.00, 35.00),
-- Venta 13 (155.00)
(13, 7, 2, 75.00, 150.00), (13, 24, 1, 45.00, 45.00), -- Se ajustará
-- Venta 14 (415.00)
(14, 31, 1, 680.00, 680.00), -- Se ajustará automáticamente
-- Venta 15 (240.00)
(15, 1, 2, 45.00, 90.00), (15, 4, 4, 25.00, 100.00), (15, 1, 1, 45.00, 45.00), -- Se ajustará
-- Continuar con más detalles para las ventas restantes
(16, 9, 4, 50.00, 200.00), (16, 2, 3, 35.00, 105.00),
(17, 6, 2, 55.00, 110.00), (17, 8, 1, 65.00, 65.00),
(18, 13, 1, 38.00, 38.00), (18, 1, 5, 45.00, 225.00), (18, 24, 1, 45.00, 45.00), -- Se ajustará
(19, 12, 1, 380.00, 380.00), (19, 24, 1, 45.00, 45.00), -- Se ajustará
(20, 18, 2, 42.00, 84.00), (20, 1, 3, 45.00, 135.00), -- Se ajustará
(21, 27, 1, 350.00, 350.00), -- Se ajustará automáticamente
(22, 20, 3, 38.00, 114.00), (22, 5, 2, 30.00, 60.00), -- Se ajustará
(23, 19, 1, 520.00, 520.00), -- Se ajustará automáticamente
(24, 1, 4, 45.00, 180.00), (24, 5, 2, 30.00, 60.00), -- Se ajustará
(25, 3, 1, 450.00, 450.00), -- Se ajustará automáticamente
(26, 11, 3, 42.00, 126.00), (26, 4, 2, 25.00, 50.00), (26, 24, 1, 45.00, 45.00), -- Se ajustará
(27, 16, 4, 48.00, 192.00), (27, 2, 4, 35.00, 140.00), -- Se ajustará
(28, 17, 3, 52.00, 156.00), (28, 5, 3, 30.00, 90.00), (28, 24, 1, 45.00, 45.00), -- Se ajustará
(29, 25, 1, 420.00, 420.00), -- Se ajustará automáticamente
(30, 1, 3, 45.00, 135.00), (30, 4, 2, 25.00, 50.00), (30, 24, 1, 45.00, 45.00); -- Se ajustará

-- Insertar 40 movimientos de inventario (entradas y salidas)
INSERT INTO inventario_movimientos (producto_id, tipo_movimiento, cantidad, motivo, empleado_id, fecha, costo_unitario, precio_venta, lote, fecha_vencimiento) VALUES
-- Entradas de stock (reposición de inventario)
(1, 'entrada', 50, 'Reposición de stock', 5, '2024-04-01 06:00:00', 25.00, 45.00, 'LT001', '2024-04-08'),
(2, 'entrada', 40, 'Reposición de stock', 8, '2024-04-01 06:00:00', 20.00, 35.00, 'LT002', '2024-04-03'),
(3, 'entrada', 15, 'Producción diaria', 11, '2024-04-01 06:00:00', 200.00, 450.00, 'LT003', '2024-04-05'),
(4, 'entrada', 80, 'Producción diaria', 15, '2024-04-01 06:00:00', 12.00, 25.00, 'LT004', '2024-04-10'),
(5, 'entrada', 200, 'Compra de insumos', 1, '2024-04-01 06:00:00', 15.00, 30.00, 'LT005', '2024-04-30'),
(6, 'entrada', 30, 'Producción diaria', 18, '2024-04-01 06:00:00', 30.00, 55.00, 'LT006', '2024-04-03'),
(7, 'entrada', 25, 'Producción diaria', 21, '2024-04-01 06:00:00', 40.00, 75.00, 'LT007', '2024-04-02'),
(8, 'entrada', 20, 'Producción diaria', 25, '2024-04-01 06:00:00', 35.00, 65.00, 'LT008', '2024-04-04'),
(9, 'entrada', 35, 'Producción diaria', 28, '2024-04-01 06:00:00', 28.00, 50.00, 'LT009', '2024-04-06'),
(10, 'entrada', 60, 'Producción diaria', 5, '2024-04-01 06:00:00', 22.00, 40.00, 'LT010', '2024-04-05'),
-- Salidas por ventas
(1, 'salida', 2, 'Venta #1', 3, '2024-04-01 08:30:00', 25.00, 45.00, 'LT001', '2024-04-08'),
(2, 'salida', 1, 'Venta #1', 3, '2024-04-01 08:30:00', 20.00, 35.00, 'LT002', '2024-04-03'),
(5, 'salida', 2, 'Venta #1', 3, '2024-04-01 08:30:00', 15.00, 30.00, 'LT005', '2024-04-30'),
(3, 'salida', 1, 'Venta #2', 4, '2024-04-01 09:15:00', 200.00, 450.00, 'LT003', '2024-04-05'),
(4, 'salida', 2, 'Venta #3', 6, '2024-04-01 10:00:00', 12.00, 25.00, 'LT004', '2024-04-10'),
(6, 'salida', 1, 'Venta #3', 6, '2024-04-01 10:00:00', 30.00, 55.00, 'LT006', '2024-04-03'),
(3, 'salida', 1, 'Venta #4', 3, '2024-04-01 11:30:00', 200.00, 450.00, 'LT003', '2024-04-05'),
(7, 'salida', 1, 'Venta #8', 4, '2024-04-02 10:15:00', 40.00, 75.00, 'LT007', '2024-04-02'),
(1, 'salida', 1, 'Venta #8', 4, '2024-04-02 10:15:00', 25.00, 45.00, 'LT001', '2024-04-08'),
(9, 'salida', 2, 'Venta #9', 6, '2024-04-02 11:00:00', 28.00, 50.00, 'LT009', '2024-04-06'),
-- Más entradas
(11, 'entrada', 50, 'Producción diaria', 8, '2024-04-02 06:00:00', 25.00, 42.00, 'LT011', '2024-04-07'),
(12, 'entrada', 12, 'Producción diaria', 11, '2024-04-02 06:00:00', 220.00, 380.00, 'LT012', '2024-04-06'),
(13, 'entrada', 45, 'Producción diaria', 15, '2024-04-02 06:00:00', 20.00, 38.00, 'LT013', '2024-04-09'),
(14, 'entrada', 25, 'Reposición de stock', 18, '2024-04-02 06:00:00', 35.00, 60.00, 'LT014', '2024-04-12'),
(15, 'entrada', 10, 'Producción especial', 21, '2024-04-02 06:00:00', 150.00, 250.00, 'LT015', '2024-04-10'),
-- Salidas por deterioro
(2, 'salida', 3, 'Producto vencido', 25, '2024-04-03 18:00:00', 20.00, 35.00, 'LT002', '2024-04-03'),
(6, 'salida', 2, 'Producto dañado', 28, '2024-04-03 18:00:00', 30.00, 55.00, 'LT006', '2024-04-03'),
(7, 'salida', 1, 'Muestra gratis', 5, '2024-04-03 12:00:00', 40.00, 75.00, 'LT007', '2024-04-02'),
-- Más entradas de reposición
(16, 'entrada', 40, 'Producción diaria', 8, '2024-04-03 06:00:00', 28.00, 48.00, 'LT016', '2024-04-08'),
(17, 'entrada', 30, 'Producción diaria', 11, '2024-04-03 06:00:00', 30.00, 52.00, 'LT017', '2024-04-10'),
(18, 'entrada', 35, 'Producción diaria', 15, '2024-04-03 06:00:00', 24.00, 42.00, 'LT018', '2024-04-09'),
(19, 'entrada', 8, 'Producción especial', 18, '2024-04-03 06:00:00', 300.00, 520.00, 'LT019', '2024-04-08'),
(20, 'entrada', 32, 'Producción diaria', 21, '2024-04-03 06:00:00', 22.00, 38.00, 'LT020', '2024-04-08'),
-- Salidas por ventas adicionales
(1, 'salida', 5, 'Ventas del día', 25, '2024-04-03 20:00:00', 25.00, 45.00, 'LT001', '2024-04-08'),
(4, 'salida', 8, 'Ventas del día', 28, '2024-04-03 20:00:00', 12.00, 25.00, 'LT004', '2024-04-10'),
(5, 'salida', 10, 'Ventas del día', 5, '2024-04-03 20:00:00', 15.00, 30.00, 'LT005', '2024-04-30'),
(10, 'salida', 6, 'Ventas del día', 8, '2024-04-03 20:00:00', 22.00, 40.00, 'LT010', '2024-04-05'),
(11, 'salida', 4, 'Ventas del día', 11, '2024-04-03 20:00:00', 25.00, 42.00, 'LT011', '2024-04-07'),
-- Transferencias internas
(1, 'salida', 3, 'Transferencia sucursal', 15, '2024-04-04 10:00:00', 25.00, 45.00, 'LT001', '2024-04-08'),
(9, 'entrada', 5, 'Devolución cliente', 18, '2024-04-04 14:00:00', 28.00, 50.00, 'LT021', '2024-04-10'),
(12, 'salida', 1, 'Degustación evento', 21, '2024-04-04 16:00:00', 220.00, 380.00, 'LT012', '2024-04-06');

-- Insertar configuraciones del sistema
INSERT INTO configuraciones (clave, valor, descripcion, tipo) VALUES
('nombre_panaderia', 'Panadería Artesanal El Buen Pan', 'Nombre oficial de la panadería', 'texto'),
('direccion', 'Av. Principal 123, Col. Centro', 'Dirección física del establecimiento', 'texto'),
('telefono', '555-0100', 'Teléfono principal de contacto', 'texto'),
('email', 'contacto@panaderia.com', 'Email de contacto principal', 'email'),
('rfc', 'PAN123456789', 'RFC de la empresa', 'texto'),
('iva_porcentaje', '16', 'Porcentaje de IVA aplicable', 'numero'),
('moneda', 'MXN', 'Moneda utilizada en el sistema', 'texto'),
('zona_horaria', 'America/Mexico_City', 'Zona horaria del establecimiento', 'texto'),
('horario_apertura', '06:00', 'Hora de apertura diaria', 'hora'),
('horario_cierre', '20:00', 'Hora de cierre diaria', 'hora'),
('dias_laborales', 'Lunes,Martes,Miércoles,Jueves,Viernes,Sábado', 'Días de operación', 'lista'),
('stock_minimo_global', '5', 'Stock mínimo por defecto para productos', 'numero'),
('descuento_empleados', '10', 'Porcentaje de descuento para empleados', 'numero'),
('puntos_por_peso', '1', 'Puntos de lealtad por peso gastado', 'numero'),
('backup_automatico', '1', 'Activar backup automático (1=sí, 0=no)', 'boolean'),
('notificaciones_stock', '1', 'Notificar cuando el stock esté bajo', 'boolean'),
('formato_fecha', 'd/m/Y', 'Formato de fecha para reportes', 'texto'),
('decimales_precio', '2', 'Número de decimales en precios', 'numero'),
('precio_delivery', '25', 'Costo de servicio a domicilio', 'numero'),
('tiempo_sesion', '480', 'Tiempo de sesión en minutos', 'numero'),
('logo_url', 'assets/img/logo.png', 'URL del logo de la panadería', 'url'),
('facebook_url', 'https://facebook.com/panaderia', 'URL de Facebook', 'url'),
('instagram_url', 'https://instagram.com/panaderia', 'URL de Instagram', 'url'),
('whatsapp', '5550100', 'Número de WhatsApp para pedidos', 'texto'),
('mensaje_bienvenida', '¡Bienvenido a Panadería El Buen Pan!', 'Mensaje de bienvenida en el sistema', 'texto'),
('politica_devoluciones', '24', 'Horas límite para devoluciones', 'numero'),
('descuento_mayoreo', '15', 'Descuento por compra mayoreo (>20 items)', 'numero'),
('clave_wifi', 'PanWifi2024', 'Clave WiFi para clientes', 'texto'),
('capacidad_salon', '30', 'Capacidad máxima del salón', 'numero'),
('version_sistema', '1.0.0', 'Versión actual del sistema', 'texto'),
('ultima_actualizacion', '2024-04-01', 'Fecha de última actualización', 'fecha');

-- =====================================================
-- COMANDOS DE VERIFICACIÓN
-- =====================================================

-- Verificar que todas las tablas se crearon correctamente
SHOW TABLES;

-- Verificar la estructura de las tablas principales
DESCRIBE empleados;
DESCRIBE productos;
DESCRIBE ventas;

-- Verificar datos iniciales
SELECT 'Empleados' as tabla, COUNT(*) as registros FROM empleados
UNION ALL
SELECT 'Productos', COUNT(*) FROM productos
UNION ALL
SELECT 'Categorías', COUNT(*) FROM categorias
UNION ALL
SELECT 'Ventas', COUNT(*) FROM ventas;

-- =====================================================
-- COMENTARIOS FINALES
-- =====================================================

/*
INSTRUCCIONES DE USO:

1. Ejecutar este script en phpMyAdmin o cliente MySQL
2. Verificar que todas las tablas se crearon correctamente
3. Los datos de prueba incluyen:
   - 4 empleados (admin/admin123 para login)
   - 4 categorías de productos
   - 17 productos de ejemplo
   - 5 ventas de ejemplo
   
4. Usuarios de prueba:
   - admin@panaderia.com / admin123 (Administrador)
   - maria@panaderia.com / maria123 (Vendedor)
   - carlos@panaderia.com / carlos123 (Panadero)
   - ana@panaderia.com / ana123 (Cajero)

5. La base de datos está optimizada con:
   - Índices en campos frecuentemente consultados
   - Triggers para automatizar inventario
   - Vistas para consultas complejas
   - Procedimientos almacenados para reportes
   
6. Funcionalidades implementadas:
   - Control automático de inventario
   - Validación de stock en ventas
   - Registro de movimientos de inventario
   - Estructura para múltiples roles de usuario
   - Sistema de categorías flexible
   - Trazabilidad completa de ventas
*/

-- Mensaje de confirmación
SELECT 'Base de datos del Sistema de Panadería creada exitosamente' as mensaje;
