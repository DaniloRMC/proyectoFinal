<?php
/**
 * API SIMPLIFICADO PARA DASHBOARD - SIN AUTENTICACIÓN COMPLEJA
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Incluir configuración de base de datos
    require_once 'api/config/database.php';
    
    // Crear conexión
    $database = new Database();
    $conn = $database->getConnection();
    
    // Obtener estadísticas
    
    // Ventas del día
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) as total FROM ventas WHERE DATE(fecha) = CURDATE()");
    $stmt->execute();
    $ventasHoy = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Productos activos
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM productos WHERE estado = 'disponible'");
    $stmt->execute();
    $productosActivos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Empleados activos
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM empleados WHERE estado = 'activo'");
    $stmt->execute();
    $empleadosActivos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Ventas del mes
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) as total FROM ventas WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())");
    $stmt->execute();
    $ventasMes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Ventas de la semana (últimos 7 días)
    $stmt = $conn->prepare("
        SELECT 
            DAYNAME(fecha) as dia,
            COALESCE(SUM(total), 0) as total 
        FROM ventas 
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(fecha), DAYNAME(fecha)
        ORDER BY fecha
    ");
    $stmt->execute();
    $ventasSemana = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Productos más vendidos
    $stmt = $conn->prepare("
        SELECT 
            p.nombre,
            COUNT(vd.producto_id) as ventas
        FROM productos p
        LEFT JOIN venta_detalles vd ON p.id = vd.producto_id
        LEFT JOIN ventas v ON vd.venta_id = v.id
        WHERE v.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY p.id, p.nombre
        ORDER BY ventas DESC
        LIMIT 5
    ");
    $stmt->execute();
    $productosPopulares = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Alertas de stock
    $stmt = $conn->prepare("
        SELECT 
            nombre as producto,
            stock,
            stock_minimo as minimo
        FROM productos 
        WHERE stock <= stock_minimo
        ORDER BY (stock_minimo - stock) DESC
        LIMIT 10
    ");
    $stmt->execute();
    $alertasStock = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Preparar datos para el gráfico
    $chartLabels = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    $chartData = [0, 0, 0, 0, 0, 0, 0];
    
    foreach ($ventasSemana as $venta) {
        $dia = $venta['dia'];
        $total = floatval($venta['total']);
        
        switch ($dia) {
            case 'Monday': $chartData[0] += $total; break;
            case 'Tuesday': $chartData[1] += $total; break;
            case 'Wednesday': $chartData[2] += $total; break;
            case 'Thursday': $chartData[3] += $total; break;
            case 'Friday': $chartData[4] += $total; break;
            case 'Saturday': $chartData[5] += $total; break;
            case 'Sunday': $chartData[6] += $total; break;
        }
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => [
            'ventasHoy' => floatval($ventasHoy),
            'productosActivos' => intval($productosActivos),
            'empleadosActivos' => intval($empleadosActivos),
            'ventasMes' => floatval($ventasMes),
            'ventasChart' => [
                'labels' => $chartLabels,
                'data' => $chartData
            ],
            'productosPopulares' => $productosPopulares,
            'alertasStock' => $alertasStock
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>
