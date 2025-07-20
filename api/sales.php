<?php
/**
 * SISTEMA DE GESTIÓN DE PANADERÍA - API ENDPOINT VENTAS
 * 
 * Este endpoint maneja todas las operaciones relacionadas con ventas
 * incluyendo punto de venta, historial, reportes y gestión de transacciones.
 * 
 * Métodos soportados:
 * - GET: Listar ventas, obtener venta por ID, reportes
 * - POST: Crear nueva venta, procesar transacción
 * - PUT: Actualizar venta existente
 * - DELETE: Cancelar venta
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
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=UTF-8');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Clase SalesAPI - Maneja operaciones de ventas
 */
class SalesAPI {
    
    private $db;
    private $salesTable = 'ventas';
    private $detailsTable = 'venta_detalles';
    
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
            $segments = explode('/', trim($pathInfo, '/'));
            $id = is_numeric($segments[0] ?? '') ? (int)$segments[0] : null;
            $action = $segments[1] ?? '';
            
            switch ($method) {
                case 'GET':
                    if ($id && $action === 'receipt') {
                        $this->getSaleReceipt($id);
                    } elseif ($id) {
                        $this->getSale($id);
                    } elseif ($action === 'reports') {
                        $this->getSalesReports();
                    } elseif ($action === 'daily-summary') {
                        $this->getDailySummary();
                    } else {
                        $this->getAllSales();
                    }
                    break;
                    
                case 'POST':
                    if ($action === 'process') {
                        $this->processSale();
                    } else {
                        $this->createSale();
                    }
                    break;
                    
                case 'PUT':
                    if (!$id) {
                        Response::error('ID de venta requerido para actualización', 400);
                    }
                    if ($action === 'cancel') {
                        $this->cancelSale($id);
                    } else {
                        $this->updateSale($id);
                    }
                    break;
                    
                case 'DELETE':
                    if (!$id) {
                        Response::error('ID de venta requerido para eliminación', 400);
                    }
                    $this->deleteSale($id);
                    break;
                    
                default:
                    Response::error('Método no permitido', 405);
            }
            
        } catch (Exception $e) {
            Logger::error('Error en SalesAPI: ' . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Obtiene todas las ventas con filtros opcionales
     */
    private function getAllSales() {
        try {
            // Obtener parámetros de consulta
            $page = max(1, (int)getGet('page', 1));
            $limit = min(max(1, (int)getGet('limit', DEFAULT_PAGE_SIZE)), MAX_PAGE_SIZE);
            $search = getGet('search', '');
            $status = getGet('status', '');
            $employee = getGet('employee', '');
            $dateFrom = getGet('date_from', '');
            $dateTo = getGet('date_to', '');
            $paymentMethod = getGet('payment_method', '');
            $sortBy = getGet('sort', 'fecha_venta');
            $sortOrder = strtoupper(getGet('order', 'DESC'));
            
            // Validar parámetros de ordenamiento
            $allowedSortFields = ['id', 'numero_factura', 'total', 'fecha_venta', 'estado'];
            $allowedSortOrders = ['ASC', 'DESC'];
            
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'fecha_venta';
            }
            
            if (!in_array($sortOrder, $allowedSortOrders)) {
                $sortOrder = 'DESC';
            }
            
            // Construir consulta base
            $sql = "SELECT v.*, e.nombre as empleado_nombre, e.apellido as empleado_apellido,
                           (SELECT COUNT(*) FROM {$this->detailsTable} vd WHERE vd.venta_id = v.id) as items_count
                    FROM {$this->salesTable} v 
                    LEFT JOIN empleados e ON v.empleado_id = e.id 
                    WHERE 1=1";
            
            $params = [];
            
            // Aplicar filtros
            if (!empty($search)) {
                $sql .= " AND (v.numero_factura LIKE :search OR v.cliente_nombre LIKE :search)";
                $params['search'] = "%{$search}%";
            }
            
            if (!empty($status)) {
                $sql .= " AND v.estado = :status";
                $params['status'] = $status;
            }
            
            if (!empty($employee)) {
                $sql .= " AND v.empleado_id = :employee";
                $params['employee'] = $employee;
            }
            
            if (!empty($paymentMethod)) {
                $sql .= " AND v.metodo_pago = :payment_method";
                $params['payment_method'] = $paymentMethod;
            }
            
            if (!empty($dateFrom)) {
                $sql .= " AND DATE(v.fecha_venta) >= :date_from";
                $params['date_from'] = $dateFrom;
            }
            
            if (!empty($dateTo)) {
                $sql .= " AND DATE(v.fecha_venta) <= :date_to";
                $params['date_to'] = $dateTo;
            }
            
            // Contar total de registros para paginación
            $countSql = str_replace('SELECT v.*, e.nombre as empleado_nombre, e.apellido as empleado_apellido, (SELECT COUNT(*) FROM venta_detalles vd WHERE vd.venta_id = v.id) as items_count', 'SELECT COUNT(*)', $sql);
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $totalRecords = $countStmt->fetchColumn();
            
            // Aplicar ordenamiento y paginación
            $sql .= " ORDER BY v.{$sortBy} {$sortOrder}";
            $offset = ($page - 1) * $limit;
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
            
            // Ejecutar consulta principal
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $sales = $stmt->fetchAll();
            
            // Procesar resultados
            $sales = array_map([$this, 'formatSale'], $sales);
            
            // Calcular información de paginación
            $totalPages = ceil($totalRecords / $limit);
            
            // Obtener estadísticas resumidas
            $summaryStats = $this->getSalesSummary($params);
            
            // Respuesta con metadatos
            Response::success([
                'sales' => $sales,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $totalRecords,
                    'per_page' => $limit,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ],
                'filters' => [
                    'search' => $search,
                    'status' => $status,
                    'employee' => $employee,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'payment_method' => $paymentMethod,
                    'sort' => $sortBy,
                    'order' => $sortOrder
                ],
                'summary' => $summaryStats
            ], 'Ventas obtenidas exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo ventas: ' . $e->getMessage());
            Response::error('Error al obtener ventas', 500);
        }
    }
    
    /**
     * Obtiene una venta específica por ID
     * 
     * @param int $id ID de la venta
     */
    private function getSale($id) {
        try {
            $sql = "SELECT v.*, e.nombre as empleado_nombre, e.apellido as empleado_apellido
                    FROM {$this->salesTable} v 
                    LEFT JOIN empleados e ON v.empleado_id = e.id 
                    WHERE v.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $sale = $stmt->fetch();
            
            if (!$sale) {
                Response::error('Venta no encontrada', 404);
            }
            
            // Obtener detalles de la venta
            $detailsSql = "SELECT vd.*, p.nombre as producto_nombre, p.codigo as producto_codigo
                          FROM {$this->detailsTable} vd 
                          INNER JOIN productos p ON vd.producto_id = p.id 
                          WHERE vd.venta_id = :id 
                          ORDER BY vd.id";
            
            $detailsStmt = $this->db->prepare($detailsSql);
            $detailsStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $detailsStmt->execute();
            $details = $detailsStmt->fetchAll();
            
            // Formatear venta
            $sale = $this->formatSale($sale);
            $sale['items'] = array_map([$this, 'formatSaleDetail'], $details);
            
            Response::success($sale, 'Venta obtenida exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo venta: ' . $e->getMessage());
            Response::error('Error al obtener venta', 500);
        }
    }
    
    /**
     * Procesa una nueva venta completa (transacción)
     */
    private function processSale() {
        try {
            $data = getJsonInput();
            
            // Validar datos de la venta
            $this->validateSaleData($data);
            
            // Iniciar transacción
            $this->db->beginTransaction();
            
            try {
                // Verificar disponibilidad de stock
                $this->validateStock($data['items']);
                
                // Crear la venta
                $saleId = $this->createSaleRecord($data);
                
                // Crear los detalles de venta
                $this->createSaleDetails($saleId, $data['items']);
                
                // Actualizar inventario
                $this->updateInventory($data['items'], $saleId);
                
                // Confirmar transacción
                $this->db->commit();
                
                // Obtener la venta completa creada
                $completeSale = $this->getSaleById($saleId);
                
                Logger::info('Venta procesada exitosamente', [
                    'sale_id' => $saleId, 
                    'total' => $data['total'],
                    'items_count' => count($data['items'])
                ]);
                
                Response::success([
                    'sale' => $completeSale,
                    'receipt_url' => API_URL . "/sales/{$saleId}/receipt"
                ], 'Venta procesada exitosamente', 201);
                
            } catch (Exception $e) {
                // Rollback en caso de error
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            Logger::error('Error procesando venta: ' . $e->getMessage());
            Response::error('Error al procesar venta: ' . $e->getMessage(), 400);
        }
    }
    
    /**
     * Crea una nueva venta (sin procesar items)
     */
    private function createSale() {
        try {
            $data = getJsonInput();
            
            // Validar datos básicos
            $this->validateBasicSaleData($data);
            
            $saleId = $this->createSaleRecord($data);
            $newSale = $this->getSaleById($saleId);
            
            Logger::info('Venta creada', ['sale_id' => $saleId]);
            Response::success($newSale, 'Venta creada exitosamente', 201);
            
        } catch (Exception $e) {
            Logger::error('Error creando venta: ' . $e->getMessage());
            Response::error('Error al crear venta: ' . $e->getMessage(), 400);
        }
    }
    
    /**
     * Actualiza una venta existente
     * 
     * @param int $id ID de la venta
     */
    private function updateSale($id) {
        try {
            // Verificar que la venta existe
            $sale = $this->getSaleById($id);
            if (!$sale) {
                Response::error('Venta no encontrada', 404);
            }
            
            // Solo permitir actualizar ventas pendientes
            if ($sale['estado'] !== 'pendiente') {
                Response::error('Solo se pueden actualizar ventas pendientes', 400);
            }
            
            $data = getJsonInput();
            
            // Validar datos básicos
            $this->validateBasicSaleData($data);
            
            // Actualizar venta
            $sql = "UPDATE {$this->salesTable} SET 
                    cliente_nombre = :cliente_nombre,
                    cliente_telefono = :cliente_telefono,
                    subtotal = :subtotal,
                    impuestos = :impuestos,
                    total = :total,
                    metodo_pago = :metodo_pago,
                    notas = :notas,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':cliente_nombre', $data['cliente_nombre'] ?? '');
            $stmt->bindParam(':cliente_telefono', $data['cliente_telefono'] ?? '');
            $stmt->bindParam(':subtotal', $data['subtotal'] ?? 0);
            $stmt->bindParam(':impuestos', $data['impuestos'] ?? 0);
            $stmt->bindParam(':total', $data['total']);
            $stmt->bindParam(':metodo_pago', $data['metodo_pago']);
            $stmt->bindParam(':notas', $data['notas'] ?? '');
            
            $stmt->execute();
            
            // Obtener venta actualizada
            $updatedSale = $this->getSaleById($id);
            
            Logger::info('Venta actualizada', ['sale_id' => $id]);
            Response::success($updatedSale, 'Venta actualizada exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error actualizando venta: ' . $e->getMessage());
            Response::error('Error al actualizar venta: ' . $e->getMessage(), 400);
        }
    }
    
    /**
     * Elimina una venta (hard delete - solo para ventas pendientes)
     * 
     * @param int $id ID de la venta
     */
    private function deleteSale($id) {
        try {
            // Verificar que la venta existe
            $sale = $this->getSaleById($id);
            if (!$sale) {
                Response::error('Venta no encontrada', 404);
            }
            
            // Solo permitir eliminar ventas pendientes
            if ($sale['estado'] !== 'pendiente') {
                Response::error('Solo se pueden eliminar ventas pendientes', 400);
            }
            
            // Iniciar transacción
            $this->db->beginTransaction();
            
            try {
                // Eliminar detalles de venta
                $deleteDetailsSql = "DELETE FROM {$this->detailsTable} WHERE venta_id = :id";
                $deleteDetailsStmt = $this->db->prepare($deleteDetailsSql);
                $deleteDetailsStmt->bindParam(':id', $id, PDO::PARAM_INT);
                $deleteDetailsStmt->execute();
                
                // Eliminar venta
                $deleteSaleSql = "DELETE FROM {$this->salesTable} WHERE id = :id";
                $deleteSaleStmt = $this->db->prepare($deleteSaleSql);
                $deleteSaleStmt->bindParam(':id', $id, PDO::PARAM_INT);
                $deleteSaleStmt->execute();
                
                $this->db->commit();
                
                Logger::info('Venta eliminada', ['sale_id' => $id]);
                Response::success(null, 'Venta eliminada exitosamente');
                
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            Logger::error('Error eliminando venta: ' . $e->getMessage());
            Response::error('Error al eliminar venta', 500);
        }
    }
    
    /**
     * Cancela una venta
     * 
     * @param int $id ID de la venta
     */
    private function cancelSale($id) {
        try {
            // Verificar que la venta existe y se puede cancelar
            $sale = $this->getSaleById($id);
            if (!$sale) {
                Response::error('Venta no encontrada', 404);
            }
            
            if ($sale['estado'] === 'cancelada') {
                Response::error('La venta ya está cancelada', 400);
            }
            
            // Iniciar transacción
            $this->db->beginTransaction();
            
            try {
                // Actualizar estado de la venta
                $sql = "UPDATE {$this->salesTable} SET estado = 'cancelada', updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
                
                // Restaurar inventario si la venta estaba completada
                if ($sale['estado'] === 'completada') {
                    $this->restoreInventory($id);
                }
                
                $this->db->commit();
                
                Logger::info('Venta cancelada', ['sale_id' => $id]);
                Response::success(null, 'Venta cancelada exitosamente');
                
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            Logger::error('Error cancelando venta: ' . $e->getMessage());
            Response::error('Error al cancelar venta', 500);
        }
    }
    
    /**
     * Obtiene el recibo de una venta
     * 
     * @param int $id ID de la venta
     */
    private function getSaleReceipt($id) {
        try {
            $sale = $this->getSaleById($id);
            if (!$sale) {
                Response::error('Venta no encontrada', 404);
            }
            
            // Obtener configuración de la empresa
            $companyInfo = $this->getCompanyInfo();
            
            $receipt = [
                'company' => $companyInfo,
                'sale' => $sale,
                'generated_at' => date('Y-m-d H:i:s'),
                'receipt_number' => $sale['numero_factura']
            ];
            
            Response::success($receipt, 'Recibo generado exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error generando recibo: ' . $e->getMessage());
            Response::error('Error al generar recibo', 500);
        }
    }
    
    /**
     * Obtiene reportes de ventas
     */
    private function getSalesReports() {
        try {
            $period = getGet('period', 'today'); // today, week, month, year, custom
            $dateFrom = getGet('date_from', '');
            $dateTo = getGet('date_to', '');
            
            // Determinar rango de fechas según el período
            [$startDate, $endDate] = $this->getDateRange($period, $dateFrom, $dateTo);
            
            // Obtener estadísticas generales
            $generalStats = $this->getGeneralStats($startDate, $endDate);
            
            // Obtener ventas por día
            $dailySales = $this->getDailySales($startDate, $endDate);
            
            // Obtener productos más vendidos
            $topProducts = $this->getTopProducts($startDate, $endDate);
            
            // Obtener ventas por método de pago
            $paymentMethods = $this->getSalesByPaymentMethod($startDate, $endDate);
            
            // Obtener ventas por empleado
            $salesByEmployee = $this->getSalesByEmployee($startDate, $endDate);
            
            Response::success([
                'period' => [
                    'type' => $period,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'general_stats' => $generalStats,
                'daily_sales' => $dailySales,
                'top_products' => $topProducts,
                'payment_methods' => $paymentMethods,
                'sales_by_employee' => $salesByEmployee
            ], 'Reporte de ventas generado exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error generando reporte: ' . $e->getMessage());
            Response::error('Error al generar reporte', 500);
        }
    }
    
    /**
     * Obtiene resumen del día actual
     */
    private function getDailySummary() {
        try {
            $today = date('Y-m-d');
            
            // Ventas del día
            $sql = "SELECT 
                        COUNT(*) as total_ventas,
                        SUM(CASE WHEN estado = 'completada' THEN total ELSE 0 END) as total_ingresos,
                        AVG(CASE WHEN estado = 'completada' THEN total ELSE NULL END) as promedio_venta,
                        SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as ventas_completadas,
                        SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as ventas_canceladas
                    FROM {$this->salesTable} 
                    WHERE DATE(fecha_venta) = :today";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':today', $today);
            $stmt->execute();
            $summary = $stmt->fetch();
            
            // Método de pago más usado
            $paymentSql = "SELECT metodo_pago, COUNT(*) as total 
                          FROM {$this->salesTable} 
                          WHERE DATE(fecha_venta) = :today AND estado = 'completada'
                          GROUP BY metodo_pago 
                          ORDER BY total DESC 
                          LIMIT 1";
            
            $paymentStmt = $this->db->prepare($paymentSql);
            $paymentStmt->bindParam(':today', $today);
            $paymentStmt->execute();
            $topPaymentMethod = $paymentStmt->fetch();
            
            // Producto más vendido del día
            $productSql = "SELECT p.nombre, SUM(vd.cantidad) as total_vendido
                          FROM {$this->detailsTable} vd
                          INNER JOIN {$this->salesTable} v ON vd.venta_id = v.id
                          INNER JOIN productos p ON vd.producto_id = p.id
                          WHERE DATE(v.fecha_venta) = :today AND v.estado = 'completada'
                          GROUP BY vd.producto_id, p.nombre
                          ORDER BY total_vendido DESC
                          LIMIT 1";
            
            $productStmt = $this->db->prepare($productSql);
            $productStmt->bindParam(':today', $today);
            $productStmt->execute();
            $topProduct = $productStmt->fetch();
            
            Response::success([
                'date' => $today,
                'summary' => [
                    'total_ventas' => (int)$summary['total_ventas'],
                    'total_ingresos' => (float)$summary['total_ingresos'],
                    'promedio_venta' => (float)$summary['promedio_venta'],
                    'ventas_completadas' => (int)$summary['ventas_completadas'],
                    'ventas_canceladas' => (int)$summary['ventas_canceladas'],
                    'total_ingresos_formateado' => formatCurrency($summary['total_ingresos']),
                    'promedio_venta_formateado' => formatCurrency($summary['promedio_venta'])
                ],
                'top_payment_method' => $topPaymentMethod ? [
                    'method' => $topPaymentMethod['metodo_pago'],
                    'count' => (int)$topPaymentMethod['total']
                ] : null,
                'top_product' => $topProduct ? [
                    'name' => $topProduct['nombre'],
                    'quantity' => (int)$topProduct['total_vendido']
                ] : null
            ], 'Resumen diario obtenido exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo resumen diario: ' . $e->getMessage());
            Response::error('Error al obtener resumen diario', 500);
        }
    }
    
    /**
     * Valida los datos completos de una venta
     * 
     * @param array $data Datos de la venta
     */
    private function validateSaleData($data) {
        // Validaciones básicas
        $this->validateBasicSaleData($data);
        
        // Validar items
        if (empty($data['items']) || !is_array($data['items'])) {
            throw new Exception('La venta debe incluir al menos un producto');
        }
        
        // Validar cada item
        foreach ($data['items'] as $item) {
            if (empty($item['producto_id']) || empty($item['cantidad']) || empty($item['precio_unitario'])) {
                throw new Exception('Cada item debe incluir producto_id, cantidad y precio_unitario');
            }
            
            Validator::positiveNumber($item['cantidad'], 'Cantidad');
            Validator::positiveNumber($item['precio_unitario'], 'Precio unitario');
        }
    }
    
    /**
     * Valida los datos básicos de una venta
     * 
     * @param array $data Datos de la venta
     */
    private function validateBasicSaleData($data) {
        Validator::required($data['metodo_pago'] ?? '', 'Método de pago');
        Validator::positiveNumber($data['total'] ?? 0, 'Total');
        
        // Validar métodos de pago permitidos
        $allowedMethods = array_keys(PAYMENT_METHODS);
        Validator::inArray($data['metodo_pago'], $allowedMethods, 'Método de pago');
    }
    
    /**
     * Valida disponibilidad de stock
     * 
     * @param array $items Items de la venta
     */
    private function validateStock($items) {
        foreach ($items as $item) {
            $sql = "SELECT stock_actual, nombre FROM productos WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $item['producto_id'], PDO::PARAM_INT);
            $stmt->execute();
            $product = $stmt->fetch();
            
            if (!$product) {
                throw new Exception("Producto con ID {$item['producto_id']} no encontrado");
            }
            
            if ($product['stock_actual'] < $item['cantidad']) {
                throw new Exception("Stock insuficiente para {$product['nombre']}. Disponible: {$product['stock_actual']}, Solicitado: {$item['cantidad']}");
            }
        }
    }
    
    /**
     * Crea el registro principal de la venta
     * 
     * @param array $data Datos de la venta
     * @return int ID de la venta creada
     */
    private function createSaleRecord($data) {
        $numeroFactura = $data['numero_factura'] ?? generateInvoiceNumber('FAC');
        
        $sql = "INSERT INTO {$this->salesTable} 
                (numero_factura, empleado_id, cliente_nombre, cliente_telefono, 
                 subtotal, impuestos, total, metodo_pago, estado, notas) 
                VALUES 
                (:numero_factura, :empleado_id, :cliente_nombre, :cliente_telefono, 
                 :subtotal, :impuestos, :total, :metodo_pago, :estado, :notas)";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':numero_factura', $numeroFactura);
        $stmt->bindParam(':empleado_id', $data['empleado_id'] ?? 1); // Default empleado
        $stmt->bindParam(':cliente_nombre', $data['cliente_nombre'] ?? '');
        $stmt->bindParam(':cliente_telefono', $data['cliente_telefono'] ?? '');
        $stmt->bindParam(':subtotal', $data['subtotal'] ?? 0);
        $stmt->bindParam(':impuestos', $data['impuestos'] ?? 0);
        $stmt->bindParam(':total', $data['total']);
        $stmt->bindParam(':metodo_pago', $data['metodo_pago']);
        $stmt->bindParam(':estado', $data['estado'] ?? 'completada');
        $stmt->bindParam(':notas', $data['notas'] ?? '');
        
        $stmt->execute();
        return $this->db->lastInsertId();
    }
    
    /**
     * Crea los detalles de la venta
     * 
     * @param int $saleId ID de la venta
     * @param array $items Items de la venta
     */
    private function createSaleDetails($saleId, $items) {
        $sql = "INSERT INTO {$this->detailsTable} 
                (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                VALUES 
                (:venta_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($items as $item) {
            $subtotal = $item['cantidad'] * $item['precio_unitario'];
            
            $stmt->bindParam(':venta_id', $saleId, PDO::PARAM_INT);
            $stmt->bindParam(':producto_id', $item['producto_id'], PDO::PARAM_INT);
            $stmt->bindParam(':cantidad', $item['cantidad']);
            $stmt->bindParam(':precio_unitario', $item['precio_unitario']);
            $stmt->bindParam(':subtotal', $subtotal);
            
            $stmt->execute();
        }
    }
    
    /**
     * Actualiza el inventario después de una venta
     * 
     * @param array $items Items vendidos
     * @param int $saleId ID de la venta
     */
    private function updateInventory($items, $saleId) {
        foreach ($items as $item) {
            // Actualizar stock del producto
            $sql = "UPDATE productos SET stock_actual = stock_actual - :cantidad WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':cantidad', $item['cantidad']);
            $stmt->bindParam(':id', $item['producto_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            // Registrar movimiento de inventario
            $movSql = "INSERT INTO inventario_movimientos 
                      (producto_id, tipo, cantidad, motivo, referencia_id, usuario, fecha_movimiento) 
                      VALUES 
                      (:producto_id, 'salida', :cantidad, :motivo, :referencia_id, :usuario, NOW())";
            
            $movStmt = $this->db->prepare($movSql);
            $movStmt->bindParam(':producto_id', $item['producto_id'], PDO::PARAM_INT);
            $movStmt->bindParam(':cantidad', $item['cantidad']);
            $movStmt->bindValue(':motivo', 'Venta');
            $movStmt->bindParam(':referencia_id', $saleId, PDO::PARAM_INT);
            $movStmt->bindValue(':usuario', 'Sistema'); // En futuro usar usuario actual
            $movStmt->execute();
        }
    }
    
    /**
     * Obtiene una venta completa por ID
     * 
     * @param int $id ID de la venta
     * @return array|null Datos de la venta
     */
    private function getSaleById($id) {
        $sql = "SELECT v.*, e.nombre as empleado_nombre, e.apellido as empleado_apellido
                FROM {$this->salesTable} v 
                LEFT JOIN empleados e ON v.empleado_id = e.id 
                WHERE v.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $sale = $stmt->fetch();
        
        if (!$sale) return null;
        
        // Obtener detalles
        $detailsSql = "SELECT vd.*, p.nombre as producto_nombre, p.codigo as producto_codigo
                      FROM {$this->detailsTable} vd 
                      INNER JOIN productos p ON vd.producto_id = p.id 
                      WHERE vd.venta_id = :id";
        
        $detailsStmt = $this->db->prepare($detailsSql);
        $detailsStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $detailsStmt->execute();
        $details = $detailsStmt->fetchAll();
        
        $sale = $this->formatSale($sale);
        $sale['items'] = array_map([$this, 'formatSaleDetail'], $details);
        
        return $sale;
    }
    
    /**
     * Formatea una venta para la respuesta
     * 
     * @param array $sale Datos de la venta
     * @return array Venta formateada
     */
    private function formatSale($sale) {
        return [
            'id' => (int)$sale['id'],
            'numero_factura' => $sale['numero_factura'],
            'empleado_id' => (int)$sale['empleado_id'],
            'empleado_nombre' => isset($sale['empleado_nombre']) ? $sale['empleado_nombre'] . ' ' . $sale['empleado_apellido'] : null,
            'cliente_nombre' => $sale['cliente_nombre'],
            'cliente_telefono' => $sale['cliente_telefono'],
            'subtotal' => (float)$sale['subtotal'],
            'impuestos' => (float)$sale['impuestos'],
            'total' => (float)$sale['total'],
            'metodo_pago' => $sale['metodo_pago'],
            'metodo_pago_nombre' => PAYMENT_METHODS[$sale['metodo_pago']] ?? $sale['metodo_pago'],
            'estado' => $sale['estado'],
            'estado_nombre' => SALE_STATUS[$sale['estado']] ?? $sale['estado'],
            'notas' => $sale['notas'],
            'fecha_venta' => $sale['fecha_venta'],
            'fecha_venta_formateada' => formatDateTime($sale['fecha_venta']),
            'total_formateado' => formatCurrency($sale['total']),
            'items_count' => isset($sale['items_count']) ? (int)$sale['items_count'] : 0,
            'created_at' => formatDateTime($sale['created_at']),
            'updated_at' => formatDateTime($sale['updated_at'])
        ];
    }
    
    /**
     * Formatea un detalle de venta para la respuesta
     * 
     * @param array $detail Datos del detalle
     * @return array Detalle formateado
     */
    private function formatSaleDetail($detail) {
        return [
            'id' => (int)$detail['id'],
            'producto_id' => (int)$detail['producto_id'],
            'producto_nombre' => $detail['producto_nombre'],
            'producto_codigo' => $detail['producto_codigo'],
            'cantidad' => (int)$detail['cantidad'],
            'precio_unitario' => (float)$detail['precio_unitario'],
            'subtotal' => (float)$detail['subtotal'],
            'precio_unitario_formateado' => formatCurrency($detail['precio_unitario']),
            'subtotal_formateado' => formatCurrency($detail['subtotal'])
        ];
    }
    
    /**
     * Obtiene información de la empresa para recibos
     * 
     * @return array Información de la empresa
     */
    private function getCompanyInfo() {
        // En una implementación real, esto vendría de una tabla de configuración
        return [
            'nombre' => 'Panadería San Miguel',
            'direccion' => 'Calle Principal #123, Centro',
            'telefono' => '555-0123',
            'email' => 'info@panaderiasanmiguel.com',
            'rfc' => 'PSM123456ABC'
        ];
    }
    
    /**
     * Obtiene rango de fechas según el período
     * 
     * @param string $period Período solicitado
     * @param string $dateFrom Fecha desde (para custom)
     * @param string $dateTo Fecha hasta (para custom)
     * @return array [fecha_inicio, fecha_fin]
     */
    private function getDateRange($period, $dateFrom = '', $dateTo = '') {
        switch ($period) {
            case 'today':
                return [date('Y-m-d'), date('Y-m-d')];
            case 'week':
                return [date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week'))];
            case 'month':
                return [date('Y-m-01'), date('Y-m-t')];
            case 'year':
                return [date('Y-01-01'), date('Y-12-31')];
            case 'custom':
                return [$dateFrom ?: date('Y-m-d'), $dateTo ?: date('Y-m-d')];
            default:
                return [date('Y-m-d'), date('Y-m-d')];
        }
    }
    
    /**
     * Obtiene estadísticas generales de ventas
     * 
     * @param string $startDate Fecha inicio
     * @param string $endDate Fecha fin
     * @return array Estadísticas
     */
    private function getGeneralStats($startDate, $endDate) {
        $sql = "SELECT 
                    COUNT(*) as total_ventas,
                    SUM(CASE WHEN estado = 'completada' THEN total ELSE 0 END) as total_ingresos,
                    AVG(CASE WHEN estado = 'completada' THEN total ELSE NULL END) as promedio_venta,
                    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as ventas_completadas,
                    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as ventas_canceladas
                FROM {$this->salesTable} 
                WHERE DATE(fecha_venta) BETWEEN :start_date AND :end_date";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        $stats = $stmt->fetch();
        
        return [
            'total_ventas' => (int)$stats['total_ventas'],
            'total_ingresos' => (float)$stats['total_ingresos'],
            'promedio_venta' => (float)$stats['promedio_venta'],
            'ventas_completadas' => (int)$stats['ventas_completadas'],
            'ventas_canceladas' => (int)$stats['ventas_canceladas']
        ];
    }
    
    /**
     * Obtiene ventas por día en el rango especificado
     * 
     * @param string $startDate Fecha inicio
     * @param string $endDate Fecha fin
     * @return array Ventas por día
     */
    private function getDailySales($startDate, $endDate) {
        $sql = "SELECT 
                    DATE(fecha_venta) as fecha,
                    COUNT(*) as total_ventas,
                    SUM(CASE WHEN estado = 'completada' THEN total ELSE 0 END) as total_ingresos
                FROM {$this->salesTable} 
                WHERE DATE(fecha_venta) BETWEEN :start_date AND :end_date
                GROUP BY DATE(fecha_venta)
                ORDER BY fecha";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtiene productos más vendidos
     * 
     * @param string $startDate Fecha inicio
     * @param string $endDate Fecha fin
     * @return array Top productos
     */
    private function getTopProducts($startDate, $endDate) {
        $sql = "SELECT 
                    p.nombre,
                    p.codigo,
                    SUM(vd.cantidad) as total_vendido,
                    SUM(vd.subtotal) as total_ingresos
                FROM {$this->detailsTable} vd
                INNER JOIN {$this->salesTable} v ON vd.venta_id = v.id
                INNER JOIN productos p ON vd.producto_id = p.id
                WHERE DATE(v.fecha_venta) BETWEEN :start_date AND :end_date 
                AND v.estado = 'completada'
                GROUP BY vd.producto_id, p.nombre, p.codigo
                ORDER BY total_vendido DESC
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtiene ventas por método de pago
     * 
     * @param string $startDate Fecha inicio
     * @param string $endDate Fecha fin
     * @return array Ventas por método de pago
     */
    private function getSalesByPaymentMethod($startDate, $endDate) {
        $sql = "SELECT 
                    metodo_pago,
                    COUNT(*) as total_ventas,
                    SUM(total) as total_ingresos
                FROM {$this->salesTable} 
                WHERE DATE(fecha_venta) BETWEEN :start_date AND :end_date 
                AND estado = 'completada'
                GROUP BY metodo_pago
                ORDER BY total_ingresos DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtiene ventas por empleado
     * 
     * @param string $startDate Fecha inicio
     * @param string $endDate Fecha fin
     * @return array Ventas por empleado
     */
    private function getSalesByEmployee($startDate, $endDate) {
        $sql = "SELECT 
                    e.nombre,
                    e.apellido,
                    COUNT(*) as total_ventas,
                    SUM(v.total) as total_ingresos
                FROM {$this->salesTable} v
                INNER JOIN empleados e ON v.empleado_id = e.id
                WHERE DATE(v.fecha_venta) BETWEEN :start_date AND :end_date 
                AND v.estado = 'completada'
                GROUP BY v.empleado_id, e.nombre, e.apellido
                ORDER BY total_ingresos DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtiene resumen de ventas con filtros
     * 
     * @param array $params Parámetros de filtro
     * @return array Resumen
     */
    private function getSalesSummary($params) {
        $sql = "SELECT 
                    COUNT(*) as total_ventas,
                    SUM(CASE WHEN estado = 'completada' THEN total ELSE 0 END) as total_ingresos,
                    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as ventas_completadas
                FROM {$this->salesTable} v 
                LEFT JOIN empleados e ON v.empleado_id = e.id 
                WHERE 1=1";
        
        // Aplicar los mismos filtros que en getAllSales
        // (código similar al de getAllSales pero solo para el resumen)
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $summary = $stmt->fetch();
        
        return [
            'total_ventas' => (int)$summary['total_ventas'],
            'total_ingresos' => (float)$summary['total_ingresos'],
            'ventas_completadas' => (int)$summary['ventas_completadas']
        ];
    }
    
    /**
     * Restaura el inventario cuando se cancela una venta
     * 
     * @param int $saleId ID de la venta
     */
    private function restoreInventory($saleId) {
        // Obtener detalles de la venta cancelada
        $sql = "SELECT producto_id, cantidad FROM {$this->detailsTable} WHERE venta_id = :sale_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':sale_id', $saleId, PDO::PARAM_INT);
        $stmt->execute();
        $details = $stmt->fetchAll();
        
        foreach ($details as $detail) {
            // Restaurar stock
            $updateSql = "UPDATE productos SET stock_actual = stock_actual + :cantidad WHERE id = :id";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->bindParam(':cantidad', $detail['cantidad']);
            $updateStmt->bindParam(':id', $detail['producto_id'], PDO::PARAM_INT);
            $updateStmt->execute();
            
            // Registrar movimiento de inventario
            $movSql = "INSERT INTO inventario_movimientos 
                      (producto_id, tipo, cantidad, motivo, referencia_id, usuario, fecha_movimiento) 
                      VALUES 
                      (:producto_id, 'entrada', :cantidad, :motivo, :referencia_id, :usuario, NOW())";
            
            $movStmt = $this->db->prepare($movSql);
            $movStmt->bindParam(':producto_id', $detail['producto_id'], PDO::PARAM_INT);
            $movStmt->bindParam(':cantidad', $detail['cantidad']);
            $movStmt->bindValue(':motivo', 'Cancelación de venta');
            $movStmt->bindParam(':referencia_id', $saleId, PDO::PARAM_INT);
            $movStmt->bindValue(':usuario', 'Sistema');
            $movStmt->execute();
        }
    }
}

// Inicializar API y manejar petición
try {
    $api = new SalesAPI();
    $api->handleRequest();
} catch (Exception $e) {
    Logger::error('Error fatal en sales.php: ' . $e->getMessage());
    Response::error('Error interno del servidor', 500);
}

?>
