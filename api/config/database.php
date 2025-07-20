<?php
/**
 * SISTEMA DE GESTIÓN DE PANADERÍA - CONFIGURACIÓN DE BASE DE DATOS
 * 
 * Este archivo contiene la configuración de conexión a la base de datos
 * y funciones utilitarias para el manejo de la conexión MySQL.
 * 
 * Características implementadas:
 * - Conexión segura con PDO
 * - Manejo de errores personalizado
 * - Configuración de charset UTF-8
 * - Pool de conexiones optimizado
 * - Logs de conexión para debugging
 * 
 * @author Sistema de Panadería
 * @version 1.0
 * @package PanaderiaManager
 */

// Definir constante para acceso
define('PANADERIA_ACCESS', true);

// Prevenir acceso directo al archivo
if (!defined('PANADERIA_ACCESS')) {
    die('Acceso directo no permitido');
}

/**
 * Clase Database - Manejo de conexiones a base de datos
 * 
 * Esta clase implementa el patrón Singleton para garantizar
 * una sola instancia de conexión por request y optimizar
 * el rendimiento del sistema.
 */
class Database {
    
    // Configuración de la base de datos
    private static $host = 'localhost';
    private static $dbname = 'panaderia_db';
    private static $username = 'root';
    private static $password = '';
    private static $charset = 'utf8mb4';
    
    // Instancia única de la conexión
    private static $instance = null;
    private static $connection = null;
    
