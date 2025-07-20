<?php
/**
 * SISTEMA DE GESTIÓN DE PANADERÍA - CONFIGURACIÓN DE CONSTANTES
 * 
 * Este archivo define todas las constantes utilizadas en el sistema
 * para mantener la configuración centralizada y facilitar el mantenimiento.
 * 
 * @author Sistema de Panadería
 * @version 1.0
 * @package PanaderiaManager
 */

// Prevenir acceso directo
if (!defined('PANADERIA_ACCESS')) {
    die('Acceso directo no permitido');
}

/**
 * ==========================================
 * CONFIGURACIÓN GENERAL DEL SISTEMA
 * ==========================================
 */

// Versión del sistema
define('PANADERIA_VERSION', '1.0.0');

// Nombre del sistema
define('SYSTEM_NAME', 'Sistema de Gestión de Panadería');

// Modo de desarrollo (cambiar a false en producción)
define('DEBUG_MODE', true);

// Zona horaria
define('TIMEZONE', 'America/Mexico_City');

// Configuración de caracteres
define('CHARSET', 'UTF-8');

/**
 * ==========================================
 * CONFIGURACIÓN DE BASE DE DATOS
 * ==========================================
 */

// Host de la base de datos
define('DB_HOST', 'localhost');

// Puerto de la base de datos
define('DB_PORT', 3306);

// Nombre de la base de datos
define('DB_NAME', 'panaderia_db');

// Usuario de la base de datos
define('DB_USER', 'root');

// Contraseña de la base de datos
define('DB_PASS', '');

// Charset de la base de datos
define('DB_CHARSET', 'utf8mb4');

// Opciones de PDO
define('PDO_OPTIONS', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
]);

/**
 * ==========================================
 * CONFIGURACIÓN DE RUTAS Y DIRECTORIOS
 * ==========================================
 */

// Directorio raíz del proyecto
define('ROOT_PATH', dirname(dirname(__DIR__)));

// Directorio de la API
define('API_PATH', ROOT_PATH . '/api');

// Directorio de uploads
define('UPLOAD_PATH', ROOT_PATH . '/uploads');

// Directorio de logs
define('LOG_PATH', API_PATH . '/logs');

// Directorio de configuración
define('CONFIG_PATH', API_PATH . '/config');

// URL base del proyecto (ajustar según configuración)
define('BASE_URL', 'http://localhost/tarea%20web%20final');

// URL base de la API
define('API_URL', BASE_URL . '/api');

/**
 * ==========================================
 * CONFIGURACIÓN DE SESIONES
 * ==========================================
 */

// Nombre de la sesión
define('SESSION_NAME', 'panaderia_session');

// Tiempo de vida de la sesión (en segundos) - 2 horas
define('SESSION_LIFETIME', 7200);

// Regenerar ID de sesión cada X minutos
define('SESSION_REGENERATE_TIME', 1800); // 30 minutos

/**
 * ==========================================
 * CONFIGURACIÓN DE AUTENTICACIÓN
 * ==========================================
 */

// Tiempo de vida del token de autenticación (en segundos) - 8 horas
define('AUTH_TOKEN_LIFETIME', 28800);

// Intentos máximos de login antes de bloquear
define('MAX_LOGIN_ATTEMPTS', 5);

// Tiempo de bloqueo después de intentos fallidos (en segundos)
define('LOGIN_BLOCK_TIME', 900); // 15 minutos

// Tiempo de bloqueo en minutos (para compatibilidad)
define('LOCKOUT_TIME', 15);

// Longitud mínima de contraseña
define('MIN_PASSWORD_LENGTH', 6);

/**
 * ==========================================
 * CONFIGURACIÓN DE ARCHIVOS Y UPLOADS
 * ==========================================
 */

// Tamaño máximo de archivo (en bytes) - 5MB
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

// Tipos de archivo permitidos para imágenes
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Tipos de archivo permitidos para documentos
define('ALLOWED_DOC_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx']);

// Directorio de imágenes de productos
define('PRODUCT_IMAGES_PATH', UPLOAD_PATH . '/products');

// Directorio de imágenes de empleados
define('EMPLOYEE_IMAGES_PATH', UPLOAD_PATH . '/employees');

/**
 * ==========================================
 * CONFIGURACIÓN DE PAGINACIÓN
 * ==========================================
 */

// Elementos por página por defecto
define('DEFAULT_PAGE_SIZE', 10);

// Tamaño máximo de página
define('MAX_PAGE_SIZE', 100);

