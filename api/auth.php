<?php
/**
 * SISTEMA DE GESTIÓN DE PANADERÍA - API ENDPOINT AUTENTICACIÓN
 * 
 * Este endpoint maneja la autenticación de usuarios, sesiones y control de acceso
 * para el sistema de gestión de panadería.
 * 
 * Métodos soportados:
 * - POST: Login, logout, verificación de sesión, cambio de contraseña
 * - GET: Verificar estado de sesión, obtener perfil de usuario
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
 * Clase AuthAPI - Maneja la autenticación y autorización
 */
class AuthAPI {
    
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        try {
            // Iniciar sesión si no está iniciada
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
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
            
            switch ($method) {
                case 'POST':
                    $this->handlePostRequest($action);
                    break;
                case 'GET':
                    $this->handleGetRequest($action);
                    break;
                default:
                    Response::error('Método no permitido', 405);
            }
            
        } catch (Exception $e) {
            Logger::error('Error en AuthAPI: ' . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Maneja peticiones POST
     */
    private function handlePostRequest($action) {
        switch ($action) {
            case '':
            case 'login':
                $this->login();
                break;
            case 'logout':
                $this->logout();
                break;
            case 'change-password':
                $this->changePassword();
                break;
            case 'refresh-session':
                $this->refreshSession();
                break;
            case 'validate-token':
                $this->validateToken();
                break;
            default:
                Response::error('Endpoint no encontrado', 404);
        }
    }
    
    /**
     * Maneja peticiones GET
     */
    private function handleGetRequest($action) {
        switch ($action) {
            case '':
            case 'status':
                $this->getAuthStatus();
                break;
            case 'profile':
                $this->getUserProfile();
                break;
            case 'permissions':
                $this->getUserPermissions();
                break;
            default:
                Response::error('Endpoint no encontrado', 404);
        }
    }
    
    /**
     * Procesa login de usuario
     */
    private function login() {
        try {
            // Obtener datos del cuerpo de la petición
            $data = getRequestData();
            
            // Validar datos requeridos
            $validator = new Validator($data);
            $validator->required(['usuario', 'password'])
                     ->string('usuario', 3, 50)
                     ->string('password', 1, 255);
            
            if (!$validator->isValid()) {
                Response::error('Datos de login inválidos', 400, ['errors' => $validator->getErrors()]);
            }
            
            $usuario = trim($data['usuario']);
            $password = $data['password'];
            $recordarme = isset($data['recordarme']) && $data['recordarme'];
            
            // Buscar usuario en la base de datos
            $sql = "SELECT id, usuario, password, nombre, apellido, email, rol, estado, 
                           ultimo_acceso, intentos_login, bloqueado_hasta
                    FROM empleados 
                    WHERE (usuario = :usuario OR email = :usuario) 
                    AND estado = 'activo'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();
            
            $user = $stmt->fetch();
            
            if (!$user) {
                // Log intento de login fallido
                Logger::warning("Intento de login con usuario inexistente: {$usuario}");
                
                // Respuesta genérica para evitar enumeración de usuarios
                Response::error('Credenciales inválidas', 401);
            }
            
            // Verificar si la cuenta está bloqueada
            if ($user['bloqueado_hasta'] && strtotime($user['bloqueado_hasta']) > time()) {
                $tiempoRestante = strtotime($user['bloqueado_hasta']) - time();
                Logger::warning("Intento de login en cuenta bloqueada: {$usuario}");
                Response::error('Cuenta temporalmente bloqueada. Intente en ' . ceil($tiempoRestante / 60) . ' minutos.', 423);
            }
            
            // Verificar contraseña
            if (!Security::verifyPassword($password, $user['password'])) {
                // Incrementar intentos fallidos
                $this->incrementFailedAttempts($user['id']);
                
                Logger::warning("Login fallido para usuario: {$usuario}");
                Response::error('Credenciales inválidas', 401);
            }
            
            // Login exitoso - limpiar intentos fallidos
            $this->clearFailedAttempts($user['id']);
            
            // Generar token de sesión
            $sessionToken = $this->generateSessionToken();
            
            // Crear sesión
            $sessionData = [
                'user_id' => $user['id'],
                'usuario' => $user['usuario'],
                'nombre' => $user['nombre'],
                'apellido' => $user['apellido'],
                'email' => $user['email'],
                'rol' => $user['rol'],
                'login_time' => date('Y-m-d H:i:s'),
                'token' => $sessionToken,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+' . SESSION_LIFETIME . ' minutes')),
                'remember_me' => $recordarme
            ];
            
            // Guardar en sesión PHP
            $_SESSION['panaderia_user'] = $sessionData;
            $_SESSION['panaderia_token'] = $sessionToken;
            
            // Actualizar último acceso en la base de datos
            $this->updateLastAccess($user['id']);
            
            // Si "recordarme" está activado, crear cookie
            if ($recordarme) {
                $cookieToken = $this->createRememberMeToken($user['id']);
                setcookie('panaderia_remember', $cookieToken, time() + (30 * 24 * 3600), '/', '', false, true); // 30 días
            }
            
            // Log login exitoso
            Logger::info("Login exitoso para usuario: {$usuario} (ID: {$user['id']})");
            
            // Preparar respuesta
            $responseData = [
                'user' => [
                    'id' => (int)$user['id'],
                    'usuario' => $user['usuario'],
                    'nombre' => $user['nombre'],
                    'apellido' => $user['apellido'],
                    'nombre_completo' => $user['nombre'] . ' ' . $user['apellido'],
                    'email' => $user['email'],
                    'rol' => $user['rol'],
                    'rol_nombre' => USER_ROLES[$user['rol']] ?? $user['rol']
                ],
                'session' => [
                    'token' => $sessionToken,
                    'expires_at' => $sessionData['expires_at'],
                    'login_time' => $sessionData['login_time']
                ],
                'permissions' => $this->getUserPermissionsByRole($user['rol']),
                'remember_me' => $recordarme
            ];
            
            Response::success($responseData, 'Login exitoso');
            
        } catch (Exception $e) {
            Logger::error('Error en login: ' . $e->getMessage());
            Response::error('Error al procesar login', 500);
        }
    }
    
    /**
     * Procesa logout de usuario
     */
    private function logout() {
        try {
            $userId = $this->getCurrentUserId();
            
            if ($userId) {
                Logger::info("Logout para usuario ID: {$userId}");
            }
            
            // Limpiar sesión PHP
            if (isset($_SESSION['panaderia_user'])) {
                unset($_SESSION['panaderia_user']);
            }
            if (isset($_SESSION['panaderia_token'])) {
                unset($_SESSION['panaderia_token']);
            }
            
            // Limpiar cookie de "recordarme"
            if (isset($_COOKIE['panaderia_remember'])) {
                setcookie('panaderia_remember', '', time() - 3600, '/', '', false, true);
            }
            
            // Destruir sesión
            session_destroy();
            
            Response::success(['logged_out' => true], 'Logout exitoso');
            
        } catch (Exception $e) {
            Logger::error('Error en logout: ' . $e->getMessage());
            Response::error('Error al procesar logout', 500);
        }
    }
    
    /**
     * Cambia la contraseña del usuario
     */
    private function changePassword() {
        try {
            // Verificar autenticación
            $userId = $this->requireAuthentication();
            
            // Obtener datos del cuerpo de la petición
            $data = getRequestData();
            
            // Validar datos
            $validator = new Validator($data);
            $validator->required(['current_password', 'new_password', 'confirm_password'])
                     ->string('current_password', 1, 255)
                     ->string('new_password', MIN_PASSWORD_LENGTH, 255)
                     ->string('confirm_password', 1, 255);
            
            if (!$validator->isValid()) {
                Response::error('Datos inválidos', 400, ['errors' => $validator->getErrors()]);
            }
            
            $currentPassword = $data['current_password'];
            $newPassword = $data['new_password'];
            $confirmPassword = $data['confirm_password'];
            
            // Verificar que las nuevas contraseñas coincidan
            if ($newPassword !== $confirmPassword) {
                Response::error('Las contraseñas nuevas no coinciden', 400);
            }
            
            // Validar fortaleza de la nueva contraseña
            if (!Security::validatePasswordStrength($newPassword)) {
                Response::error('La nueva contraseña no cumple con los requisitos de seguridad', 400);
            }
            
            // Obtener contraseña actual del usuario
            $sql = "SELECT password FROM empleados WHERE id = :user_id AND estado = 'activo'";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $user = $stmt->fetch();
            if (!$user) {
                Response::error('Usuario no encontrado', 404);
            }
            
            // Verificar contraseña actual
            if (!Security::verifyPassword($currentPassword, $user['password'])) {
                Logger::warning("Intento de cambio de contraseña con contraseña actual incorrecta para usuario ID: {$userId}");
                Response::error('Contraseña actual incorrecta', 401);
            }
            
            // Hashear nueva contraseña
            $hashedPassword = Security::hashPassword($newPassword);
            
            // Actualizar contraseña en la base de datos
            $updateSql = "UPDATE empleados 
                          SET password = :password, 
                              fecha_modificacion = NOW() 
                          WHERE id = :user_id";
            
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->bindParam(':password', $hashedPassword);
            $updateStmt->bindParam(':user_id', $userId);
            
            if ($updateStmt->execute()) {
                Logger::info("Contraseña cambiada exitosamente para usuario ID: {$userId}");
                Response::success(['password_changed' => true], 'Contraseña actualizada exitosamente');
            } else {
                throw new Exception('Error al actualizar la contraseña en la base de datos');
            }
            
        } catch (Exception $e) {
            Logger::error('Error cambiando contraseña: ' . $e->getMessage());
            Response::error('Error al cambiar contraseña', 500);
        }
    }
    
    /**
     * Refresca la sesión del usuario
     */
    private function refreshSession() {
        try {
            $userId = $this->requireAuthentication();
            
            // Generar nuevo token
            $newToken = $this->generateSessionToken();
            
            // Actualizar datos de sesión
            $_SESSION['panaderia_user']['token'] = $newToken;
            $_SESSION['panaderia_user']['expires_at'] = date('Y-m-d H:i:s', strtotime('+' . SESSION_LIFETIME . ' minutes'));
            $_SESSION['panaderia_token'] = $newToken;
            
            Response::success([
                'token' => $newToken,
                'expires_at' => $_SESSION['panaderia_user']['expires_at']
            ], 'Sesión refrescada exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error refrescando sesión: ' . $e->getMessage());
            Response::error('Error al refrescar sesión', 500);
        }
    }
    
    /**
     * Valida un token de sesión
     */
    private function validateToken() {
        try {
            $data = getRequestData();
            
            if (!isset($data['token'])) {
                Response::error('Token requerido', 400);
            }
            
            $isValid = $this->isValidSession($data['token']);
            
            Response::success([
                'valid' => $isValid,
                'timestamp' => date('Y-m-d H:i:s')
            ], $isValid ? 'Token válido' : 'Token inválido');
            
        } catch (Exception $e) {
            Logger::error('Error validando token: ' . $e->getMessage());
            Response::error('Error al validar token', 500);
        }
    }
    
    /**
     * Obtiene el estado de autenticación actual
     */
    private function getAuthStatus() {
        try {
            $isAuthenticated = $this->isAuthenticated();
            
            if ($isAuthenticated) {
                $user = $_SESSION['panaderia_user'];
                
                Response::success([
                    'authenticated' => true,
                    'user' => [
                        'id' => (int)$user['user_id'],
                        'usuario' => $user['usuario'],
                        'nombre' => $user['nombre'],
                        'apellido' => $user['apellido'],
                        'nombre_completo' => $user['nombre'] . ' ' . $user['apellido'],
                        'email' => $user['email'],
                        'rol' => $user['rol'],
                        'rol_nombre' => USER_ROLES[$user['rol']] ?? $user['rol']
                    ],
                    'session' => [
                        'login_time' => $user['login_time'],
                        'expires_at' => $user['expires_at'],
                        'token' => $user['token']
                    ],
                    'permissions' => $this->getUserPermissionsByRole($user['rol'])
                ], 'Usuario autenticado');
            } else {
                Response::success([
                    'authenticated' => false,
                    'user' => null,
                    'session' => null,
                    'permissions' => []
                ], 'Usuario no autenticado');
            }
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo estado de autenticación: ' . $e->getMessage());
            Response::error('Error al obtener estado de autenticación', 500);
        }
    }
    
    /**
     * Obtiene el perfil del usuario actual
     */
    private function getUserProfile() {
        try {
            $userId = $this->requireAuthentication();
            
            $sql = "SELECT id, usuario, nombre, apellido, email, telefono, rol, estado,
                           fecha_creacion, ultimo_acceso
                    FROM empleados 
                    WHERE id = :user_id AND estado = 'activo'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $user = $stmt->fetch();
            
            if (!$user) {
                Response::error('Usuario no encontrado', 404);
            }
            
            // Formatear datos del usuario
            $profile = [
                'id' => (int)$user['id'],
                'usuario' => $user['usuario'],
                'nombre' => $user['nombre'],
                'apellido' => $user['apellido'],
                'nombre_completo' => $user['nombre'] . ' ' . $user['apellido'],
                'email' => $user['email'],
                'telefono' => $user['telefono'],
                'rol' => $user['rol'],
                'rol_nombre' => USER_ROLES[$user['rol']] ?? $user['rol'],
                'estado' => $user['estado'],
                'fecha_creacion' => $user['fecha_creacion'],
                'ultimo_acceso' => $user['ultimo_acceso'],
                'fecha_creacion_formateada' => formatDateTime($user['fecha_creacion']),
                'ultimo_acceso_formateado' => formatDateTime($user['ultimo_acceso'])
            ];
            
            Response::success(['profile' => $profile], 'Perfil obtenido exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo perfil: ' . $e->getMessage());
            Response::error('Error al obtener perfil del usuario', 500);
        }
    }
    
    /**
     * Obtiene los permisos del usuario actual
     */
    private function getUserPermissions() {
        try {
            $userId = $this->requireAuthentication();
            $user = $_SESSION['panaderia_user'];
            
            $permissions = $this->getUserPermissionsByRole($user['rol']);
            
            Response::success([
                'permissions' => $permissions,
                'role' => $user['rol']
            ], 'Permisos obtenidos exitosamente');
            
        } catch (Exception $e) {
            Logger::error('Error obteniendo permisos: ' . $e->getMessage());
            Response::error('Error al obtener permisos del usuario', 500);
        }
    }
    
    /**
     * Verifica si el usuario está autenticado
     */
    private function isAuthenticated() {
        if (!isset($_SESSION['panaderia_user']) || !isset($_SESSION['panaderia_token'])) {
            return false;
        }
        
        $user = $_SESSION['panaderia_user'];
        
        // Verificar si la sesión ha expirado
        if (strtotime($user['expires_at']) <= time()) {
            $this->clearSession();
            return false;
        }
        
        // Verificar token
        if (!$this->isValidSession($_SESSION['panaderia_token'])) {
            $this->clearSession();
            return false;
        }
        
        return true;
    }
    
    /**
     * Requiere autenticación y devuelve el ID del usuario
     */
    private function requireAuthentication() {
        if (!$this->isAuthenticated()) {
            Response::error('No autenticado', 401);
        }
        
        return $_SESSION['panaderia_user']['user_id'];
    }
    
    /**
     * Obtiene el ID del usuario actual (si está autenticado)
     */
    private function getCurrentUserId() {
        return $this->isAuthenticated() ? $_SESSION['panaderia_user']['user_id'] : null;
    }
    
    /**
     * Verifica si una sesión es válida
     */
    private function isValidSession($token) {
        return isset($_SESSION['panaderia_token']) && 
               $_SESSION['panaderia_token'] === $token &&
               isset($_SESSION['panaderia_user']) &&
               strtotime($_SESSION['panaderia_user']['expires_at']) > time();
    }
    
    /**
     * Genera un token de sesión único
     */
    private function generateSessionToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Incrementa los intentos fallidos de login
     */
    private function incrementFailedAttempts($userId) {
        $sql = "UPDATE empleados 
                SET intentos_login = COALESCE(intentos_login, 0) + 1,
                    bloqueado_hasta = CASE 
                        WHEN COALESCE(intentos_login, 0) + 1 >= :max_attempts 
                        THEN DATE_ADD(NOW(), INTERVAL :lockout_time MINUTE)
                        ELSE bloqueado_hasta 
                    END
                WHERE id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':max_attempts', MAX_LOGIN_ATTEMPTS);
        $stmt->bindValue(':lockout_time', LOCKOUT_TIME);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
    }
    
    /**
     * Limpia los intentos fallidos de login
     */
    private function clearFailedAttempts($userId) {
        $sql = "UPDATE empleados 
                SET intentos_login = 0, 
                    bloqueado_hasta = NULL 
                WHERE id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
    }
    
    /**
     * Actualiza el último acceso del usuario
     */
    private function updateLastAccess($userId) {
        $sql = "UPDATE empleados 
                SET ultimo_acceso = NOW() 
                WHERE id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
    }
    
    /**
     * Crea un token para "recordarme"
     */
    private function createRememberMeToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + (30 * 24 * 3600)); // 30 días
        