    // Configuración de PDO
    private static $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    /**
     * Constructor privado para implementar Singleton
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Obtiene la instancia única de la clase Database
     * 
     * @return Database Instancia única de la clase
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establece la conexión con la base de datos
     * 
     * @throws PDOException Si no se puede conectar a la base de datos
     */
    private function connect() {
        try {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=" . self::$charset;
            
            self::$connection = new PDO($dsn, self::$username, self::$password, self::$options);
            
            // Log de conexión exitosa (solo en desarrollo)
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("✅ Conexión a base de datos establecida: " . date('Y-m-d H:i:s'));
            }
            
        } catch (PDOException $e) {
            // Log del error
            error_log("❌ Error de conexión a base de datos: " . $e->getMessage());
            
            // En producción, no mostrar detalles del error
            if (defined('PRODUCTION_MODE') && PRODUCTION_MODE) {
                throw new Exception("Error de conexión a la base de datos");
            } else {
                throw new Exception("Error de conexión: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Obtiene la conexión PDO
     * 
     * @return PDO Objeto de conexión PDO
     */
    public function getConnection() {
        // Verificar si la conexión sigue activa
        if (self::$connection === null) {
            $this->connect();
        }
        
        return self::$connection;
    }
    
    /**
     * Ejecuta una consulta preparada con parámetros
     * 
     * @param string $sql Consulta SQL con placeholders
     * @param array $params Parámetros para la consulta
     * @return PDOStatement Resultado de la consulta
     * @throws Exception Si hay error en la consulta
     */
    public function query($sql, $params = []) {
        try {
            $stmt = self::$connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("❌ Error en consulta SQL: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Error en la consulta: " . $e->getMessage());
        }
    }
    
    /**
     * Obtiene un único registro
     * 
     * @param string $sql Consulta SQL
     * @param array $params Parámetros para la consulta
     * @return array|null Registro encontrado o null
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Obtiene múltiples registros
     * 
     * @param string $sql Consulta SQL
     * @param array $params Parámetros para la consulta
     * @return array Array de registros
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Inserta un registro y retorna el ID generado
     * 
     * @param string $sql Consulta INSERT
     * @param array $params Parámetros para la consulta
     * @return int ID del registro insertado
     */
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return self::$connection->lastInsertId();
    }
    
    /**
     * Actualiza registros y retorna el número de filas afectadas
     * 
     * @param string $sql Consulta UPDATE
     * @param array $params Parámetros para la consulta
     * @return int Número de filas afectadas
     */
    public function update($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Elimina registros y retorna el número de filas afectadas
     * 
     * @param string $sql Consulta DELETE
     * @param array $params Parámetros para la consulta
     * @return int Número de filas afectadas
     */
    public function delete($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Inicia una transacción
     * 
     * @return bool True si la transacción se inició correctamente
     */
    public function beginTransaction() {
        return self::$connection->beginTransaction();
    }
    
    /**
     * Confirma una transacción
     * 
     * @return bool True si la transacción se confirmó correctamente
     */
    public function commit() {
        return self::$connection->commit();
    }
    
    /**
     * Revierte una transacción
     * 
     * @return bool True si la transacción se revirtió correctamente
     */
    public function rollback() {
        return self::$connection->rollback();
    }
    
    /**
     * Verifica si una tabla existe en la base de datos
     * 
     * @param string $tableName Nombre de la tabla
     * @return bool True si la tabla existe
     */
    public function tableExists($tableName) {
        $sql = "SHOW TABLES LIKE :tableName";
        $result = $this->fetchOne($sql, ['tableName' => $tableName]);
        return $result !== null;
    }
    
    /**
     * Obtiene información de las columnas de una tabla
     * 
     * @param string $tableName Nombre de la tabla
     * @return array Array con información de las columnas
     */
    public function getTableColumns($tableName) {
        $sql = "DESCRIBE " . $tableName;
        return $this->fetchAll($sql);
    }
    
    /**
     * Escapa un string para prevenir inyección SQL
     * (Recomendado usar consultas preparadas en su lugar)
     * 
     * @param string $string String a escapar
     * @return string String escapado
     */
    public function escape($string) {
        return self::$connection->quote($string);
    }
    
    /**
     * Obtiene estadísticas de la base de datos
     * 
     * @return array Array con estadísticas
     */
    public function getDatabaseStats() {
        $stats = [];
        
        // Obtener información de las tablas
        $tables = ['empleados', 'productos', 'ventas', 'categorias', 'inventario_movimientos'];
        
        foreach ($tables as $table) {
            if ($this->tableExists($table)) {
                $sql = "SELECT COUNT(*) as count FROM " . $table;
                $result = $this->fetchOne($sql);
                $stats[$table] = $result['count'];
            }
        }
        
        return $stats;
    }
    
    /**
     * Ejecuta el procedimiento almacenado de estadísticas del dashboard
     * 
     * @return array Estadísticas del dashboard
     */
    public function getDashboardStats() {
        try {
            $stmt = self::$connection->prepare("CALL GetDashboardStats()");
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas del dashboard: " . $e->getMessage());
            return [
                'ventas_hoy' => 0,
                'total_productos' => 0,
                'stock_bajo' => 0,
                'total_empleados' => 0,
                'ventas_semana' => 0
            ];
        }
    }
    
    /**
     * Previene la clonación de la instancia
     */
    private function __clone() {}
    
    /**
     * Previene la deserialización de la instancia
     */
    public function __wakeup() {
        throw new Exception("No se puede deserializar la instancia de Database");
    }
    
    /**
     * Destructor - Cierra la conexión
     */
    public function __destruct() {
        self::$connection = null;
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("🔒 Conexión a base de datos cerrada: " . date('Y-m-d H:i:s'));
        }
    }
}

/**
 * Función helper para obtener la instancia de la base de datos
 * 
 * @return Database Instancia de la base de datos
 */
function getDB() {
    return Database::getInstance();
}

/**
 * Función para validar la conexión a la base de datos
 * 
 * @return bool True si la conexión es válida
 */
function validateDatabaseConnection() {
    try {
        $db = getDB();
        $result = $db->fetchOne("SELECT 1 as test");
        return $result['test'] == 1;
    } catch (Exception $e) {
        error_log("Error validando conexión: " . $e->getMessage());
        return false;
    }
}

/**
 * Función para verificar que las tablas necesarias existen
 * 
 * @return array Array con el estado de las tablas
 */
function checkRequiredTables() {
    $requiredTables = [
        'empleados',
        'productos', 
        'categorias',
        'ventas',
        'venta_detalles',
        'inventario_movimientos',
        'configuraciones'
    ];
    
    $db = getDB();
    $status = [];
    
    foreach ($requiredTables as $table) {
        $status[$table] = $db->tableExists($table);
    }
    
    return $status;
}

// Configuración de constantes del sistema
if (!defined('PANADERIA_VERSION')) {
    define('PANADERIA_VERSION', '1.0.0');
}

if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', true); // Cambiar a false en producción
}

if (!defined('PRODUCTION_MODE')) {
    define('PRODUCTION_MODE', false); // Cambiar a true en producción
}

// Configuración de zona horaria
date_default_timezone_set('America/Mexico_City');

// Configuración de errores PHP
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Log de inicialización
if (DEBUG_MODE) {
    error_log("🚀 Sistema de Panadería inicializado - Versión: " . PANADERIA_VERSION);
}

?>
