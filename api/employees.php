<?php
/**
 * SISTEMA DE GESTIÓN DE PANADERÍA - API ENDPOINT EMPLEADOS
 * 
 * Este endpoint maneja todas las operaciones CRUD para empleados
 * incluyendo gestión de roles, autenticación y datos personales.
 * 
 * Métodos soportados:
 * - GET: Listar empleados, obtener empleado por ID
 * - POST: Crear nuevo empleado
 * - PUT: Actualizar empleado existente
 * - DELETE: Eliminar empleado
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
 * Clase EmployeesAPI - Maneja operaciones de empleados
 */
class EmployeesAPI {
    
    private $db;
    private $table = 'empleados';
    
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
                        $this->getEmployee($id);
                    } else {
                        $this->getAllEmployees();
                    }
                    break;
                    
                case 'POST':
                    $this->createEmployee();
                    break;
                    
                case 'PUT':
                    if (!$id) {
                        Response::error('ID de empleado requerido para actualización', 400);
                    }
                    $this->updateEmployee($id);
                    break;
                    
                case 'DELETE':
                    if (!$id) {
                        Response::error('ID de empleado requerido para eliminación', 400);
                    }
                    $this->deleteEmployee($id);
                    break;
                    
                default:
                    Response::error('Método no permitido', 405);
            }
            
        } catch (Exception $e) {
            Logger::error('Error en EmployeesAPI: ' . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Extrae el ID del empleado de la ruta
     * 
     * @param string $path Ruta de la petición
     * @return int|null ID del empleado o null
     */
    private function extractIdFromPath($path) {
        if (empty($path)) return null;
        
        $segments = explode('/', trim($path, '/'));
        return is_numeric($segments[0]) ? (int)$segments[0] : null;
    }
    
    /**
     * Obtiene todos los empleados con filtros opcionales
     */
    private function getAllEmployees() {
        try {
            // Obtener parámetros de consulta
            $page = max(1, (int)getGet('page', 1));
            $limit = min(max(1, (int)getGet('limit', DEFAULT_PAGE_SIZE)), MAX_PAGE_SIZE);
            $search = getGet('search', '');
            $role = getGet('role', '');
            $status = getGet('status', '');
            $sortBy = getGet('sort', 'nombre');
            $sortOrder = strtoupper(getGet('order', 'ASC'));
            
            // Validar parámetros de ordenamiento
            $allowedSortFields = ['id', 'nombre', 'apellido', 'rol', 'salario', 'fecha_contratacion', 'estado'];
            $allowedSortOrders = ['ASC', 'DESC'];
            
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'nombre';
            }
            
            if (!in_array($sortOrder, $allowedSortOrders)) {
                $sortOrder = 'ASC';
            }
            
            // Construir consulta base
            $sql = "SELECT id, nombre, apellido, email, telefono, rol, salario, 
                           fecha_contratacion, estado, direccion, imagen_url,
                           created_at, updated_at
                    FROM {$this->table} 
                    WHERE 1=1";
            
            $params = [];
            
            // Aplicar filtros
            if (!empty($search)) {
                $sql .= " AND (nombre LIKE :search OR apellido LIKE :search OR email LIKE :search OR telefono LIKE :search)";
                $params['search'] = "%{$search}%";
            }
            
            if (!empty($role)) {
                $sql .= " AND rol = :role";
                $params['role'] = $role;
            }
            
            if (!empty($status)) {
                $sql .= " AND estado = :status";
                $params['status'] = $status;
            }
            
            // Contar total de registros para paginación
            $countSql = str_replace('SELECT id, nombre, apellido, email, telefono, rol, salario, fecha_contratacion, estado, direccion, imagen_url, created_at, updated_at', 'SELECT COUNT(*)', $sql);
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $totalRecords = $countStmt->fetchColumn();
            
            // Aplicar ordenamiento y paginación
            $sql .= " ORDER BY {$sortBy} {$sortOrder}";
            $offset = ($page - 1) * $limit;
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
            
            // Ejecutar consulta principal
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $employees = $stmt->fetchAll();
            
            // Procesar resultados
            $employees = array_map([$this, 'formatEmployee'], $employees);
            
            // Calcular información de paginación
            $totalPages = ceil($totalRecords / $limit);
            
            // Obtener estadísticas de roles
            $roleStats = $this->getRoleStatistics();
            
            // Respuesta con metadatos
            Response::success([
                'employees' => $employees,
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
                    'role' => $role,
                    'status' => $status,
                    'sort' => $sortBy,
                    'order' => $sortOrder
                ],
                'statistics' => $roleStats
            ], 'Empleados obtenidos exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo empleados: ' . $e->getMessage());
            Response::error('Error al obtener empleados', 500);
        }
    }
    
    /**
     * Obtiene un empleado específico por ID
     * 
     * @param int $id ID del empleado
     */
    private function getEmployee($id) {
        try {
            $sql = "SELECT id, nombre, apellido, email, telefono, rol, salario, 
                           fecha_contratacion, estado, direccion, imagen_url,
                           created_at, updated_at
                    FROM {$this->table} 
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $employee = $stmt->fetch();
            
            if (!$employee) {
                Response::error('Empleado no encontrado', 404);
            }
            
            // Formatear empleado
            $employee = $this->formatEmployee($employee);
            
            // Obtener estadísticas de ventas si es vendedor/cajero
            if (in_array($employee['rol'], ['cajero', 'vendedor'])) {
                $employee['sales_statistics'] = $this->getEmployeeSalesStats($id);
            }
            
            Response::success($employee, 'Empleado obtenido exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo empleado: ' . $e->getMessage());
            Response::error('Error al obtener empleado', 500);
        }
    }
    
    /**
     * Crea un nuevo empleado
     */
    private function createEmployee() {
        try {
            $data = getJsonInput();
            
            // Validar datos requeridos
            $this->validateEmployeeData($data);
            
            // Verificar que el email no exista
            if ($this->emailExists($data['email'])) {
                Response::error('El email ya está registrado', 400);
            }
            
            // Generar hash de contraseña si se proporciona
            $passwordHash = null;
            if (!empty($data['password'])) {
                $passwordHash = Security::hashPassword($data['password']);
            }
            
            // Preparar datos para inserción
            $sql = "INSERT INTO {$this->table} 
                    (nombre, apellido, email, telefono, password_hash, rol, salario, 
                     fecha_contratacion, estado, direccion, imagen_url) 
                    VALUES 
                    (:nombre, :apellido, :email, :telefono, :password_hash, :rol, :salario, 
                     :fecha_contratacion, :estado, :direccion, :imagen_url)";
            
            $stmt = $this->db->prepare($sql);
            
            // Bind de parámetros
            $stmt->bindParam(':nombre', $data['nombre']);
            $stmt->bindParam(':apellido', $data['apellido']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':telefono', $data['telefono']);
            $stmt->bindParam(':password_hash', $passwordHash);
            $stmt->bindParam(':rol', $data['rol']);
            $stmt->bindParam(':salario', $data['salario']);
            $stmt->bindParam(':fecha_contratacion', $data['fecha_contratacion']);
            $stmt->bindParam(':estado', $data['estado']);
            $stmt->bindParam(':direccion', $data['direccion']);
            $stmt->bindParam(':imagen_url', $data['imagen_url']);
            
            $stmt->execute();
            $employeeId = $this->db->lastInsertId();
            
            // Obtener el empleado creado
            $newEmployee = $this->getEmployeeById($employeeId);
            
            Logger::info('Empleado creado', ['employee_id' => $employeeId, 'email' => $data['email']]);
            Response::success($newEmployee, 'Empleado creado exitosamente', 201);
            
        } catch (Exception $e) {
            Logger::error('Error creando empleado: ' . $e->getMessage());
            Response::error('Error al crear empleado: ' . $e->getMessage(), 400);
        }
    }
    
    /**
     * Actualiza un empleado existente
     * 
     * @param int $id ID del empleado
     */
    private function updateEmployee($id) {
        try {
            // Verificar que el empleado existe
            if (!$this->employeeExists($id)) {
                Response::error('Empleado no encontrado', 404);
            }
            
            $data = getJsonInput();
            
            // Validar datos
            $this->validateEmployeeData($data, $id);
            
            // Verificar que el email no exista en otro empleado
            if ($this->emailExists($data['email'], $id)) {
                Response::error('El email ya está registrado en otro empleado', 400);
            }
            
            // Preparar SQL base
            $sql = "UPDATE {$this->table} SET 
                    nombre = :nombre, 
                    apellido = :apellido, 
                    email = :email, 
                    telefono = :telefono, 
                    rol = :rol, 
                    salario = :salario, 
                    fecha_contratacion = :fecha_contratacion, 
                    estado = :estado, 
                    direccion = :direccion, 
                    imagen_url = :imagen_url,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $data['nombre']);
            $stmt->bindParam(':apellido', $data['apellido']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':telefono', $data['telefono']);
            $stmt->bindParam(':rol', $data['rol']);
            $stmt->bindParam(':salario', $data['salario']);
            $stmt->bindParam(':fecha_contratacion', $data['fecha_contratacion']);
            $stmt->bindParam(':estado', $data['estado']);
            $stmt->bindParam(':direccion', $data['direccion']);
            $stmt->bindParam(':imagen_url', $data['imagen_url']);
            
            $stmt->execute();
            
            // Actualizar contraseña si se proporciona
            if (!empty($data['password'])) {
                $this->updatePassword($id, $data['password']);
            }
            
            // Obtener empleado actualizado
            $updatedEmployee = $this->getEmployeeById($id);
            
            Logger::info('Empleado actualizado', ['employee_id' => $id]);
            Response::success($updatedEmployee, 'Empleado actualizado exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error actualizando empleado: ' . $e->getMessage());
            Response::error('Error al actualizar empleado: ' . $e->getMessage(), 400);
        }
    }
    
    /**
     * Elimina un empleado
     * 
     * @param int $id ID del empleado
     */
    private function deleteEmployee($id) {
        try {
            // Verificar que el empleado existe
            if (!$this->employeeExists($id)) {
                Response::error('Empleado no encontrado', 404);
            }
            
            // Verificar que el empleado no tenga ventas asociadas recientes
            if ($this->hasRecentSales($id)) {
                Response::error('No se puede eliminar el empleado porque tiene ventas recientes', 400);
            }
            
            // Eliminar empleado (soft delete - cambiar estado)
            $sql = "UPDATE {$this->table} SET estado = 'inactivo', updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            Logger::info('Empleado eliminado (soft delete)', ['employee_id' => $id]);
            Response::success(null, 'Empleado eliminado exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error eliminando empleado: ' . $e->getMessage());
            Response::error('Error al eliminar empleado', 500);
        }
    }
    
    /**
     * Valida los datos de un empleado
     * 
     * @param array $data Datos del empleado
     * @param int|null $excludeId ID a excluir de validaciones (para updates)
     */
    private function validateEmployeeData($data, $excludeId = null) {
        // Campos requeridos
        Validator::required($data['nombre'] ?? '', 'Nombre');
        Validator::required($data['apellido'] ?? '', 'Apellido');
        Validator::required($data['email'] ?? '', 'Email');
        Validator::required($data['rol'] ?? '', 'Rol');
        
        // Validaciones de formato
        Validator::email($data['email']);
        Validator::minLength($data['nombre'], 2, 'Nombre');
        Validator::minLength($data['apellido'], 2, 'Apellido');
        
        // Validar salario si se proporciona
        if (isset($data['salario']) && !empty($data['salario'])) {
            Validator::positiveNumber($data['salario'], 'Salario');
        }
        
        // Validar roles permitidos
        $allowedRoles = array_keys(USER_ROLES);
        Validator::inArray($data['rol'], $allowedRoles, 'Rol');
        
        // Validar estados permitidos
        $allowedStatuses = ['activo', 'inactivo'];
        if (isset($data['estado'])) {
            Validator::inArray($data['estado'], $allowedStatuses, 'Estado');
        }
        
        // Validar fecha de contratación si se proporciona
        if (isset($data['fecha_contratacion']) && !empty($data['fecha_contratacion'])) {
            Validator::date($data['fecha_contratacion']);
        }
        
        // Validar contraseña si se proporciona
        if (!empty($data['password'])) {
            Validator::minLength($data['password'], MIN_PASSWORD_LENGTH, 'Contraseña');
        }
    }
    
    /**
     * Verifica si un email ya existe
     * 
     * @param string $email Email a verificar
     * @param int|null $excludeId ID a excluir de la verificación
     * @return bool True si existe
     */
    private function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = :email";
        $params = ['email' => $email];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Verifica si un empleado existe
     * 
     * @param int $id ID del empleado
     * @return bool True si existe
     */
    private function employeeExists($id) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Verifica si un empleado tiene ventas recientes
     * 
     * @param int $id ID del empleado
     * @return bool True si tiene ventas recientes
     */
    private function hasRecentSales($id) {
        $sql = "SELECT COUNT(*) FROM ventas 
                WHERE empleado_id = :id 
                AND fecha_venta >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Actualiza la contraseña de un empleado
     * 
     * @param int $id ID del empleado
     * @param string $password Nueva contraseña
     */
    private function updatePassword($id, $password) {
        $passwordHash = Security::hashPassword($password);
        
        $sql = "UPDATE {$this->table} SET password_hash = :password_hash WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':password_hash', $passwordHash);
        $stmt->execute();
        
        Logger::info('Contraseña actualizada', ['employee_id' => $id]);
    }
    
    /**
     * Obtiene un empleado por ID (interno)
     * 
     * @param int $id ID del empleado
     * @return array Datos del empleado
     */
    private function getEmployeeById($id) {
        $sql = "SELECT id, nombre, apellido, email, telefono, rol, salario, 
                       fecha_contratacion, estado, direccion, imagen_url,
                       created_at, updated_at
                FROM {$this->table} 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $employee = $stmt->fetch();
        return $employee ? $this->formatEmployee($employee) : null;
    }
    
    /**
     * Obtiene estadísticas de roles
     * 
     * @return array Estadísticas de roles
     */
    private function getRoleStatistics() {
        $sql = "SELECT rol, estado, COUNT(*) as total 
                FROM {$this->table} 
                GROUP BY rol, estado 
                ORDER BY rol, estado";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        $stats = [];
        foreach ($results as $row) {
            $role = $row['rol'];
            $status = $row['estado'];
            $total = (int)$row['total'];
            
            if (!isset($stats[$role])) {
                $stats[$role] = [
                    'total' => 0,
                    'activo' => 0,
                    'inactivo' => 0
                ];
            }
            
            $stats[$role]['total'] += $total;
            $stats[$role][$status] = $total;
        }
        
        return $stats;
    }
    
    /**
     * Obtiene estadísticas de ventas de un empleado
     * 
     * @param int $employeeId ID del empleado
     * @return array Estadísticas de ventas
     */
    private function getEmployeeSalesStats($employeeId) {
        $sql = "SELECT 
                    COUNT(*) as total_ventas,
                    SUM(total) as monto_total,
                    AVG(total) as promedio_venta,
                    DATE(MAX(fecha_venta)) as ultima_venta
                FROM ventas 
                WHERE empleado_id = :employee_id 
                AND estado = 'completada'
                AND fecha_venta >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();
        
        $stats = $stmt->fetch();
        
        return [
            'ventas_mes' => (int)$stats['total_ventas'],
            'monto_total_mes' => (float)$stats['monto_total'],
            'promedio_venta' => (float)$stats['promedio_venta'],
            'ultima_venta' => $stats['ultima_venta'] ? formatDate($stats['ultima_venta']) : null
        ];
    }
    
    /**
     * Formatea un empleado para la respuesta
     * 
     * @param array $employee Datos del empleado
     * @return array Empleado formateado
     */
    private function formatEmployee($employee) {
        return [
            'id' => (int)$employee['id'],
            'nombre' => $employee['nombre'],
            'apellido' => $employee['apellido'],
            'nombre_completo' => $employee['nombre'] . ' ' . $employee['apellido'],
            'email' => $employee['email'],
            'telefono' => $employee['telefono'],
            'rol' => $employee['rol'],
            'rol_nombre' => USER_ROLES[$employee['rol']] ?? $employee['rol'],
            'salario' => (float)$employee['salario'],
            'salario_formateado' => formatCurrency($employee['salario']),
            'fecha_contratacion' => $employee['fecha_contratacion'],
            'fecha_contratacion_formateada' => formatDate($employee['fecha_contratacion']),
            'antiguedad_dias' => $employee['fecha_contratacion'] ? dateDiffInDays($employee['fecha_contratacion'], date('Y-m-d')) : 0,
            'estado' => $employee['estado'],
            'direccion' => $employee['direccion'],
            'imagen_url' => $employee['imagen_url'],
            'created_at' => formatDateTime($employee['created_at']),
            'updated_at' => formatDateTime($employee['updated_at'])
        ];
    }
}

// Inicializar API y manejar petición
try {
    $api = new EmployeesAPI();
    $api->handleRequest();
} catch (Exception $e) {
    Logger::error('Error fatal en employees.php: ' . $e->getMessage());
    Response::error('Error interno del servidor', 500);
}

?>
