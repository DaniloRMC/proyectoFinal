<?php
/**
 * ARCHIVO DE PRUEBA DEL SISTEMA DE PANADERÍA
 * 
 * Este archivo verifica que todas las configuraciones y clases
 * estén funcionando correctamente.
 */

// Definir constante de acceso
define('PANADERIA_ACCESS', true);

// Incluir archivos de configuración en orden correcto
require_once 'api/config/config.php';
require_once 'api/config/functions.php';
require_once 'api/config/database.php';

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Prueba del Sistema de Panadería</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 40px; background-color: #f8f9fa; }";
echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".header { text-align: center; color: #ff8c00; margin-bottom: 30px; }";
echo ".test-section { margin: 20px 0; padding: 15px; border-left: 4px solid #ff8c00; background: #fff3e0; }";
echo ".success { color: #28a745; }";
echo ".error { color: #dc3545; }";
echo ".info { color: #17a2b8; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🥖 " . SYSTEM_NAME . "</h1>";
echo "<p>Versión: " . PANADERIA_VERSION . "</p>";
echo "</div>";

// Test 1: Configuraciones básicas
echo "<div class='test-section'>";
echo "<h3>✓ Test 1: Configuraciones Básicas</h3>";
echo "<p class='success'>✓ Configuraciones cargadas correctamente</p>";
echo "<p class='info'>• Modo Debug: " . (DEBUG_MODE ? 'Activado' : 'Desactivado') . "</p>";
echo "<p class='info'>• Base de datos: " . DB_HOST . ":" . DB_PORT . "</p>";
echo "<p class='info'>• Zona horaria: " . TIMEZONE . "</p>";
echo "</div>";

// Test 2: Clases utilitarias
echo "<div class='test-section'>";
echo "<h3>✓ Test 2: Clases Utilitarias</h3>";
try {
    // Test Validator
    $validator = new Validator(['test' => 'valor']);
    echo "<p class='success'>✓ Clase Validator: Funcionando</p>";
    
    // Test Security
    $hash = Security::hashPassword('test123');
    echo "<p class='success'>✓ Clase Security: Funcionando</p>";
    
    // Test Logger
    Logger::info('Test de sistema funcionando');
    echo "<p class='success'>✓ Clase Logger: Funcionando</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Error en clases utilitarias: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 3: Funciones auxiliares
echo "<div class='test-section'>";
echo "<h3>✓ Test 3: Funciones Auxiliares</h3>";
try {
    $fecha = formatDate(date('Y-m-d'));
    $moneda = formatCurrency(1234.56);
    $codigo = generateCode('PAN', 6);
    
    echo "<p class='success'>✓ Función formatDate: " . $fecha . "</p>";
    echo "<p class='success'>✓ Función formatCurrency: " . $moneda . "</p>";
    echo "<p class='success'>✓ Función generateCode: " . $codigo . "</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Error en funciones auxiliares: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 4: Conexión a base de datos
echo "<div class='test-section'>";
echo "<h3>✓ Test 4: Conexión a Base de Datos</h3>";
try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    if ($connection) {
        echo "<p class='success'>✓ Conexión a MySQL: Exitosa</p>";
        
        // Verificar que las tablas existen
        $stmt = $connection->prepare("SHOW TABLES LIKE 'productos'");
        $stmt->execute();
        $exists = $stmt->fetch();
        
        if ($exists) {
            echo "<p class='success'>✓ Tabla 'productos': Existe</p>";
            
            // Contar productos
            $stmt = $connection->prepare("SELECT COUNT(*) as total FROM productos");
            $stmt->execute();
            $count = $stmt->fetch();
            echo "<p class='info'>• Total de productos: " . $count['total'] . "</p>";
        } else {
            echo "<p class='error'>✗ Tabla 'productos': No existe (ejecutar panaderia_db.sql)</p>";
        }
        
    } else {
        echo "<p class='error'>✗ No se pudo conectar a la base de datos</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Error de conexión: " . $e->getMessage() . "</p>";
    echo "<p class='info'>Nota: Asegúrate de que MySQL esté corriendo y la base de datos 'panaderia_db' exista</p>";
}
echo "</div>";

// Test 5: APIs disponibles
echo "<div class='test-section'>";
echo "<h3>✓ Test 5: APIs Disponibles</h3>";
$apis = [
    'api/products.php' => 'Gestión de Productos',
    'api/employees.php' => 'Gestión de Empleados', 
    'api/sales.php' => 'Gestión de Ventas',
    'api/inventory.php' => 'Gestión de Inventario',
    'api/dashboard.php' => 'Dashboard',
    'api/auth.php' => 'Autenticación'
];

foreach ($apis as $file => $description) {
    if (file_exists($file)) {
        echo "<p class='success'>✓ $description: Disponible</p>";
    } else {
        echo "<p class='error'>✗ $description: No encontrado</p>";
    }
}
echo "</div>";

// Test 6: Información del sistema
echo "<div class='test-section'>";
echo "<h3>✓ Test 6: Información del Sistema</h3>";
$systemInfo = getSystemInfo();
echo "<p class='info'>• PHP Version: " . $systemInfo['php_version'] . "</p>";
echo "<p class='info'>• Servidor: " . $systemInfo['server_software'] . "</p>";
echo "<p class='info'>• Memoria usada: " . formatNumber($systemInfo['memory_usage'] / 1024 / 1024, 2) . " MB</p>";
echo "<p class='info'>• Zona horaria: " . $systemInfo['timezone'] . "</p>";
echo "</div>";

// Instrucciones finales
echo "<div class='test-section'>";
echo "<h3>📋 Instrucciones para completar la instalación:</h3>";
echo "<ol>";
echo "<li><strong>Base de Datos:</strong> Ejecuta el archivo <code>panaderia_db.sql</code> en phpMyAdmin</li>";
echo "<li><strong>Frontend:</strong> Abre <code>index.html</code> en tu navegador</li>";
echo "<li><strong>Login:</strong> Usuario: <code>admin</code>, Contraseña: <code>admin123</code></li>";
echo "<li><strong>APIs:</strong> Todas las APIs están listas para usar</li>";
echo "</ol>";
echo "</div>";

echo "<div style='text-align: center; margin-top: 30px; color: #6c757d;'>";
echo "<p><em>Sistema de Gestión de Panadería - Desarrollado con PHP, JavaScript y MySQL</em></p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";

?>
