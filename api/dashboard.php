<?php
/**
 * SISTEMA DE GESTIÓN DE PANADERÍA - API ENDPOINT DASHBOARD
 * 
 * Este endpoint proporciona datos estadísticos y métricas principales
 * para el dashboard del sistema de gestión de panadería.
 * 
 * Métodos soportados:
 * - GET: Obtener estadísticas del dashboard, métricas específicas
 * 
 * @author Sistema de Panadería
 * @version 1.0
 * @package PanaderiaManager\API
 */

// Definir constante de acceso
define('PANADERIA_ACCESS', true);

// Incluir archivos de configuración
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Configurar headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=UTF-8');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Clase DashboardAPI - Maneja operaciones del dashboard
 */
class DashboardAPI {
    
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Exception $e) {
            Logger::error('Error conectando a la base de datos: ' . $e->getMessage());
            Response::error('Error de conexión a la base de datos', 500);
        }
    }
    
    /**
     * Maneja las peticiones HTTP
     */
    public function handleRequest() {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $pathInfo = $_SERVER['PATH_INFO'] ?? '';
            $action = trim($pathInfo, '/');
            
            if ($method !== 'GET') {
                Response::error('Solo se permiten peticiones GET', 405);
            }
            
            switch ($action) {
                case '':
                case 'overview':
                    $this->getOverview();
                    break;
                case 'sales-chart':
                    $this->getSalesChart();
                    break;
                case 'products-chart':
                    $this->getProductsChart();
                    break;
                case 'inventory-alerts':
                    $this->getInventoryAlerts();
                    break;
                case 'recent-activities':
                    $this->getRecentActivities();
                    break;
                case 'top-products':
                    $this->getTopProducts();
                    break;
                case 'financial-summary':
                    $this->getFinancialSummary();
                    break;
                case 'employee-performance':
                    $this->getEmployeePerformance();
                    break;
                default:
                    Response::error('Endpoint no encontrado', 404);
            }
            
        } catch (Exception $e) {
            Logger::error('Error en DashboardAPI: ' . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Obtiene resumen general del dashboard
     */
    private function getOverview() {
        try {
            $period = getGet('period', 'today'); // today, week, month, year
            [$startDate, $endDate] = $this->getDateRange($period);
            
            // Estadísticas de ventas
            $salesStats = $this->getSalesStatistics($startDate, $endDate);
            
            // Estadísticas de productos
            $productStats = $this->getProductStatistics();
            
            // Estadísticas de inventario
            $inventoryStats = $this->getInventoryStatistics();
            
            // Estadísticas de empleados
            $employeeStats = $this->getEmployeeStatistics();
            
            // Alertas importantes
            $alerts = $this->getImportantAlerts();
            
            // Actividades recientes
            $recentActivities = $this->getRecentActivitiesData(5);
            
            // Comparación con período anterior
            $comparison = $this->getPeriodComparison($period, $startDate, $endDate);
            
            Response::success([
                'period' => [
                    'type' => $period,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'label' => $this->getPeriodLabel($period, $startDate, $endDate)
                ],
                'sales' => $salesStats,
                'products' => $productStats,
                'inventory' => $inventoryStats,
                'employees' => $employeeStats,
                'alerts' => $alerts,
                'recent_activities' => $recentActivities,
                'comparison' => $comparison,
                'generated_at' => date('Y-m-d H:i:s')
            ], 'Dashboard overview obtenido exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo overview: ' . $e->getMessage());
            Response::error('Error al obtener overview del dashboard', 500);
        }
    }
    
    /**
     * Obtiene datos para gráfico de ventas
     */
    private function getSalesChart() {
        try {
            $period = getGet('period', 'week');
            $type = getGet('type', 'daily'); // daily, weekly, monthly
            
            [$startDate, $endDate] = $this->getDateRange($period);
            
            $groupBy = $this->getGroupByClause($type);
            
            $sql = "SELECT 
                        {$groupBy} as period_label,
                        COUNT(*) as total_ventas,
                        SUM(CASE WHEN estado = 'completada' THEN total ELSE 0 END) as total_ingresos,
                        AVG(CASE WHEN estado = 'completada' THEN total ELSE NULL END) as promedio_venta
                    FROM ventas 
                    WHERE DATE(fecha_venta) BETWEEN :start_date AND :end_date
                    GROUP BY {$groupBy}
                    ORDER BY {$groupBy}";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            $chartData = $stmt->fetchAll();
            
            // Formatear datos para el gráfico
            $formattedData = [
                'labels' => array_column($chartData, 'period_label'),
                'datasets' => [
                    [
                        'label' => 'Ingresos',
                        'data' => array_map('floatval', array_column($chartData, 'total_ingresos')),
                        'backgroundColor' => 'rgba(255, 193, 7, 0.2)',
                        'borderColor' => 'rgba(255, 193, 7, 1)',
                        'borderWidth' => 2,
                        'fill' => true
                    ],
                    [
                        'label' => 'Número de Ventas',
                        'data' => array_map('intval', array_column($chartData, 'total_ventas')),
                        'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 2,
                        'fill' => false,
                        'yAxisID' => 'y1'
                    ]
                ]
            ];
            
            Response::success([
                'chart_data' => $formattedData,
                'period' => $period,
                'type' => $type,
                'total_period_sales' => array_sum(array_column($chartData, 'total_ingresos')),
                'total_period_transactions' => array_sum(array_column($chartData, 'total_ventas'))
            ], 'Datos del gráfico de ventas obtenidos exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo gráfico de ventas: ' . $e->getMessage());
            Response::error('Error al obtener gráfico de ventas', 500);
        }
    }
    
    /**
     * Obtiene datos para gráfico de productos
     */
    private function getProductsChart() {
        try {
            $type = getGet('type', 'top-selling'); // top-selling, by-category, low-stock
            $period = getGet('period', 'month');
            $limit = min(max(1, (int)getGet('limit', 10)), 20);
            
            switch ($type) {
                case 'top-selling':
                    $chartData = $this->getTopSellingProductsChart($period, $limit);
                    break;
                case 'by-category':
                    $chartData = $this->getProductsByCategoryChart($period);
                    break;
                case 'low-stock':
                    $chartData = $this->getLowStockProductsChart();
                    break;
                default:
                    Response::error('Tipo de gráfico no válido', 400);
            }
            
            Response::success([
                'chart_data' => $chartData,
                'type' => $type,
                'period' => $period
            ], 'Datos del gráfico de productos obtenidos exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo gráfico de productos: ' . $e->getMessage());
            Response::error('Error al obtener gráfico de productos', 500);
        }
    }
    
    /**
     * Obtiene alertas de inventario para el dashboard
     */
    private function getInventoryAlerts() {
        try {
            $sql = "SELECT p.id, p.codigo, p.nombre, p.stock_actual, p.stock_minimo,
                           CASE 
                               WHEN p.stock_actual <= 0 THEN 'critical'
                               WHEN p.stock_actual <= p.stock_minimo THEN 'warning'
                               ELSE 'normal'
                           END as alert_level
                    FROM productos p 
                    WHERE p.estado = 'activo' 
                    AND p.stock_actual <= p.stock_minimo
                    ORDER BY 
                        CASE 
                            WHEN p.stock_actual <= 0 THEN 1
                            WHEN p.stock_actual <= p.stock_minimo THEN 2
                            ELSE 3
                        END,
                        p.stock_actual ASC
                    LIMIT 10";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $alerts = $stmt->fetchAll();
            
            // Formatear alertas
            $formattedAlerts = array_map(function($alert) {
                return [
                    'id' => (int)$alert['id'],
                    'codigo' => $alert['codigo'],
                    'nombre' => $alert['nombre'],
                    'stock_actual' => (int)$alert['stock_actual'],
                    'stock_minimo' => (int)$alert['stock_minimo'],
                    'alert_level' => $alert['alert_level'],
                    'message' => $this->generateAlertMessage($alert),
                    'deficit' => max(0, $alert['stock_minimo'] - $alert['stock_actual'])
                ];
            }, $alerts);
            
            // Contar alertas por nivel
            $alertCounts = [
                'critical' => count(array_filter($formattedAlerts, fn($a) => $a['alert_level'] === 'critical')),
                'warning' => count(array_filter($formattedAlerts, fn($a) => $a['alert_level'] === 'warning')),
                'total' => count($formattedAlerts)
            ];
            
            Response::success([
                'alerts' => $formattedAlerts,
                'counts' => $alertCounts
            ], 'Alertas de inventario obtenidas exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo alertas: ' . $e->getMessage());
            Response::error('Error al obtener alertas de inventario', 500);
        }
    }
    
    /**
     * Obtiene actividades recientes
     */
    private function getRecentActivities() {
        try {
            $limit = min(max(1, (int)getGet('limit', 10)), 50);
            $activities = $this->getRecentActivitiesData($limit);
            
            Response::success([
                'activities' => $activities,
                'total' => count($activities)
            ], 'Actividades recientes obtenidas exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo actividades: ' . $e->getMessage());
            Response::error('Error al obtener actividades recientes', 500);
        }
    }
    
    /**
     * Obtiene productos más vendidos
     */
    private function getTopProducts() {
        try {
            $period = getGet('period', 'month');
            $limit = min(max(1, (int)getGet('limit', 5)), 20);
            
            [$startDate, $endDate] = $this->getDateRange($period);
            
            $sql = "SELECT 
                        p.id,
                        p.codigo,
                        p.nombre,
                        p.precio,
                        SUM(vd.cantidad) as total_vendido,
                        SUM(vd.subtotal) as total_ingresos,
                        COUNT(DISTINCT vd.venta_id) as numero_ventas
                    FROM venta_detalles vd
                    INNER JOIN ventas v ON vd.venta_id = v.id
                    INNER JOIN productos p ON vd.producto_id = p.id
                    WHERE DATE(v.fecha_venta) BETWEEN :start_date AND :end_date
                    AND v.estado = 'completada'
                    GROUP BY p.id, p.codigo, p.nombre, p.precio
                    ORDER BY total_vendido DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $topProducts = $stmt->fetchAll();
            
            // Formatear productos
            $formattedProducts = array_map(function($product) {
                return [
                    'id' => (int)$product['id'],
                    'codigo' => $product['codigo'],
                    'nombre' => $product['nombre'],
                    'precio' => (float)$product['precio'],
                    'total_vendido' => (int)$product['total_vendido'],
                    'total_ingresos' => (float)$product['total_ingresos'],
                    'numero_ventas' => (int)$product['numero_ventas'],
                    'precio_formateado' => formatCurrency($product['precio']),
                    'total_ingresos_formateado' => formatCurrency($product['total_ingresos'])
                ];
            }, $topProducts);
            
            Response::success([
                'top_products' => $formattedProducts,
                'period' => $period,
                'total_products' => count($formattedProducts)
            ], 'Productos más vendidos obtenidos exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo top productos: ' . $e->getMessage());
            Response::error('Error al obtener productos más vendidos', 500);
        }
    }
    
    /**
     * Obtiene resumen financiero
     */
    private function getFinancialSummary() {
        try {
            $period = getGet('period', 'month');
            [$startDate, $endDate] = $this->getDateRange($period);
            
            // Ingresos del período
            $sql = "SELECT 
                        SUM(CASE WHEN estado = 'completada' THEN total ELSE 0 END) as total_ingresos,
                        SUM(CASE WHEN estado = 'completada' THEN impuestos ELSE 0 END) as total_impuestos,
                        COUNT(CASE WHEN estado = 'completada' THEN 1 ELSE NULL END) as ventas_completadas,
                        AVG(CASE WHEN estado = 'completada' THEN total ELSE NULL END) as ticket_promedio
                    FROM ventas 
                    WHERE DATE(fecha_venta) BETWEEN :start_date AND :end_date";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            $salesData = $stmt->fetch();
            
            // Costos estimados (basado en productos vendidos)
            $costSql = "SELECT 
                            SUM(vd.cantidad * p.costo) as costos_estimados
                        FROM venta_detalles vd
                        INNER JOIN ventas v ON vd.venta_id = v.id
                        INNER JOIN productos p ON vd.producto_id = p.id
                        WHERE DATE(v.fecha_venta) BETWEEN :start_date AND :end_date
                        AND v.estado = 'completada'";
            
            $costStmt = $this->db->prepare($costSql);
            $costStmt->bindParam(':start_date', $startDate);
            $costStmt->bindParam(':end_date', $endDate);
            $costStmt->execute();
            $costData = $costStmt->fetch();
            
            // Ingresos por método de pago
            $paymentMethodSql = "SELECT 
                                    metodo_pago,
                                    SUM(total) as total_por_metodo,
                                    COUNT(*) as ventas_por_metodo
                                FROM ventas 
                                WHERE DATE(fecha_venta) BETWEEN :start_date AND :end_date
                                AND estado = 'completada'
                                GROUP BY metodo_pago
                                ORDER BY total_por_metodo DESC";
            
            $paymentStmt = $this->db->prepare($paymentMethodSql);
            $paymentStmt->bindParam(':start_date', $startDate);
            $paymentStmt->bindParam(':end_date', $endDate);
            $paymentStmt->execute();
            $paymentMethods = $paymentStmt->fetchAll();
            
            // Calcular métricas
            $totalIngresos = (float)$salesData['total_ingresos'];
            $costosEstimados = (float)$costData['costos_estimados'];
            $gananciaBruta = $totalIngresos - $costosEstimados;
            $margenGanancia = $totalIngresos > 0 ? ($gananciaBruta / $totalIngresos) * 100 : 0;
            
            Response::success([
                'period' => [
                    'type' => $period,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'summary' => [
                    'total_ingresos' => $totalIngresos,
                    'total_impuestos' => (float)$salesData['total_impuestos'],
                    'costos_estimados' => $costosEstimados,
                    'ganancia_bruta' => $gananciaBruta,
                    'margen_ganancia' => round($margenGanancia, 2),
                    'ventas_completadas' => (int)$salesData['ventas_completadas'],
                    'ticket_promedio' => (float)$salesData['ticket_promedio'],
                    'total_ingresos_formateado' => formatCurrency($totalIngresos),
                    'ganancia_bruta_formateada' => formatCurrency($gananciaBruta),
                    'ticket_promedio_formateado' => formatCurrency($salesData['ticket_promedio'])
                ],
                'payment_methods' => array_map(function($method) {
                    return [
                        'metodo' => $method['metodo_pago'],
                        'metodo_nombre' => PAYMENT_METHODS[$method['metodo_pago']] ?? $method['metodo_pago'],
                        'total' => (float)$method['total_por_metodo'],
                        'ventas' => (int)$method['ventas_por_metodo'],
                        'total_formateado' => formatCurrency($method['total_por_metodo'])
                    ];
                }, $paymentMethods)
            ], 'Resumen financiero obtenido exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo resumen financiero: ' . $e->getMessage());
            Response::error('Error al obtener resumen financiero', 500);
        }
    }
    
    /**
     * Obtiene rendimiento de empleados
     */
    private function getEmployeePerformance() {
        try {
            $period = getGet('period', 'month');
            [$startDate, $endDate] = $this->getDateRange($period);
            
            $sql = "SELECT 
                        e.id,
                        e.nombre,
                        e.apellido,
                        e.rol,
                        COUNT(v.id) as total_ventas,
                        SUM(CASE WHEN v.estado = 'completada' THEN v.total ELSE 0 END) as total_ingresos,
                        AVG(CASE WHEN v.estado = 'completada' THEN v.total ELSE NULL END) as promedio_venta
                    FROM empleados e
                    LEFT JOIN ventas v ON e.id = v.empleado_id 
                        AND DATE(v.fecha_venta) BETWEEN :start_date AND :end_date
                    WHERE e.estado = 'activo'
                    AND e.rol IN ('cajero', 'vendedor', 'manager')
                    GROUP BY e.id, e.nombre, e.apellido, e.rol
                    HAVING total_ventas > 0
                    ORDER BY total_ingresos DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            $employees = $stmt->fetchAll();
            
            // Formatear datos de empleados
            $formattedEmployees = array_map(function($employee) {
                return [
                    'id' => (int)$employee['id'],
                    'nombre_completo' => $employee['nombre'] . ' ' . $employee['apellido'],
                    'rol' => $employee['rol'],
                    'rol_nombre' => USER_ROLES[$employee['rol']] ?? $employee['rol'],
                    'total_ventas' => (int)$employee['total_ventas'],
                    'total_ingresos' => (float)$employee['total_ingresos'],
                    'promedio_venta' => (float)$employee['promedio_venta'],
                    'total_ingresos_formateado' => formatCurrency($employee['total_ingresos']),
                    'promedio_venta_formateado' => formatCurrency($employee['promedio_venta'])
                ];
            }, $employees);
            
            Response::success([
                'employees' => $formattedEmployees,
                'period' => [
                    'type' => $period,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'total_employees' => count($formattedEmployees)
            ], 'Rendimiento de empleados obtenido exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo rendimiento de empleados: ' . $e->getMessage());
            Response::error('Error al obtener rendimiento de empleados', 500);
        }
    }
    
    /**
     * Obtiene estadísticas de ventas
     */
    private function getSalesStatistics($startDate, $endDate) {
        $sql = "SELECT 
                    COUNT(*) as total_ventas,
                    SUM(CASE WHEN estado = 'completada' THEN total ELSE 0 END) as total_ingresos,
                    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as ventas_completadas,
                    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as ventas_canceladas,
                    AVG(CASE WHEN estado = 'completada' THEN total ELSE NULL END) as ticket_promedio
                FROM ventas 
                WHERE DATE(fecha_venta) BETWEEN :start_date AND :end_date";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        $stats = $stmt->fetch();
        
        return [
            'total_ventas' => (int)$stats['total_ventas'],
            'total_ingresos' => (float)$stats['total_ingresos'],
            'ventas_completadas' => (int)$stats['ventas_completadas'],
            'ventas_canceladas' => (int)$stats['ventas_canceladas'],
            'ticket_promedio' => (float)$stats['ticket_promedio'],
            'total_ingresos_formateado' => formatCurrency($stats['total_ingresos']),
            'ticket_promedio_formateado' => formatCurrency($stats['ticket_promedio'])
        ];
    }
    
    /**
     * Obtiene estadísticas de productos
     */
    private function getProductStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_productos,
                    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as productos_activos,
                    SUM(CASE WHEN estado = 'activo' AND stock_actual > stock_minimo THEN 1 ELSE 0 END) as stock_normal,
                    SUM(CASE WHEN estado = 'activo' AND stock_actual <= stock_minimo AND stock_actual > 0 THEN 1 ELSE 0 END) as stock_bajo,
                    SUM(CASE WHEN estado = 'activo' AND stock_actual <= 0 THEN 1 ELSE 0 END) as sin_stock
                FROM productos";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Obtiene estadísticas de inventario
     */
    private function getInventoryStatistics() {
        $sql = "SELECT 
                    SUM(stock_actual * precio) as valor_inventario,
                    SUM(stock_actual * costo) as costo_inventario,
                    COUNT(CASE WHEN stock_actual <= stock_minimo THEN 1 ELSE NULL END) as alertas_stock
                FROM productos 
                WHERE estado = 'activo'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $stats = $stmt->fetch();
        
        return [
            'valor_inventario' => (float)$stats['valor_inventario'],
            'costo_inventario' => (float)$stats['costo_inventario'],
            'alertas_stock' => (int)$stats['alertas_stock'],
            'valor_inventario_formateado' => formatCurrency($stats['valor_inventario'])
        ];
    }
    
    /**
     * Obtiene estadísticas de empleados
     */
    private function getEmployeeStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_empleados,
                    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as empleados_activos,
                    COUNT(DISTINCT rol) as roles_diferentes
                FROM empleados";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Obtiene alertas importantes
     */
    private function getImportantAlerts() {
        $alerts = [];
        
        // Alertas de stock bajo
        $stockSql = "SELECT COUNT(*) as count FROM productos WHERE estado = 'activo' AND stock_actual <= stock_minimo";
        $stockStmt = $this->db->prepare($stockSql);
        $stockStmt->execute();
        $stockCount = $stockStmt->fetchColumn();
        
        if ($stockCount > 0) {
            $alerts[] = [
                'type' => 'stock',
                'level' => 'warning',
                'message' => "{$stockCount} producto(s) con stock bajo",
                'count' => $stockCount
            ];
        }
        
        // Productos sin stock
        $noStockSql = "SELECT COUNT(*) as count FROM productos WHERE estado = 'activo' AND stock_actual <= 0";
        $noStockStmt = $this->db->prepare($noStockSql);
        $noStockStmt->execute();
        $noStockCount = $noStockStmt->fetchColumn();
        
        if ($noStockCount > 0) {
            $alerts[] = [
                'type' => 'no_stock',
                'level' => 'critical',
                'message' => "{$noStockCount} producto(s) sin stock",
                'count' => $noStockCount
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Obtiene actividades recientes del sistema
     */
    private function getRecentActivitiesData($limit) {
        $activities = [];
        
        // Ventas recientes
        $salesSql = "SELECT 'sale' as type, id, numero_factura as reference, total, fecha_venta as created_at 
                     FROM ventas 
                     WHERE estado = 'completada'
                     ORDER BY fecha_venta DESC 
                     LIMIT 5";
        
        $salesStmt = $this->db->prepare($salesSql);
        $salesStmt->execute();
        $sales = $salesStmt->fetchAll();
        
        foreach ($sales as $sale) {
            $activities[] = [
                'type' => 'sale',
                'icon' => 'fa-shopping-cart',
                'color' => 'success',
                'title' => "Venta completada",
                'description' => "Factura {$sale['reference']} por " . formatCurrency($sale['total']),
                'timestamp' => $sale['created_at'],
                'formatted_time' => formatDateTime($sale['created_at'])
            ];
        }
        
        // Movimientos de inventario recientes
        $inventorySql = "SELECT 'inventory' as type, im.tipo, im.cantidad, p.nombre, im.fecha_movimiento as created_at
                         FROM inventario_movimientos im
                         INNER JOIN productos p ON im.producto_id = p.id
                         ORDER BY im.fecha_movimiento DESC 
                         LIMIT 5";
        
        $inventoryStmt = $this->db->prepare($inventorySql);
        $inventoryStmt->execute();
        $movements = $inventoryStmt->fetchAll();
        
        foreach ($movements as $movement) {
            $activities[] = [
                'type' => 'inventory',
                'icon' => 'fa-boxes',
                'color' => 'info',
                'title' => "Movimiento de inventario",
                'description' => "{$movement['tipo']} de {$movement['cantidad']} {$movement['nombre']}",
                'timestamp' => $movement['created_at'],
                'formatted_time' => formatDateTime($movement['created_at'])
            ];
        }
        
        // Ordenar por timestamp y limitar
        usort($activities, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return array_slice($activities, 0, $limit);
    }
    
    /**
     * Obtiene comparación con período anterior
     */
    private function getPeriodComparison($period, $startDate, $endDate) {
        // Calcular período anterior
        $days = (strtotime($endDate) - strtotime($startDate)) / (24 * 3600) + 1;
        $prevEndDate = date('Y-m-d', strtotime($startDate . ' -1 day'));
        $prevStartDate = date('Y-m-d', strtotime($prevEndDate . " -{$days} days"));
        
        // Estadísticas período actual
        $currentStats = $this->getSalesStatistics($startDate, $endDate);
        
        // Estadísticas período anterior
        $prevStats = $this->getSalesStatistics($prevStartDate, $prevEndDate);
        
        // Calcular cambios porcentuales
        $changes = [];
        $keys = ['total_ingresos', 'total_ventas', 'ventas_completadas', 'ticket_promedio'];
        
        foreach ($keys as $key) {
            $current = $currentStats[$key];
            $previous = $prevStats[$key];
            
            if ($previous > 0) {
                $change = (($current - $previous) / $previous) * 100;
            } else {
                $change = $current > 0 ? 100 : 0;
            }
            
            $changes[$key] = [
                'current' => $current,
                'previous' => $previous,
                'change_percent' => round($change, 2),
                'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral')
            ];
        }
        
        return [
            'current_period' => ['start' => $startDate, 'end' => $endDate],
            'previous_period' => ['start' => $prevStartDate, 'end' => $prevEndDate],
            'changes' => $changes
        ];
    }
    
    /**
     * Obtiene rango de fechas según el período
     */
    private function getDateRange($period) {
        switch ($period) {
            case 'today':
                return [date('Y-m-d'), date('Y-m-d')];
            case 'yesterday':
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                return [$yesterday, $yesterday];
            case 'week':
                return [date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week'))];
            case 'month':
                return [date('Y-m-01'), date('Y-m-t')];
            case 'year':
                return [date('Y-01-01'), date('Y-12-31')];
            default:
                return [date('Y-m-d'), date('Y-m-d')];
        }
    }
    
    /**
     * Obtiene etiqueta del período
     */
    private function getPeriodLabel($period, $startDate, $endDate) {
        switch ($period) {
            case 'today':
                return 'Hoy - ' . formatDate($startDate);
            case 'yesterday':
                return 'Ayer - ' . formatDate($startDate);
            case 'week':
                return 'Esta semana - ' . formatDate($startDate) . ' al ' . formatDate($endDate);
            case 'month':
                return 'Este mes - ' . date('F Y', strtotime($startDate));
            case 'year':
                return 'Este año - ' . date('Y', strtotime($startDate));
            default:
                return formatDate($startDate) . ' al ' . formatDate($endDate);
        }
    }
    
    /**
     * Obtiene cláusula GROUP BY según el tipo
     */
    private function getGroupByClause($type) {
        switch ($type) {
            case 'daily':
                return "DATE(fecha_venta)";
            case 'weekly':
                return "YEARWEEK(fecha_venta)";
            case 'monthly':
                return "DATE_FORMAT(fecha_venta, '%Y-%m')";
            default:
                return "DATE(fecha_venta)";
        }
    }
    
    /**
     * Obtiene gráfico de productos más vendidos
     */
    private function getTopSellingProductsChart($period, $limit) {
        [$startDate, $endDate] = $this->getDateRange($period);
        
        $sql = "SELECT 
                    p.nombre,
                    SUM(vd.cantidad) as total_vendido
                FROM venta_detalles vd
                INNER JOIN ventas v ON vd.venta_id = v.id
                INNER JOIN productos p ON vd.producto_id = p.id
                WHERE DATE(v.fecha_venta) BETWEEN :start_date AND :end_date
                AND v.estado = 'completada'
                GROUP BY p.id, p.nombre
                ORDER BY total_vendido DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $products = $stmt->fetchAll();
        
        return [
            'labels' => array_column($products, 'nombre'),
            'datasets' => [[
                'label' => 'Cantidad Vendida',
                'data' => array_map('intval', array_column($products, 'total_vendido')),
                'backgroundColor' => [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 205, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)',
                    'rgba(199, 199, 199, 0.8)',
                    'rgba(83, 102, 255, 0.8)',
                    'rgba(255, 99, 255, 0.8)',
                    'rgba(99, 255, 132, 0.8)'
                ]
            ]]
        ];
    }
    
    /**
     * Obtiene gráfico de productos por categoría
     */
    private function getProductsByCategoryChart($period) {
        [$startDate, $endDate] = $this->getDateRange($period);
        
        $sql = "SELECT 
                    c.nombre as categoria,
                    SUM(vd.cantidad) as total_vendido
                FROM venta_detalles vd
                INNER JOIN ventas v ON vd.venta_id = v.id
                INNER JOIN productos p ON vd.producto_id = p.id
                INNER JOIN categorias c ON p.categoria_id = c.id
                WHERE DATE(v.fecha_venta) BETWEEN :start_date AND :end_date
                AND v.estado = 'completada'
                GROUP BY c.id, c.nombre
                ORDER BY total_vendido DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        $categories = $stmt->fetchAll();
        
        return [
            'labels' => array_column($categories, 'categoria'),
            'datasets' => [[
                'label' => 'Ventas por Categoría',
                'data' => array_map('intval', array_column($categories, 'total_vendido')),
                'backgroundColor' => [
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(23, 162, 184, 0.8)',
                    'rgba(108, 117, 125, 0.8)'
                ]
            ]]
        ];
    }
    
    /**
     * Obtiene gráfico de productos con stock bajo
     */
    private function getLowStockProductsChart() {
        $sql = "SELECT 
                    nombre,
                    stock_actual,
                    stock_minimo
                FROM productos 
                WHERE estado = 'activo' 
                AND stock_actual <= stock_minimo
                ORDER BY (stock_actual / NULLIF(stock_minimo, 0)) ASC
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $products = $stmt->fetchAll();
        
        return [
            'labels' => array_column($products, 'nombre'),
            'datasets' => [
                [
                    'label' => 'Stock Actual',
                    'data' => array_map('intval', array_column($products, 'stock_actual')),
                    'backgroundColor' => 'rgba(220, 53, 69, 0.8)'
                ],
                [
                    'label' => 'Stock Mínimo',
                    'data' => array_map('intval', array_column($products, 'stock_minimo')),
                    'backgroundColor' => 'rgba(255, 193, 7, 0.8)'
                ]
            ]
        ];
    }
    
    /**
     * Genera mensaje de alerta
     */
    private function generateAlertMessage($alert) {
        if ($alert['stock_actual'] <= 0) {
            return "Sin stock disponible";
        } elseif ($alert['stock_actual'] <= $alert['stock_minimo']) {
            return "Stock bajo: {$alert['stock_actual']}/{$alert['stock_minimo']}";
        }
        return '';
    }
}

// Inicializar API y manejar petición
try {
    $api = new DashboardAPI();
    $api->handleRequest();
} catch (Exception $e) {
    Logger::error('Error fatal en dashboard.php: ' . $e->getMessage());
    Response::error('Error interno del servidor', 500);
}

?>
