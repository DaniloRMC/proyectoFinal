<?php
/**
 * SISTEMA DE GESTIÓN DE PANADERÍA - API ENDPOINT INVENTARIO
 * 
 * Este endpoint maneja todas las operaciones relacionadas con inventario
 * incluyendo movimientos, ajustes, alertas de stock bajo y reportes.
 * 
 * Métodos soportados:
 * - GET: Listar movimientos, obtener alertas de stock, reportes
 * - POST: Registrar movimiento de inventario, ajustes de stock
 * - PUT: Actualizar movimiento
 * - DELETE: Eliminar movimiento (solo ajustes)
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
 * Clase InventoryAPI - Maneja operaciones de inventario
 */
class InventoryAPI {
    
    private $db;
    private $movementsTable = 'inventario_movimientos';
    private $productsTable = 'productos';
    
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
            $action = $segments[0] ?? '';
            
            switch ($method) {
                case 'GET':
                    if ($action === 'movements') {
                        $this->getMovements();
                    } elseif ($action === 'alerts') {
                        $this->getStockAlerts();
                    } elseif ($action === 'reports') {
                        $this->getInventoryReports();
                    } elseif ($action === 'summary') {
                        $this->getInventorySummary();
                    } elseif ($action === 'low-stock') {
                        $this->getLowStockProducts();
                    } elseif ($id) {
                        $this->getMovement($id);
                    } else {
                        $this->getInventoryStatus();
                    }
                    break;
                    
                case 'POST':
                    if ($action === 'movement') {
                        $this->createMovement();
                    } elseif ($action === 'adjust') {
                        $this->adjustStock();
                    } elseif ($action === 'bulk-adjust') {
                        $this->bulkStockAdjustment();
                    } else {
                        $this->createMovement();
                    }
                    break;
                    
                case 'PUT':
                    if (!$id) {
                        Response::error('ID de movimiento requerido para actualización', 400);
                    }
                    $this->updateMovement($id);
                    break;
                    
                case 'DELETE':
                    if (!$id) {
                        Response::error('ID de movimiento requerido para eliminación', 400);
                    }
                    $this->deleteMovement($id);
                    break;
                    
                default:
                    Response::error('Método no permitido', 405);
            }
            
        } catch (Exception $e) {
            Logger::error('Error en InventoryAPI: ' . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Obtiene el estado general del inventario
     */
    private function getInventoryStatus() {
        try {
            // Obtener parámetros de consulta
            $page = max(1, (int)getGet('page', 1));
            $limit = min(max(1, (int)getGet('limit', DEFAULT_PAGE_SIZE)), MAX_PAGE_SIZE);
            $search = getGet('search', '');
            $category = getGet('category', '');
            $lowStock = getGet('low_stock', '');
            $sortBy = getGet('sort', 'nombre');
            $sortOrder = strtoupper(getGet('order', 'ASC'));
            
            // Validar parámetros de ordenamiento
            $allowedSortFields = ['id', 'nombre', 'stock_actual', 'stock_minimo', 'categoria'];
            $allowedSortOrders = ['ASC', 'DESC'];
            
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'nombre';
            }
            
            if (!in_array($sortOrder, $allowedSortOrders)) {
                $sortOrder = 'ASC';
            }
            
            // Construir consulta base
            $sql = "SELECT p.id, p.codigo, p.nombre, p.stock_actual, p.stock_minimo, 
                           p.unidad_medida, p.estado, c.nombre as categoria_nombre,
                           p.precio, p.costo,
                           CASE 
                               WHEN p.stock_actual <= 0 THEN 'sin_stock'
                               WHEN p.stock_actual <= p.stock_minimo THEN 'stock_bajo'
                               ELSE 'stock_normal'
                           END as status_stock
                    FROM {$this->productsTable} p 
                    LEFT JOIN categorias c ON p.categoria_id = c.id 
                    WHERE p.estado = 'activo'";
            
            $params = [];
            
            // Aplicar filtros
            if (!empty($search)) {
                $sql .= " AND (p.nombre LIKE :search OR p.codigo LIKE :search)";
                $params['search'] = "%{$search}%";
            }
            
            if (!empty($category)) {
                $sql .= " AND p.categoria_id = :category";
                $params['category'] = $category;
            }
            
            if ($lowStock === 'true') {
                $sql .= " AND p.stock_actual <= p.stock_minimo";
            }
            
            // Contar total de registros para paginación
            $countSql = str_replace('SELECT p.id, p.codigo, p.nombre, p.stock_actual, p.stock_minimo, p.unidad_medida, p.estado, c.nombre as categoria_nombre, p.precio, p.costo, CASE WHEN p.stock_actual <= 0 THEN \'sin_stock\' WHEN p.stock_actual <= p.stock_minimo THEN \'stock_bajo\' ELSE \'stock_normal\' END as status_stock', 'SELECT COUNT(*)', $sql);
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $totalRecords = $countStmt->fetchColumn();
            
            // Aplicar ordenamiento y paginación
            $sql .= " ORDER BY p.{$sortBy} {$sortOrder}";
            $offset = ($page - 1) * $limit;
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
            
            // Ejecutar consulta principal
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll();
            
            // Procesar resultados
            $products = array_map([$this, 'formatInventoryItem'], $products);
            
            // Calcular información de paginación
            $totalPages = ceil($totalRecords / $limit);
            
            // Obtener estadísticas generales
            $generalStats = $this->getGeneralInventoryStats();
            
            // Respuesta con metadatos
            Response::success([
                'inventory' => $products,
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
                    'category' => $category,
                    'low_stock' => $lowStock,
                    'sort' => $sortBy,
                    'order' => $sortOrder
                ],
                'statistics' => $generalStats
            ], 'Estado del inventario obtenido exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo estado del inventario: ' . $e->getMessage());
            Response::error('Error al obtener estado del inventario', 500);
        }
    }
    
    /**
     * Obtiene movimientos de inventario
     */
    private function getMovements() {
        try {
            // Obtener parámetros de consulta
            $page = max(1, (int)getGet('page', 1));
            $limit = min(max(1, (int)getGet('limit', DEFAULT_PAGE_SIZE)), MAX_PAGE_SIZE);
            $productId = getGet('product_id', '');
            $type = getGet('type', '');
            $dateFrom = getGet('date_from', '');
            $dateTo = getGet('date_to', '');
            $sortBy = getGet('sort', 'fecha_movimiento');
            $sortOrder = strtoupper(getGet('order', 'DESC'));
            
            // Validar parámetros de ordenamiento
            $allowedSortFields = ['id', 'fecha_movimiento', 'tipo', 'cantidad'];
            $allowedSortOrders = ['ASC', 'DESC'];
            
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'fecha_movimiento';
            }
            
            if (!in_array($sortOrder, $allowedSortOrders)) {
                $sortOrder = 'DESC';
            }
            
            // Construir consulta base
            $sql = "SELECT im.*, p.nombre as producto_nombre, p.codigo as producto_codigo
                    FROM {$this->movementsTable} im 
                    INNER JOIN {$this->productsTable} p ON im.producto_id = p.id 
                    WHERE 1=1";
            
            $params = [];
            
            // Aplicar filtros
            if (!empty($productId)) {
                $sql .= " AND im.producto_id = :product_id";
                $params['product_id'] = $productId;
            }
            
            if (!empty($type)) {
                $sql .= " AND im.tipo = :type";
                $params['type'] = $type;
            }
            
            if (!empty($dateFrom)) {
                $sql .= " AND DATE(im.fecha_movimiento) >= :date_from";
                $params['date_from'] = $dateFrom;
            }
            
            if (!empty($dateTo)) {
                $sql .= " AND DATE(im.fecha_movimiento) <= :date_to";
                $params['date_to'] = $dateTo;
            }
            
            // Contar total de registros para paginación
            $countSql = str_replace('SELECT im.*, p.nombre as producto_nombre, p.codigo as producto_codigo', 'SELECT COUNT(*)', $sql);
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $totalRecords = $countStmt->fetchColumn();
            
            // Aplicar ordenamiento y paginación
            $sql .= " ORDER BY im.{$sortBy} {$sortOrder}";
            $offset = ($page - 1) * $limit;
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
            
            // Ejecutar consulta principal
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $movements = $stmt->fetchAll();
            
            // Procesar resultados
            $movements = array_map([$this, 'formatMovement'], $movements);
            
            // Calcular información de paginación
            $totalPages = ceil($totalRecords / $limit);
            
            // Respuesta con metadatos
            Response::success([
                'movements' => $movements,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $totalRecords,
                    'per_page' => $limit,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ],
                'filters' => [
                    'product_id' => $productId,
                    'type' => $type,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'sort' => $sortBy,
                    'order' => $sortOrder
                ]
            ], 'Movimientos de inventario obtenidos exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo movimientos: ' . $e->getMessage());
            Response::error('Error al obtener movimientos', 500);
        }
    }
    
    /**
     * Obtiene alertas de stock bajo
     */
    private function getStockAlerts() {
        try {
            $sql = "SELECT p.id, p.codigo, p.nombre, p.stock_actual, p.stock_minimo, 
                           p.unidad_medida, c.nombre as categoria_nombre,
                           CASE 
                               WHEN p.stock_actual <= 0 THEN 'critical'
                               WHEN p.stock_actual <= p.stock_minimo THEN 'warning'
                               ELSE 'normal'
                           END as alert_level
                    FROM {$this->productsTable} p 
                    LEFT JOIN categorias c ON p.categoria_id = c.id 
                    WHERE p.estado = 'activo' 
                    AND p.stock_actual <= p.stock_minimo
                    ORDER BY 
                        CASE 
                            WHEN p.stock_actual <= 0 THEN 1
                            WHEN p.stock_actual <= p.stock_minimo THEN 2
                            ELSE 3
                        END,
                        p.nombre";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $alerts = $stmt->fetchAll();
            
            // Procesar resultados
            $alerts = array_map(function($alert) {
                return [
                    'id' => (int)$alert['id'],
                    'codigo' => $alert['codigo'],
                    'nombre' => $alert['nombre'],
                    'stock_actual' => (int)$alert['stock_actual'],
                    'stock_minimo' => (int)$alert['stock_minimo'],
                    'unidad_medida' => $alert['unidad_medida'],
                    'categoria_nombre' => $alert['categoria_nombre'],
                    'alert_level' => $alert['alert_level'],
                    'deficit' => max(0, $alert['stock_minimo'] - $alert['stock_actual']),
                    'message' => $this->getAlertMessage($alert)
                ];
            }, $alerts);
            
            Response::success([
                'alerts' => $alerts,
                'total_alerts' => count($alerts),
                'critical_count' => count(array_filter($alerts, fn($a) => $a['alert_level'] === 'critical')),
                'warning_count' => count(array_filter($alerts, fn($a) => $a['alert_level'] === 'warning'))
            ], 'Alertas de stock obtenidas exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo alertas: ' . $e->getMessage());
            Response::error('Error al obtener alertas', 500);
        }
    }
    
    /**
     * Crea un nuevo movimiento de inventario
     */
    private function createMovement() {
        try {
            $data = getJsonInput();
            
            // Validar datos requeridos
            $this->validateMovementData($data);
            
            // Verificar que el producto existe
            if (!$this->productExists($data['producto_id'])) {
                Response::error('Producto no encontrado', 404);
            }
            
            // Iniciar transacción
            $this->db->beginTransaction();
            
            try {
                // Crear el movimiento
                $movementId = $this->createMovementRecord($data);
                
                // Actualizar stock del producto
                $this->updateProductStock($data['producto_id'], $data['tipo'], $data['cantidad']);
                
                $this->db->commit();
                
                // Obtener el movimiento creado
                $newMovement = $this->getMovementById($movementId);
                
                Logger::info('Movimiento de inventario creado', [
                    'movement_id' => $movementId,
                    'product_id' => $data['producto_id'],
                    'type' => $data['tipo'],
                    'quantity' => $data['cantidad']
                ]);
                
                Response::success($newMovement, 'Movimiento de inventario registrado exitosamente', 201);
                
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            Logger::error('Error creando movimiento: ' . $e->getMessage());
            Response::error('Error al crear movimiento: ' . $e->getMessage(), 400);
        }
    }
    
    /**
     * Ajusta el stock de un producto
     */
    private function adjustStock() {
        try {
            $data = getJsonInput();
            
            // Validar datos
            Validator::required($data['producto_id'] ?? '', 'ID del producto');
            Validator::required($data['nuevo_stock'] ?? '', 'Nuevo stock');
            Validator::positiveNumber($data['nuevo_stock'], 'Nuevo stock');
            
            if (!$this->productExists($data['producto_id'])) {
                Response::error('Producto no encontrado', 404);
            }
            
            // Obtener stock actual
            $currentStock = $this->getCurrentStock($data['producto_id']);
            $newStock = (int)$data['nuevo_stock'];
            $difference = $newStock - $currentStock;
            
            if ($difference == 0) {
                Response::error('El nuevo stock es igual al stock actual', 400);
            }
            
            // Iniciar transacción
            $this->db->beginTransaction();
            
            try {
                // Actualizar stock directamente
                $this->setProductStock($data['producto_id'], $newStock);
                
                // Registrar movimiento de ajuste
                $movementData = [
                    'producto_id' => $data['producto_id'],
                    'tipo' => 'ajuste',
                    'cantidad' => abs($difference),
                    'motivo' => $data['motivo'] ?? 'Ajuste de inventario',
                    'referencia_id' => null,
                    'usuario' => 'Sistema'
                ];
                
                $movementId = $this->createMovementRecord($movementData);
                
                $this->db->commit();
                
                Logger::info('Stock ajustado', [
                    'product_id' => $data['producto_id'],
                    'old_stock' => $currentStock,
                    'new_stock' => $newStock,
                    'difference' => $difference
                ]);
                
                Response::success([
                    'product_id' => $data['producto_id'],
                    'stock_anterior' => $currentStock,
                    'stock_nuevo' => $newStock,
                    'diferencia' => $difference,
                    'movement_id' => $movementId
                ], 'Stock ajustado exitosamente');
                
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            Logger::error('Error ajustando stock: ' . $e->getMessage());
            Response::error('Error al ajustar stock: ' . $e->getMessage(), 400);
        }
    }
    
    /**
     * Ajuste masivo de stock
     */
    private function bulkStockAdjustment() {
        try {
            $data = getJsonInput();
            
            if (empty($data['adjustments']) || !is_array($data['adjustments'])) {
                Response::error('Se requiere un array de ajustes', 400);
            }
            
            $results = [];
            $errors = [];
            
            // Iniciar transacción
            $this->db->beginTransaction();
            
            try {
                foreach ($data['adjustments'] as $index => $adjustment) {
                    try {
                        // Validar cada ajuste
                        if (empty($adjustment['producto_id']) || !isset($adjustment['nuevo_stock'])) {
                            throw new Exception("Ajuste #{$index}: producto_id y nuevo_stock son requeridos");
                        }
                        
                        $productId = $adjustment['producto_id'];
                        $newStock = (int)$adjustment['nuevo_stock'];
                        
                        if (!$this->productExists($productId)) {
                            throw new Exception("Ajuste #{$index}: Producto no encontrado");
                        }
                        
                        $currentStock = $this->getCurrentStock($productId);
                        $difference = $newStock - $currentStock;
                        
                        if ($difference != 0) {
                            // Actualizar stock
                            $this->setProductStock($productId, $newStock);
                            
                            // Registrar movimiento
                            $movementData = [
                                'producto_id' => $productId,
                                'tipo' => 'ajuste',
                                'cantidad' => abs($difference),
                                'motivo' => $adjustment['motivo'] ?? 'Ajuste masivo',
                                'referencia_id' => null,
                                'usuario' => 'Sistema'
                            ];
                            
                            $movementId = $this->createMovementRecord($movementData);
                            
                            $results[] = [
                                'producto_id' => $productId,
                                'stock_anterior' => $currentStock,
                                'stock_nuevo' => $newStock,
                                'diferencia' => $difference,
                                'movement_id' => $movementId
                            ];
                        }
                        
                    } catch (Exception $e) {
                        $errors[] = "Ajuste #{$index}: " . $e->getMessage();
                    }
                }
                
                if (!empty($errors) && empty($results)) {
                    // Si hay errores y no hay resultados exitosos, hacer rollback
                    $this->db->rollBack();
                    Response::error('Errores en ajuste masivo', 400, $errors);
                }
                
                $this->db->commit();
                
                Logger::info('Ajuste masivo de stock completado', [
                    'successful_adjustments' => count($results),
                    'errors' => count($errors)
                ]);
                
                Response::success([
                    'adjustments' => $results,
                    'errors' => $errors,
                    'summary' => [
                        'total_processed' => count($data['adjustments']),
                        'successful' => count($results),
                        'failed' => count($errors)
                    ]
                ], 'Ajuste masivo completado');
                
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            Logger::error('Error en ajuste masivo: ' . $e->getMessage());
            Response::error('Error en ajuste masivo: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Obtiene productos con stock bajo
     */
    private function getLowStockProducts() {
        try {
            $sql = "SELECT p.id, p.codigo, p.nombre, p.stock_actual, p.stock_minimo, 
                           p.unidad_medida, c.nombre as categoria_nombre, p.precio
                    FROM {$this->productsTable} p 
                    LEFT JOIN categorias c ON p.categoria_id = c.id 
                    WHERE p.estado = 'activo' 
                    AND p.stock_actual <= p.stock_minimo
                    ORDER BY 
                        (p.stock_actual / NULLIF(p.stock_minimo, 0)) ASC,
                        p.nombre";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $products = $stmt->fetchAll();
            
            // Procesar resultados
            $products = array_map(function($product) {
                $percentage = $product['stock_minimo'] > 0 ? 
                    round(($product['stock_actual'] / $product['stock_minimo']) * 100, 2) : 0;
                
                return [
                    'id' => (int)$product['id'],
                    'codigo' => $product['codigo'],
                    'nombre' => $product['nombre'],
                    'stock_actual' => (int)$product['stock_actual'],
                    'stock_minimo' => (int)$product['stock_minimo'],
                    'unidad_medida' => $product['unidad_medida'],
                    'categoria_nombre' => $product['categoria_nombre'],
                    'precio' => (float)$product['precio'],
                    'deficit' => max(0, $product['stock_minimo'] - $product['stock_actual']),
                    'percentage_of_minimum' => $percentage,
                    'estimated_cost_to_restock' => ($product['precio'] * max(0, $product['stock_minimo'] - $product['stock_actual'])),
                    'priority' => $this->getRestockPriority($product)
                ];
            }, $products);
            
            Response::success([
                'low_stock_products' => $products,
                'total_products' => count($products),
                'estimated_total_cost' => array_sum(array_column($products, 'estimated_cost_to_restock'))
            ], 'Productos con stock bajo obtenidos exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo productos con stock bajo: ' . $e->getMessage());
            Response::error('Error al obtener productos con stock bajo', 500);
        }
    }
    
    /**
     * Obtiene resumen del inventario
     */
    private function getInventorySummary() {
        try {
            // Estadísticas generales
            $generalStats = $this->getGeneralInventoryStats();
            
            // Movimientos recientes (últimos 7 días)
            $recentMovements = $this->getRecentMovements();
            
            // Top productos con mayor movimiento
            $topMovedProducts = $this->getTopMovedProducts();
            
            // Valor total del inventario
            $inventoryValue = $this->getInventoryValue();
            
            Response::success([
                'general_statistics' => $generalStats,
                'recent_movements' => $recentMovements,
                'top_moved_products' => $topMovedProducts,
                'inventory_value' => $inventoryValue,
                'generated_at' => date('Y-m-d H:i:s')
            ], 'Resumen de inventario obtenido exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo resumen: ' . $e->getMessage());
            Response::error('Error al obtener resumen de inventario', 500);
        }
    }
    
    /**
     * Valida los datos de un movimiento
     * 
     * @param array $data Datos del movimiento
     */
    private function validateMovementData($data) {
        Validator::required($data['producto_id'] ?? '', 'ID del producto');
        Validator::required($data['tipo'] ?? '', 'Tipo de movimiento');
        Validator::required($data['cantidad'] ?? '', 'Cantidad');
        Validator::positiveNumber($data['cantidad'], 'Cantidad');
        
        // Validar tipos permitidos
        $allowedTypes = array_keys(INVENTORY_MOVEMENT_TYPES);
        Validator::inArray($data['tipo'], $allowedTypes, 'Tipo de movimiento');
    }
    
    /**
     * Verifica si un producto existe
     * 
     * @param int $productId ID del producto
     * @return bool True si existe
     */
    private function productExists($productId) {
        $sql = "SELECT COUNT(*) FROM {$this->productsTable} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Obtiene el stock actual de un producto
     * 
     * @param int $productId ID del producto
     * @return int Stock actual
     */
    private function getCurrentStock($productId) {
        $sql = "SELECT stock_actual FROM {$this->productsTable} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Establece el stock de un producto
     * 
     * @param int $productId ID del producto
     * @param int $stock Nuevo stock
     */
    private function setProductStock($productId, $stock) {
        $sql = "UPDATE {$this->productsTable} SET stock_actual = :stock WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
        $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    /**
     * Actualiza el stock de un producto según el tipo de movimiento
     * 
     * @param int $productId ID del producto
     * @param string $type Tipo de movimiento
     * @param int $quantity Cantidad
     */
    private function updateProductStock($productId, $type, $quantity) {
        $sql = "UPDATE {$this->productsTable} SET stock_actual = stock_actual ";
        
        switch ($type) {
            case 'entrada':
            case 'production':
                $sql .= "+ :quantity";
                break;
            case 'salida':
            case 'waste':
                $sql .= "- :quantity";
                break;
            case 'ajuste':
                // Para ajustes, la cantidad puede ser positiva o negativa
                // Este caso se maneja diferente en adjustStock()
                return;
            default:
                throw new Exception('Tipo de movimiento no válido para actualizar stock');
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    /**
     * Crea un registro de movimiento
     * 
     * @param array $data Datos del movimiento
     * @return int ID del movimiento creado
     */
    private function createMovementRecord($data) {
        $sql = "INSERT INTO {$this->movementsTable} 
                (producto_id, tipo, cantidad, motivo, referencia_id, usuario, fecha_movimiento) 
                VALUES 
                (:producto_id, :tipo, :cantidad, :motivo, :referencia_id, :usuario, NOW())";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':producto_id', $data['producto_id'], PDO::PARAM_INT);
        $stmt->bindParam(':tipo', $data['tipo']);
        $stmt->bindParam(':cantidad', $data['cantidad']);
        $stmt->bindParam(':motivo', $data['motivo'] ?? '');
        $stmt->bindParam(':referencia_id', $data['referencia_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindParam(':usuario', $data['usuario'] ?? 'Sistema');
        
        $stmt->execute();
        return $this->db->lastInsertId();
    }
    
    /**
     * Obtiene un movimiento por ID
     * 
     * @param int $id ID del movimiento
     * @return array|null Datos del movimiento
     */
    private function getMovementById($id) {
        $sql = "SELECT im.*, p.nombre as producto_nombre, p.codigo as producto_codigo
                FROM {$this->movementsTable} im 
                INNER JOIN {$this->productsTable} p ON im.producto_id = p.id 
                WHERE im.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $movement = $stmt->fetch();
        return $movement ? $this->formatMovement($movement) : null;
    }
    
    /**
     * Obtiene estadísticas generales del inventario
     * 
     * @return array Estadísticas
     */
    private function getGeneralInventoryStats() {
        $sql = "SELECT 
                    COUNT(*) as total_productos,
                    SUM(CASE WHEN stock_actual <= 0 THEN 1 ELSE 0 END) as sin_stock,
                    SUM(CASE WHEN stock_actual <= stock_minimo AND stock_actual > 0 THEN 1 ELSE 0 END) as stock_bajo,
                    SUM(CASE WHEN stock_actual > stock_minimo THEN 1 ELSE 0 END) as stock_normal,
                    SUM(stock_actual * precio) as valor_total_inventario
                FROM {$this->productsTable} 
                WHERE estado = 'activo'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Obtiene movimientos recientes
     * 
     * @return array Movimientos recientes
     */
    private function getRecentMovements() {
        $sql = "SELECT im.*, p.nombre as producto_nombre
                FROM {$this->movementsTable} im
                INNER JOIN {$this->productsTable} p ON im.producto_id = p.id
                WHERE im.fecha_movimiento >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY im.fecha_movimiento DESC
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $movements = $stmt->fetchAll();
        
        return array_map([$this, 'formatMovement'], $movements);
    }
    
    /**
     * Obtiene productos con mayor movimiento
     * 
     * @return array Top productos
     */
    private function getTopMovedProducts() {
        $sql = "SELECT 
                    p.nombre,
                    p.codigo,
                    COUNT(im.id) as total_movimientos,
                    SUM(im.cantidad) as cantidad_total
                FROM {$this->movementsTable} im
                INNER JOIN {$this->productsTable} p ON im.producto_id = p.id
                WHERE im.fecha_movimiento >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY im.producto_id, p.nombre, p.codigo
                ORDER BY total_movimientos DESC
                LIMIT 5";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtiene valor total del inventario
     * 
     * @return array Valor del inventario
     */
    private function getInventoryValue() {
        $sql = "SELECT 
                    SUM(stock_actual * costo) as valor_costo,
                    SUM(stock_actual * precio) as valor_venta,
                    COUNT(*) as total_productos_con_stock
                FROM {$this->productsTable} 
                WHERE estado = 'activo' AND stock_actual > 0";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Genera mensaje de alerta para un producto
     * 
     * @param array $alert Datos de la alerta
     * @return string Mensaje
     */
    private function getAlertMessage($alert) {
        if ($alert['stock_actual'] <= 0) {
            return "¡SIN STOCK! El producto {$alert['nombre']} no tiene existencias.";
        } elseif ($alert['stock_actual'] <= $alert['stock_minimo']) {
            return "STOCK BAJO: El producto {$alert['nombre']} está por debajo del mínimo ({$alert['stock_actual']}/{$alert['stock_minimo']}).";
        }
        return '';
    }
    
    /**
     * Determina la prioridad de restock
     * 
     * @param array $product Datos del producto
     * @return string Prioridad
     */
    private function getRestockPriority($product) {
        if ($product['stock_actual'] <= 0) {
            return 'critical';
        } elseif ($product['stock_actual'] <= $product['stock_minimo'] * 0.5) {
            return 'high';
        } elseif ($product['stock_actual'] <= $product['stock_minimo']) {
            return 'medium';
        }
        return 'low';
    }
    
    /**
     * Formatea un item de inventario para la respuesta
     * 
     * @param array $item Datos del item
     * @return array Item formateado
     */
    private function formatInventoryItem($item) {
        $percentage = $item['stock_minimo'] > 0 ? 
            round(($item['stock_actual'] / $item['stock_minimo']) * 100, 2) : 0;
        
        return [
            'id' => (int)$item['id'],
            'codigo' => $item['codigo'],
            'nombre' => $item['nombre'],
            'stock_actual' => (int)$item['stock_actual'],
            'stock_minimo' => (int)$item['stock_minimo'],
            'unidad_medida' => $item['unidad_medida'],
            'categoria_nombre' => $item['categoria_nombre'],
            'precio' => (float)$item['precio'],
            'costo' => (float)$item['costo'],
            'status_stock' => $item['status_stock'],
            'percentage_of_minimum' => $percentage,
            'valor_inventario' => $item['stock_actual'] * $item['precio'],
            'is_low_stock' => $item['stock_actual'] <= $item['stock_minimo'],
            'is_out_of_stock' => $item['stock_actual'] <= 0
        ];
    }
    
    /**
     * Formatea un movimiento para la respuesta
     * 
     * @param array $movement Datos del movimiento
     * @return array Movimiento formateado
     */
    private function formatMovement($movement) {
        return [
            'id' => (int)$movement['id'],
            'producto_id' => (int)$movement['producto_id'],
            'producto_nombre' => $movement['producto_nombre'],
            'producto_codigo' => $movement['producto_codigo'] ?? '',
            'tipo' => $movement['tipo'],
            'tipo_nombre' => INVENTORY_MOVEMENT_TYPES[$movement['tipo']] ?? $movement['tipo'],
            'cantidad' => (int)$movement['cantidad'],
            'motivo' => $movement['motivo'],
            'referencia_id' => $movement['referencia_id'] ? (int)$movement['referencia_id'] : null,
            'usuario' => $movement['usuario'],
            'fecha_movimiento' => $movement['fecha_movimiento'],
            'fecha_movimiento_formateada' => formatDateTime($movement['fecha_movimiento'])
        ];
    }
    
    /**
     * Obtiene un movimiento específico por ID
     * 
     * @param int $id ID del movimiento
     */
    private function getMovement($id) {
        try {
            $movement = $this->getMovementById($id);
            
            if (!$movement) {
                Response::error('Movimiento no encontrado', 404);
            }
            
            Response::success($movement, 'Movimiento obtenido exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo movimiento: ' . $e->getMessage());
            Response::error('Error al obtener movimiento', 500);
        }
    }
    
    /**
     * Actualiza un movimiento (solo ajustes)
     * 
     * @param int $id ID del movimiento
     */
    private function updateMovement($id) {
        try {
            $movement = $this->getMovementById($id);
            if (!$movement) {
                Response::error('Movimiento no encontrado', 404);
            }
            
            // Solo permitir actualizar ajustes
            if ($movement['tipo'] !== 'ajuste') {
                Response::error('Solo se pueden actualizar movimientos de tipo ajuste', 400);
            }
            
            $data = getJsonInput();
            
            // Actualizar solo el motivo
            $sql = "UPDATE {$this->movementsTable} SET motivo = :motivo WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':motivo', $data['motivo'] ?? '');
            $stmt->execute();
            
            $updatedMovement = $this->getMovementById($id);
            
            Logger::info('Movimiento actualizado', ['movement_id' => $id]);
            Response::success($updatedMovement, 'Movimiento actualizado exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error actualizando movimiento: ' . $e->getMessage());
            Response::error('Error al actualizar movimiento', 500);
        }
    }
    
    /**
     * Elimina un movimiento (solo ajustes)
     * 
     * @param int $id ID del movimiento
     */
    private function deleteMovement($id) {
        try {
            $movement = $this->getMovementById($id);
            if (!$movement) {
                Response::error('Movimiento no encontrado', 404);
            }
            
            // Solo permitir eliminar ajustes
            if ($movement['tipo'] !== 'ajuste') {
                Response::error('Solo se pueden eliminar movimientos de tipo ajuste', 400);
            }
            
            // Iniciar transacción
            $this->db->beginTransaction();
            
            try {
                // Revertir el ajuste en el stock
                $currentStock = $this->getCurrentStock($movement['producto_id']);
                $revertedStock = $currentStock - $movement['cantidad']; // Asumiendo que fue suma
                
                $this->setProductStock($movement['producto_id'], $revertedStock);
                
                // Eliminar el movimiento
                $sql = "DELETE FROM {$this->movementsTable} WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
                
                $this->db->commit();
                
                Logger::info('Movimiento eliminado', ['movement_id' => $id]);
                Response::success(null, 'Movimiento eliminado exitosamente');
                
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            Logger::error('Error eliminando movimiento: ' . $e->getMessage());
            Response::error('Error al eliminar movimiento', 500);
        }
    }
    
    /**
     * Obtiene reportes de inventario
     */
    private function getInventoryReports() {
        try {
            $period = getGet('period', 'month');
            $dateFrom = getGet('date_from', '');
            $dateTo = getGet('date_to', '');
            
            // Determinar rango de fechas
            [$startDate, $endDate] = $this->getDateRange($period, $dateFrom, $dateTo);
            
            // Movimientos por tipo
            $movementsByType = $this->getMovementsByType($startDate, $endDate);
            
            // Productos más movidos
            $topMovedProducts = $this->getTopMovedProductsInPeriod($startDate, $endDate);
            
            // Valor de movimientos
            $movementValue = $this->getMovementValue($startDate, $endDate);
            
            Response::success([
                'period' => [
                    'type' => $period,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'movements_by_type' => $movementsByType,
                'top_moved_products' => $topMovedProducts,
                'movement_value' => $movementValue
            ], 'Reporte de inventario generado exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error generando reporte: ' . $e->getMessage());
            Response::error('Error al generar reporte', 500);
        }
    }
    
    /**
     * Obtiene rango de fechas según el período
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
                return [date('Y-m-01'), date('Y-m-t')];
        }
    }
    
    /**
     * Obtiene movimientos por tipo en un período
     */
    private function getMovementsByType($startDate, $endDate) {
        $sql = "SELECT tipo, COUNT(*) as total_movimientos, SUM(cantidad) as cantidad_total
                FROM {$this->movementsTable}
                WHERE DATE(fecha_movimiento) BETWEEN :start_date AND :end_date
                GROUP BY tipo
                ORDER BY cantidad_total DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtiene productos más movidos en un período
     */
    private function getTopMovedProductsInPeriod($startDate, $endDate) {
        $sql = "SELECT p.nombre, p.codigo, im.tipo,
                       COUNT(im.id) as total_movimientos,
                       SUM(im.cantidad) as cantidad_total
                FROM {$this->movementsTable} im
                INNER JOIN {$this->productsTable} p ON im.producto_id = p.id
                WHERE DATE(im.fecha_movimiento) BETWEEN :start_date AND :end_date
                GROUP BY im.producto_id, p.nombre, p.codigo, im.tipo
                ORDER BY cantidad_total DESC
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtiene valor de movimientos en un período
     */
    private function getMovementValue($startDate, $endDate) {
        $sql = "SELECT 
                    im.tipo,
                    SUM(im.cantidad * p.precio) as valor_total
                FROM {$this->movementsTable} im
                INNER JOIN {$this->productsTable} p ON im.producto_id = p.id
                WHERE DATE(im.fecha_movimiento) BETWEEN :start_date AND :end_date
                GROUP BY im.tipo";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}

// Inicializar API y manejar petición
try {
    $api = new InventoryAPI();
    $api->handleRequest();
} catch (Exception $e) {
    Logger::error('Error fatal en inventory.php: ' . $e->getMessage());
    Response::error('Error interno del servidor', 500);
}

?>