        // Aquí podrías guardar el token en la base de datos si lo deseas
        // Por simplicidad, usamos solo la cookie
        
        return base64_encode(json_encode(['user_id' => $userId, 'token' => $token, 'expires' => $expiry]));
    }
    
    /**
     * Limpia la sesión actual
     */
    private function clearSession() {
        if (isset($_SESSION['panaderia_user'])) {
            unset($_SESSION['panaderia_user']);
        }
        if (isset($_SESSION['panaderia_token'])) {
            unset($_SESSION['panaderia_token']);
        }
    }
    
    /**
     * Obtiene permisos según el rol del usuario
     */
    private function getUserPermissionsByRole($role) {
        $permissions = [];
        
        switch ($role) {
            case 'admin':
                $permissions = [
                    'dashboard' => ['read'],
                    'products' => ['create', 'read', 'update', 'delete'],
                    'categories' => ['create', 'read', 'update', 'delete'],
                    'inventory' => ['create', 'read', 'update', 'delete'],
                    'sales' => ['create', 'read', 'update', 'delete'],
                    'employees' => ['create', 'read', 'update', 'delete'],
                    'reports' => ['read', 'export'],
                    'settings' => ['read', 'update']
                ];
                break;
                
            case 'manager':
                $permissions = [
                    'dashboard' => ['read'],
                    'products' => ['create', 'read', 'update'],
                    'categories' => ['read'],
                    'inventory' => ['read', 'update'],
                    'sales' => ['create', 'read', 'update'],
                    'employees' => ['read'],
                    'reports' => ['read', 'export'],
                    'settings' => ['read']
                ];
                break;
                
            case 'cajero':
            case 'vendedor':
                $permissions = [
                    'dashboard' => ['read'],
                    'products' => ['read'],
                    'categories' => ['read'],
                    'inventory' => ['read'],
                    'sales' => ['create', 'read'],
                    'employees' => [],
                    'reports' => ['read'],
                    'settings' => []
                ];
                break;
                
            default:
                $permissions = [
                    'dashboard' => ['read'],
                    'products' => ['read'],
                    'sales' => ['read']
                ];
        }
        
        return $permissions;
    }
}

// Inicializar API y manejar petición
try {
    $api = new AuthAPI();
    $api->handleRequest();
} catch (Exception $e) {
    Logger::error('Error fatal en auth.php: ' . $e->getMessage());
    Response::error('Error interno del servidor', 500);
}

?>