/**
 * ==========================================
 * CONFIGURACIÓN DE CACHE
 * ==========================================
 */

// Tiempo de vida del cache (en segundos) - 1 hora
define('CACHE_LIFETIME', 3600);

// Habilitar cache
define('CACHE_ENABLED', true);

/**
 * ==========================================
 * CONFIGURACIÓN DE EMAIL (OPCIONAL)
 * ==========================================
 */

// Servidor SMTP
define('SMTP_HOST', 'smtp.gmail.com');

// Puerto SMTP
define('SMTP_PORT', 587);

// Usuario SMTP
define('SMTP_USER', '');

// Contraseña SMTP
define('SMTP_PASS', '');

// Email de remitente
define('FROM_EMAIL', 'noreply@panaderia.com');

// Nombre del remitente
define('FROM_NAME', 'Sistema de Panadería');

/**
 * ==========================================
 * CONFIGURACIÓN DE MONEDA Y FORMATO
 * ==========================================
 */

// Moneda por defecto
define('DEFAULT_CURRENCY', 'MXN');

// Símbolo de moneda
define('CURRENCY_SYMBOL', '$');

// Decimales para precios
define('PRICE_DECIMALS', 2);

// Separador de decimales
define('DECIMAL_SEPARATOR', '.');

// Separador de miles
define('THOUSANDS_SEPARATOR', ',');

/**
 * ==========================================
 * CONFIGURACIÓN DE IMPUESTOS
 * ==========================================
 */

// IVA por defecto (16% en México)
define('DEFAULT_TAX_RATE', 0.16);

// Incluir impuestos en precios
define('PRICES_INCLUDE_TAX', false);

/**
 * ==========================================
 * CONFIGURACIÓN DE NOTIFICACIONES
 * ==========================================
 */

// Habilitar notificaciones por email
define('EMAIL_NOTIFICATIONS', false);

// Habilitar notificaciones push
define('PUSH_NOTIFICATIONS', false);

// Habilitar alertas de inventario bajo
define('LOW_STOCK_ALERTS', true);

// Nivel de stock bajo por defecto
define('DEFAULT_LOW_STOCK_LEVEL', 10);

/**
 * ==========================================
 * CONFIGURACIÓN DE REPORTES
 * ==========================================
 */

// Formato de fecha para reportes
define('REPORT_DATE_FORMAT', 'Y-m-d');

// Formato de fecha y hora para reportes
define('REPORT_DATETIME_FORMAT', 'Y-m-d H:i:s');

// Directorio de reportes generados
define('REPORTS_PATH', ROOT_PATH . '/reports');

/**
 * ==========================================
 * CONFIGURACIÓN DE BACKUP
 * ==========================================
 */

// Habilitar backup automático
define('AUTO_BACKUP', false);

// Frecuencia de backup (en horas)
define('BACKUP_FREQUENCY', 24);

// Directorio de backups
define('BACKUP_PATH', ROOT_PATH . '/backups');

// Retener backups por X días
define('BACKUP_RETENTION_DAYS', 30);

/**
 * ==========================================
 * CONFIGURACIÓN DE LOGS
 * ==========================================
 */

// Nivel de logging ('debug', 'info', 'warning', 'error')
define('LOG_LEVEL', DEBUG_MODE ? 'debug' : 'error');

// Rotar logs diariamente
define('LOG_ROTATION', true);

// Retener logs por X días
define('LOG_RETENTION_DAYS', 30);

/**
 * ==========================================
 * CONFIGURACIÓN DE ROLES Y PERMISOS
 * ==========================================
 */

// Roles del sistema
define('USER_ROLES', [
    'admin' => 'Administrador',
    'manager' => 'Gerente',
    'cashier' => 'Cajero',
    'baker' => 'Panadero',
    'sales' => 'Vendedor'
]);

// Permisos por módulo
define('MODULE_PERMISSIONS', [
    'dashboard' => ['admin', 'manager', 'cashier', 'baker', 'sales'],
    'products' => ['admin', 'manager', 'baker'],
    'sales' => ['admin', 'manager', 'cashier', 'sales'],
    'inventory' => ['admin', 'manager', 'baker'],
    'employees' => ['admin', 'manager'],
    'reports' => ['admin', 'manager'],
    'settings' => ['admin']
]);

/**
 * ==========================================
 * CONFIGURACIÓN DE API
 * ==========================================
 */

// Versión de la API
define('API_VERSION', 'v1');

