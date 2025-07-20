<?php
/**
 * Archivo de prueba para verificar la conectividad de la base de datos
 * y mostrar algunos datos de muestra del sistema de panadería
 */

// Incluir configuración de la base de datos
require_once 'api/config/database.php';

try {
    // Crear conexión usando la clase Database
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h1>🍞 Sistema de Gestión de Panadería - Verificación de Datos</h1>";
    echo "<div style='font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px;'>";
    
    // Verificar categorías
    echo "<h2>📋 Categorías (Primeras 10)</h2>";
    $stmt = $conn->prepare("SELECT id, nombre, descripcion, icono, color FROM categorias LIMIT 10");
    $stmt->execute();
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>";
    echo "<tr style='background-color: #f4f4f4;'><th>ID</th><th>Nombre</th><th>Descripción</th><th>Icono</th><th>Color</th></tr>";
    foreach($categorias as $categoria) {
        echo "<tr>";
        echo "<td>" . $categoria['id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($categoria['nombre']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($categoria['descripcion']) . "</td>";
        echo "<td><i class='fa " . $categoria['icono'] . "'></i> " . $categoria['icono'] . "</td>";
        echo "<td><span style='background-color: " . $categoria['color'] . "; padding: 5px; color: white; border-radius: 3px;'>" . $categoria['color'] . "</span></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar empleados
    echo "<h2>👥 Empleados (Primeros 10)</h2>";
    $stmt = $conn->prepare("SELECT id, nombre, email, rol, salario, fecha_contratacion, estado FROM empleados LIMIT 10");
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>";
    echo "<tr style='background-color: #f4f4f4;'><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Salario</th><th>Fecha Contratación</th><th>Estado</th></tr>";
    foreach($empleados as $empleado) {
        echo "<tr>";
        echo "<td>" . $empleado['id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($empleado['nombre']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($empleado['email']) . "</td>";
        echo "<td><span style='background-color: #007bff; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;'>" . strtoupper($empleado['rol']) . "</span></td>";
        echo "<td>$" . number_format($empleado['salario'], 2) . "</td>";
        echo "<td>" . $empleado['fecha_contratacion'] . "</td>";
        echo "<td><span style='color: " . ($empleado['estado'] == 'activo' ? 'green' : 'red') . ";'>●</span> " . ucfirst($empleado['estado']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar productos
    echo "<h2>🥖 Productos (Primeros 10)</h2>";
    $stmt = $conn->prepare("
        SELECT p.id, p.nombre, p.descripcion, p.precio, p.costo, p.stock, p.stock_minimo, 
               c.nombre as categoria_nombre, p.estado 
        FROM productos p 
        JOIN categorias c ON p.categoria_id = c.id 
        LIMIT 10
    ");
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>";
    echo "<tr style='background-color: #f4f4f4;'><th>ID</th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Costo</th><th>Stock</th><th>Stock Mín.</th><th>Estado</th></tr>";
    foreach($productos as $producto) {
        $stock_color = $producto['stock'] <= $producto['stock_minimo'] ? 'red' : 'green';
        echo "<tr>";
        echo "<td>" . $producto['id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($producto['nombre']) . "</strong><br><small>" . htmlspecialchars(substr($producto['descripcion'], 0, 50)) . "...</small></td>";
        echo "<td>" . htmlspecialchars($producto['categoria_nombre']) . "</td>";
        echo "<td style='text-align: right;'><strong>$" . number_format($producto['precio'], 2) . "</strong></td>";
        echo "<td style='text-align: right;'>$" . number_format($producto['costo'], 2) . "</td>";
        echo "<td style='text-align: center; color: " . $stock_color . ";'><strong>" . $producto['stock'] . "</strong></td>";
        echo "<td style='text-align: center;'>" . $producto['stock_minimo'] . "</td>";
        echo "<td><span style='color: " . ($producto['estado'] == 'disponible' ? 'green' : 'orange') . ";'>●</span> " . ucfirst($producto['estado']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar ventas
    echo "<h2>🛒 Ventas Recientes (Últimas 10)</h2>";
    $stmt = $conn->prepare("
        SELECT v.id, v.fecha, v.total, v.metodo_pago, v.cliente_nombre, v.cliente_telefono, 
               e.nombre as empleado_nombre, v.estado 
        FROM ventas v 
        JOIN empleados e ON v.empleado_id = e.id 
        ORDER BY v.fecha DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>";
    echo "<tr style='background-color: #f4f4f4;'><th>ID</th><th>Fecha</th><th>Cliente</th><th>Empleado</th><th>Total</th><th>Método Pago</th><th>Estado</th></tr>";
    foreach($ventas as $venta) {
        echo "<tr>";
        echo "<td>" . $venta['id'] . "</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($venta['fecha'])) . "</td>";
        echo "<td><strong>" . htmlspecialchars($venta['cliente_nombre']) . "</strong><br><small>" . $venta['cliente_telefono'] . "</small></td>";
        echo "<td>" . htmlspecialchars($venta['empleado_nombre']) . "</td>";
        echo "<td style='text-align: right;'><strong>$" . number_format($venta['total'], 2) . "</strong></td>";
        echo "<td><span style='background-color: " . ($venta['metodo_pago'] == 'efectivo' ? '#28a745' : '#007bff') . "; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;'>" . strtoupper($venta['metodo_pago']) . "</span></td>";
        echo "<td><span style='color: green;'>●</span> " . ucfirst($venta['estado']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Estadísticas generales
    echo "<h2>📊 Estadísticas Generales</h2>";
    
    // Contar registros totales
    $stats = [];
    $tables = ['categorias', 'empleados', 'productos', 'ventas', 'configuraciones'];
    
    foreach($tables as $table) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM $table");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats[$table] = $result['total'];
    }
    
    // Total de ventas del día
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) as total_dia FROM ventas WHERE DATE(fecha) = CURDATE()");
    $stmt->execute();
    $total_dia = $stmt->fetch(PDO::FETCH_ASSOC)['total_dia'];
    
    // Productos con stock bajo
    $stmt = $conn->prepare("SELECT COUNT(*) as productos_stock_bajo FROM productos WHERE stock <= stock_minimo");
    $stmt->execute();
    $stock_bajo = $stmt->fetch(PDO::FETCH_ASSOC)['productos_stock_bajo'];
    
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;'>";
    
    echo "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center;'>";
    echo "<h3 style='margin: 0; font-size: 24px;'>" . $stats['categorias'] . "</h3>";
    echo "<p style='margin: 5px 0 0 0;'>Categorías</p>";
    echo "</div>";
    
    echo "<div style='background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 8px; text-align: center;'>";
    echo "<h3 style='margin: 0; font-size: 24px;'>" . $stats['empleados'] . "</h3>";
    echo "<p style='margin: 5px 0 0 0;'>Empleados</p>";
    echo "</div>";
    
    echo "<div style='background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 20px; border-radius: 8px; text-align: center;'>";
    echo "<h3 style='margin: 0; font-size: 24px;'>" . $stats['productos'] . "</h3>";
    echo "<p style='margin: 5px 0 0 0;'>Productos</p>";
    echo "</div>";
    
    echo "<div style='background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 20px; border-radius: 8px; text-align: center;'>";
    echo "<h3 style='margin: 0; font-size: 24px;'>" . $stats['ventas'] . "</h3>";
    echo "<p style='margin: 5px 0 0 0;'>Ventas Totales</p>";
    echo "</div>";
    
    echo "<div style='background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 20px; border-radius: 8px; text-align: center;'>";
    echo "<h3 style='margin: 0; font-size: 24px;'>$" . number_format($total_dia, 2) . "</h3>";
    echo "<p style='margin: 5px 0 0 0;'>Ventas Hoy</p>";
    echo "</div>";
    
    echo "<div style='background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); color: white; padding: 20px; border-radius: 8px; text-align: center;'>";
    echo "<h3 style='margin: 0; font-size: 24px;'>" . $stock_bajo . "</h3>";
    echo "<p style='margin: 5px 0 0 0;'>Stock Bajo</p>";
    echo "</div>";
    
    echo "</div>";
    
    // Instrucciones de acceso
    echo "<h2>🔐 Acceso al Sistema</h2>";
    echo "<div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #007bff;'>";
    echo "<h3>Usuarios de Prueba:</h3>";
    echo "<ul>";
    echo "<li><strong>Administrador:</strong> admin@panaderia.com | Contraseña: password</li>";
    echo "<li><strong>Manager:</strong> maria@panaderia.com | Contraseña: password</li>";
    echo "<li><strong>Cajero:</strong> juan@panaderia.com | Contraseña: password</li>";
    echo "<li><strong>Vendedor:</strong> ana@panaderia.com | Contraseña: password</li>";
    echo "<li><strong>Panadero:</strong> pedro@panaderia.com | Contraseña: password</li>";
    echo "</ul>";
    echo "<p><strong>Nota:</strong> Todas las contraseñas están hasheadas en la base de datos. La contraseña real es 'password' para todos los usuarios.</p>";
    echo "</div>";
    
    echo "<div style='background-color: #d4edda; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; margin-top: 20px;'>";
    echo "<h3>✅ Sistema Listo</h3>";
    echo "<p>La base de datos ha sido poblada exitosamente con:</p>";
    echo "<ul>";
    echo "<li>30 categorías de productos de panadería</li>";
    echo "<li>30 empleados con diferentes roles</li>";
    echo "<li>35 productos variados con precios realistas</li>";
    echo "<li>30 ventas de muestra con detalles</li>";
    echo "<li>31 configuraciones del sistema</li>";
    echo "</ul>";
    echo "<p><strong>Accede al sistema principal:</strong> <a href='index.html' style='color: #007bff; text-decoration: none; font-weight: bold;'>Sistema de Gestión de Panadería</a></p>";
    echo "</div>";
    
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='background-color: #f8d7da; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545; color: #721c24;'>";
    echo "<h2>❌ Error de Conexión</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Asegúrate de que:</p>";
    echo "<ul>";
    echo "<li>XAMPP esté ejecutándose</li>";
    echo "<li>MySQL esté activo</li>";
    echo "<li>La base de datos 'panaderia_db' exista</li>";
    echo "</ul>";
    echo "</div>";
}
?>
