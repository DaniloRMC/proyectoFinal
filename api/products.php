<?php
/**
 * SISTEMA DE GESTIÓN DE PANADERÍA - API ENDPOINT PRODUCTOS
 * 
 * Este endpoint maneja todas las operaciones CRUD para productos
 * incluyendo gestión de categorías, precios, inventario y imágenes.
 * 
 * Métodos soportados:
 * - GET: Listar productos, obtener producto por ID
 * - POST: Crear nuevo producto
 * - PUT: Actualizar producto existente
 * - DELETE: Eliminar producto
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
 * Clase ProductsAPI - Maneja operaciones de productos
 */
class ProductsAPI {
    
    private $db;
    private $table = 'productos';
    
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
            $id = $this->extractIdFromPath($pathInfo);
            
            switch ($method) {
                case 'GET':
                    if ($id) {
                        $this->getProduct($id);
                    } else {
                        $this->getAllProducts();
                    }
                    break;
                    
                case 'POST':
                    $this->createProduct();
                    break;
                    
                case 'PUT':
                    if (!$id) {
                        Response::error('ID de producto requerido para actualización', 400);
                    }
                    $this->updateProduct($id);
                    break;
                    
                case 'DELETE':
                    if (!$id) {
                        Response::error('ID de producto requerido para eliminación', 400);
                    }
                    $this->deleteProduct($id);
                    break;
                    
                default:
                    Response::error('Método no permitido', 405);
            }
            
        } catch (Exception $e) {
            Logger::error('Error en ProductsAPI: ' . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Extrae el ID del producto de la ruta
     * 
     * @param string $path Ruta de la petición
     * @return int|null ID del producto o null
     */
    private function extractIdFromPath($path) {
        if (empty($path)) return null;
        
        $segments = explode('/', trim($path, '/'));
        return is_numeric($segments[0]) ? (int)$segments[0] : null;
    }
    
    /**
     * Obtiene todos los productos con filtros opcionales
     */
    private function getAllProducts() {
        try {
            // Obtener parámetros de consulta
            $page = max(1, (int)getGet('page', 1));
            $limit = min(max(1, (int)getGet('limit', DEFAULT_PAGE_SIZE)), MAX_PAGE_SIZE);
            $search = getGet('search', '');
            $category = getGet('category', '');
            $status = getGet('status', '');
            $sortBy = getGet('sort', 'nombre');
            $sortOrder = strtoupper(getGet('order', 'ASC'));
            
            // Validar parámetros de ordenamiento
            $allowedSortFields = ['id', 'nombre', 'precio', 'stock_actual', 'categoria', 'created_at'];
            $allowedSortOrders = ['ASC', 'DESC'];
            
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'nombre';
            }
            
            if (!in_array($sortOrder, $allowedSortOrders)) {
                $sortOrder = 'ASC';
            }
            
            // Construir consulta base
            $sql = "SELECT p.*, c.nombre as categoria_nombre 
                    FROM {$this->table} p 
                    LEFT JOIN categorias c ON p.categoria_id = c.id 
                    WHERE 1=1";
            
            $params = [];
            
            // Aplicar filtros
            if (!empty($search)) {
                $sql .= " AND (p.nombre LIKE :search OR p.descripcion LIKE :search OR p.codigo LIKE :search)";
                $params['search'] = "%{$search}%";
            }
            
            if (!empty($category)) {
                $sql .= " AND p.categoria_id = :category";
                $params['category'] = $category;
            }
            
            if (!empty($status)) {
                $sql .= " AND p.estado = :status";
                $params['status'] = $status;
            }
            
            // Contar total de registros para paginación
            $countSql = str_replace('SELECT p.*, c.nombre as categoria_nombre', 'SELECT COUNT(*)', $sql);
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
            $products = array_map([$this, 'formatProduct'], $products);
            
            // Calcular información de paginación
            $totalPages = ceil($totalRecords / $limit);
            
            // Respuesta con metadatos
            Response::success([
                'products' => $products,
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
                    'status' => $status,
                    'sort' => $sortBy,
                    'order' => $sortOrder
                ]
            ], 'Productos obtenidos exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo productos: ' . $e->getMessage());
            Response::error('Error al obtener productos', 500);
        }
    }
    
    /**
     * Obtiene un producto específico por ID
     * 
     * @param int $id ID del producto
     */
    private function getProduct($id) {
        try {
            $sql = "SELECT p.*, c.nombre as categoria_nombre,
                           (SELECT COUNT(*) FROM venta_detalles vd 
                            INNER JOIN ventas v ON vd.venta_id = v.id 
                            WHERE vd.producto_id = p.id AND v.estado = 'completada') as total_vendido
                    FROM {$this->table} p 
                    LEFT JOIN categorias c ON p.categoria_id = c.id 
                    WHERE p.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $product = $stmt->fetch();
            
            if (!$product) {
                Response::error('Producto no encontrado', 404);
            }
            
            // Formatear producto
            $product = $this->formatProduct($product);
            
            // Obtener historial de movimientos de inventario
            $movementsSql = "SELECT * FROM inventario_movimientos 
                           WHERE producto_id = :id 
                           ORDER BY fecha_movimiento DESC 
                           LIMIT 10";
            
            $movementsStmt = $this->db->prepare($movementsSql);
            $movementsStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $movementsStmt->execute();
            $movements = $movementsStmt->fetchAll();
            
            $product['recent_movements'] = array_map(function($movement) {
                return [
                    'id' => $movement['id'],
                    'tipo' => $movement['tipo'],
                    'cantidad' => (int)$movement['cantidad'],
                    'motivo' => $movement['motivo'],
                    'fecha' => formatDateTime($movement['fecha_movimiento']),
                    'usuario' => $movement['usuario'] ?? 'Sistema'
                ];
            }, $movements);
            
            Response::success($product, 'Producto obtenido exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo producto: ' . $e->getMessage());
            Response::error('Error al obtener producto', 500);
        }
    }
    
    /**
     * Crea un nuevo producto
     */
    private function createProduct() {
        try {
            $data = getJsonInput();
            
            // Validar datos requeridos
            $this->validateProductData($data);
            
            // Verificar que el código no exista
            if ($this->codeExists($data['codigo'])) {
                Response::error('El código de producto ya existe', 400);
            }
            
            // Preparar datos para inserción
            $sql = "INSERT INTO {$this->table} 
                    (codigo, nombre, descripcion, precio, costo, categoria_id, 
                     stock_actual, stock_minimo, unidad_medida, estado, imagen_url) 
                    VALUES 
                    (:codigo, :nombre, :descripcion, :precio, :costo, :categoria_id, 
                     :stock_actual, :stock_minimo, :unidad_medida, :estado, :imagen_url)";
            
            $stmt = $this->db->prepare($sql);
            
            // Bind de parámetros
            $stmt->bindParam(':codigo', $data['codigo']);
            $stmt->bindParam(':nombre', $data['nombre']);
            $stmt->bindParam(':descripcion', $data['descripcion']);
            $stmt->bindParam(':precio', $data['precio']);
            $stmt->bindParam(':costo', $data['costo']);
            $stmt->bindParam(':categoria_id', $data['categoria_id']);
            $stmt->bindParam(':stock_actual', $data['stock_actual']);
            $stmt->bindParam(':stock_minimo', $data['stock_minimo']);
            $stmt->bindParam(':unidad_medida', $data['unidad_medida']);
            $stmt->bindParam(':estado', $data['estado']);
            $stmt->bindParam(':imagen_url', $data['imagen_url']);
            
            $stmt->execute();
            $productId = $this->db->lastInsertId();
            
            // Registrar movimiento de inventario inicial si hay stock
            if ($data['stock_actual'] > 0) {
                $this->recordInventoryMovement($productId, 'entrada', $data['stock_actual'], 'Stock inicial');
            }
            
            // Obtener el producto creado
            $newProduct = $this->getProductById($productId);
            
            Logger::info('Producto creado', ['product_id' => $productId, 'code' => $data['codigo']]);
            Response::success($newProduct, 'Producto creado exitosamente', 201);
            
        } catch (Exception $e) {
            Logger::error('Error creando producto: ' . $e->getMessage());
            Response::error('Error al crear producto: ' . $e->getMessage(), 400);
        }
    }
    
    /**
     * Actualiza un producto existente
     * 
     * @param int $id ID del producto
     */
    private function updateProduct($id) {
        try {
            // Verificar que el producto existe
            if (!$this->productExists($id)) {
                Response::error('Producto no encontrado', 404);
            }
            
            $data = getJsonInput();
            
            // Validar datos
            $this->validateProductData($data, $id);
            
            // Verificar que el código no exista en otro producto
            if ($this->codeExists($data['codigo'], $id)) {
                Response::error('El código de producto ya existe en otro producto', 400);
            }
            
            // Obtener stock actual para comparar
            $currentProduct = $this->getProductById($id);
            $oldStock = $currentProduct['stock_actual'];
            $newStock = $data['stock_actual'];
            
            // Actualizar producto
            $sql = "UPDATE {$this->table} SET 
                    codigo = :codigo, 
                    nombre = :nombre, 
                    descripcion = :descripcion, 
                    precio = :precio, 
                    costo = :costo, 
                    categoria_id = :categoria_id, 
                    stock_actual = :stock_actual, 
                    stock_minimo = :stock_minimo, 
                    unidad_medida = :unidad_medida, 
                    estado = :estado, 
                    imagen_url = :imagen_url,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':codigo', $data['codigo']);
            $stmt->bindParam(':nombre', $data['nombre']);
            $stmt->bindParam(':descripcion', $data['descripcion']);
            $stmt->bindParam(':precio', $data['precio']);
            $stmt->bindParam(':costo', $data['costo']);
            $stmt->bindParam(':categoria_id', $data['categoria_id']);
            $stmt->bindParam(':stock_actual', $data['stock_actual']);
            $stmt->bindParam(':stock_minimo', $data['stock_minimo']);
            $stmt->bindParam(':unidad_medida', $data['unidad_medida']);
            $stmt->bindParam(':estado', $data['estado']);
            $stmt->bindParam(':imagen_url', $data['imagen_url']);
            
            $stmt->execute();
            
            // Registrar movimiento de inventario si cambió el stock
            if ($oldStock != $newStock) {
                $diff = $newStock - $oldStock;
                $type = $diff > 0 ? 'entrada' : 'salida';
                $this->recordInventoryMovement($id, $type, abs($diff), 'Ajuste manual');
            }
            
            // Obtener producto actualizado
            $updatedProduct = $this->getProductById($id);
            
            Logger::info('Producto actualizado', ['product_id' => $id]);
            Response::success($updatedProduct, 'Producto actualizado exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error actualizando producto: ' . $e->getMessage());
            Response::error('Error al actualizar producto: ' . $e->getMessage(), 400);
        }
    }
    
    /**
     * Elimina un producto
     * 
     * @param int $id ID del producto
     */
    private function deleteProduct($id) {
        try {
            // Verificar que el producto existe
            if (!$this->productExists($id)) {
                Response::error('Producto no encontrado', 404);
            }
            
            // Verificar que el producto no tenga ventas asociadas
            if ($this->hasAssociatedSales($id)) {
                Response::error('No se puede eliminar el producto porque tiene ventas asociadas', 400);
            }
            
            // Eliminar producto (soft delete - cambiar estado)
            $sql = "UPDATE {$this->table} SET estado = 'inactivo', updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            Logger::info('Producto eliminado (soft delete)', ['product_id' => $id]);
            Response::success(null, 'Producto eliminado exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error eliminando producto: ' . $e->getMessage());
            Response::error('Error al eliminar producto', 500);
        }
    }
    
    /**
     * Valida los datos de un producto
     * 
     * @param array $data Datos del producto
     * @param int|null $excludeId ID a excluir de validaciones (para updates)
     */
    private function validateProductData($data, $excludeId = null) {
        // Campos requeridos
        Validator::required($data['codigo'] ?? '', 'Código');
        Validator::required($data['nombre'] ?? '', 'Nombre');
        Validator::required($data['precio'] ?? '', 'Precio');
        Validator::required($data['categoria_id'] ?? '', 'Categoría');
        
        // Validaciones de formato
        Validator::positiveNumber($data['precio'], 'Precio');
        Validator::positiveNumber($data['costo'] ?? 0, 'Costo');
        Validator::positiveNumber($data['stock_actual'] ?? 0, 'Stock actual');
        Validator::positiveNumber($data['stock_minimo'] ?? 0, 'Stock mínimo');
        
        // Validar estados permitidos
        $allowedStatuses = array_keys(PRODUCT_STATUS);
        Validator::inArray($data['estado'] ?? 'activo', $allowedStatuses, 'Estado');
        
        // Validar que la categoría existe
        if (!$this->categoryExists($data['categoria_id'])) {
            throw new Exception('La categoría especificada no existe');
        }
    }
    
    /**
     * Verifica si un código de producto ya existe
     * 
     * @param string $code Código a verificar
     * @param int|null $excludeId ID a excluir de la verificación
     * @return bool True si existe
     */
    private function codeExists($code, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE codigo = :code";
        $params = ['code' => $code];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Verifica si un producto existe
     * 
     * @param int $id ID del producto
     * @return bool True si existe
     */
    private function productExists($id) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Verifica si una categoría existe
     * 
     * @param int $categoryId ID de la categoría
     * @return bool True si existe
     */
    private function categoryExists($categoryId) {
        $sql = "SELECT COUNT(*) FROM categorias WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Verifica si un producto tiene ventas asociadas
     * 
     * @param int $id ID del producto
     * @return bool True si tiene ventas
     */
    private function hasAssociatedSales($id) {
        $sql = "SELECT COUNT(*) FROM venta_detalles WHERE producto_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Obtiene un producto por ID (interno)
     * 
     * @param int $id ID del producto
     * @return array Datos del producto
     */
    private function getProductById($id) {
        $sql = "SELECT p.*, c.nombre as categoria_nombre 
                FROM {$this->table} p 
                LEFT JOIN categorias c ON p.categoria_id = c.id 
                WHERE p.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $product = $stmt->fetch();
        return $product ? $this->formatProduct($product) : null;
    }
    
    /**
     * Registra un movimiento de inventario
     * 
     * @param int $productId ID del producto
     * @param string $type Tipo de movimiento
     * @param int $quantity Cantidad
     * @param string $reason Motivo
     */
    private function recordInventoryMovement($productId, $type, $quantity, $reason) {
        $sql = "INSERT INTO inventario_movimientos 
                (producto_id, tipo, cantidad, motivo, usuario, fecha_movimiento) 
                VALUES (:producto_id, :tipo, :cantidad, :motivo, :usuario, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':producto_id', $productId, PDO::PARAM_INT);
        $stmt->bindParam(':tipo', $type);
        $stmt->bindParam(':cantidad', $quantity);
        $stmt->bindParam(':motivo', $reason);
        $stmt->bindParam(':usuario', 'Sistema'); // En futuro usar usuario actual
        $stmt->execute();
    }
    
    /**
     * Formatea un producto para la respuesta
     * 
     * @param array $product Datos del producto
     * @return array Producto formateado
     */
    private function formatProduct($product) {
        return [
            'id' => (int)$product['id'],
            'codigo' => $product['codigo'],
            'nombre' => $product['nombre'],
            'descripcion' => $product['descripcion'],
            'precio' => (float)$product['precio'],
            'costo' => (float)$product['costo'],
            'categoria_id' => (int)$product['categoria_id'],
            'categoria_nombre' => $product['categoria_nombre'],
            'stock_actual' => (int)$product['stock_actual'],
            'stock_minimo' => (int)$product['stock_minimo'],
            'unidad_medida' => $product['unidad_medida'],
            'estado' => $product['estado'],
            'imagen_url' => $product['imagen_url'],
            'precio_formateado' => formatCurrency($product['precio']),
            'costo_formateado' => formatCurrency($product['costo']),
            'margen_ganancia' => $product['precio'] > 0 ? round((($product['precio'] - $product['costo']) / $product['precio']) * 100, 2) : 0,
            'stock_bajo' => $product['stock_actual'] <= $product['stock_minimo'],
            'total_vendido' => isset($product['total_vendido']) ? (int)$product['total_vendido'] : 0,
            'created_at' => formatDateTime($product['created_at']),
            'updated_at' => formatDateTime($product['updated_at'])
        ];
    }
}

// Inicializar API y manejar petición
try {
    $api = new ProductsAPI();
    $api->handleRequest();
} catch (Exception $e) {
    Logger::error('Error fatal en products.php: ' . $e->getMessage());
    Response::error('Error interno del servidor', 500);
}

?>