// Rate limiting (peticiones por minuto)
define('API_RATE_LIMIT', 100);

// Habilitar CORS
define('ENABLE_CORS', true);

// Origenes permitidos para CORS
define('CORS_ORIGINS', ['*']);

// Métodos HTTP permitidos
define('ALLOWED_METHODS', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);

// Headers permitidos
define('ALLOWED_HEADERS', ['Content-Type', 'Authorization', 'X-Requested-With']);

/**
 * ==========================================
 * CONFIGURACIÓN DE PRODUCTOS
 * ==========================================
 */

// Categorías por defecto de productos
define('DEFAULT_CATEGORIES', [
    'pan_dulce' => 'Pan Dulce',
    'pan_salado' => 'Pan Salado',
    'pasteles' => 'Pasteles',
    'galletas' => 'Galletas',
    'bebidas' => 'Bebidas',
    'otros' => 'Otros'
]);

// Estados de productos
define('PRODUCT_STATUS', [
    'active' => 'Activo',
    'inactive' => 'Inactivo',
    'discontinued' => 'Descontinuado'
]);

/**
 * ==========================================
 * CONFIGURACIÓN DE VENTAS
 * ==========================================
 */

// Estados de ventas
define('SALE_STATUS', [
    'pending' => 'Pendiente',
    'completed' => 'Completada',
    'cancelled' => 'Cancelada',
    'refunded' => 'Reembolsada'
]);

// Métodos de pago
define('PAYMENT_METHODS', [
    'cash' => 'Efectivo',
    'card' => 'Tarjeta',
    'transfer' => 'Transferencia'
]);

/**
 * ==========================================
 * CONFIGURACIÓN DE INVENTARIO
 * ==========================================
 */

// Tipos de movimiento de inventario
define('INVENTORY_MOVEMENT_TYPES', [
    'entry' => 'Entrada',
    'exit' => 'Salida',
    'adjustment' => 'Ajuste',
    'production' => 'Producción',
    'waste' => 'Merma'
]);

/**
 * ==========================================
 * MENSAJES DEL SISTEMA
 * ==========================================
 */

// Mensajes de éxito
define('SUCCESS_MESSAGES', [
    'created' => 'Registro creado exitosamente',
    'updated' => 'Registro actualizado exitosamente',
    'deleted' => 'Registro eliminado exitosamente',
    'login' => 'Inicio de sesión exitoso',
    'logout' => 'Sesión cerrada exitosamente'
]);

// Mensajes de error
define('ERROR_MESSAGES', [
    'not_found' => 'Registro no encontrado',
    'unauthorized' => 'No autorizado',
    'forbidden' => 'Acceso denegado',
    'validation' => 'Error de validación',
    'database' => 'Error en la base de datos',
    'server' => 'Error interno del servidor'
]);

/**
 * ==========================================
 * CONFIGURACIÓN DE DESARROLLO
 * ==========================================
 */

if (DEBUG_MODE) {
    // Mostrar todos los errores en desarrollo
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    
    // Configuración de logs más detallada
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_PATH . '/php_errors.log');
} else {
    // Ocultar errores en producción
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// Configurar zona horaria
date_default_timezone_set(TIMEZONE);

// Configurar charset
mb_internal_encoding(CHARSET);

/**
 * ==========================================
 * FUNCIONES DE UTILIDAD PARA CONSTANTES
 * ==========================================
 */

/**
 * Obtiene el valor de una configuración
 * 
 * @param string $key Clave de la configuración
 * @param mixed $default Valor por defecto
 * @return mixed Valor de la configuración
 */
function config($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

/**
 * Verifica si una funcionalidad está habilitada
 * 
 * @param string $feature Nombre de la funcionalidad
 * @return bool True si está habilitada
 */
function isFeatureEnabled($feature) {
    $constant = strtoupper($feature) . '_ENABLED';
    return defined($constant) && constant($constant) === true;
}

/**
 * Obtiene la URL completa de un archivo
 * 
 * @param string $path Ruta relativa
 * @return string URL completa
 */
function getFullUrl($path) {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Obtiene la ruta completa de un archivo
 * 
 * @param string $path Ruta relativa
 * @return string Ruta completa
 */
function getFullPath($path) {
    return ROOT_PATH . '/' . ltrim($path, '/');
}

// Log de carga de configuración
if (function_exists('Logger::debug')) {
    Logger::debug('Archivo de configuración cargado correctamente');
}

?>
