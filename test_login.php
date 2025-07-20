<?php
/**
 * Archivo de prueba para autenticación y obtención de datos del dashboard
 */

// Configurar sesión
session_start();

echo "<h1>🔐 Prueba de Autenticación del Sistema</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

// Función para hacer peticiones POST
function makePostRequest($url, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen(json_encode($data))
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return array('data' => $result, 'code' => $httpCode);
}

// Función para hacer peticiones GET
function makeGetRequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return array('data' => $result, 'code' => $httpCode);
}

try {
    // 1. Intentar login
    echo "<h2>1. 🔑 Prueba de Login</h2>";
    $loginData = array(
        'action' => 'login',
        'email' => 'admin@panaderia.com',
        'password' => 'password'
    );
    
    $loginResult = makePostRequest('http://localhost/tarea%20web%20final/api/auth.php', $loginData);
    echo "<p><strong>Código HTTP:</strong> " . $loginResult['code'] . "</p>";
    echo "<p><strong>Respuesta:</strong></p>";
    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
    echo htmlspecialchars($loginResult['data']);
    echo "</pre>";
    
    $loginResponse = json_decode($loginResult['data'], true);
    
    if ($loginResponse && isset($loginResponse['success']) && $loginResponse['success']) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745; margin: 15px 0;'>";
        echo "<strong>✅ Login exitoso!</strong>";
        if (isset($loginResponse['data']['token'])) {
            echo "<br>Token: " . substr($loginResponse['data']['token'], 0, 20) . "...";
        }
        echo "</div>";
        
        // 2. Probar dashboard sin autenticación directa
        echo "<h2>2. 📊 Prueba de Dashboard</h2>";
        $dashboardResult = makeGetRequest('http://localhost/tarea%20web%20final/api/dashboard.php');
        echo "<p><strong>Código HTTP:</strong> " . $dashboardResult['code'] . "</p>";
        echo "<p><strong>Respuesta:</strong></p>";
        echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
        echo htmlspecialchars($dashboardResult['data']);
        echo "</pre>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545; margin: 15px 0;'>";
        echo "<strong>❌ Error en el login</strong>";
        echo "</div>";
    }
    
    // 3. Verificar datos directamente de la base de datos
    echo "<h2>3. 🗄️ Verificación Directa de Base de Datos</h2>";
    
    require_once 'api/config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    // Verificar productos
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM productos WHERE estado = 'disponible'");
    $stmt->execute();
    $productos = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar ventas del día
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) as total FROM ventas WHERE DATE(fecha) = CURDATE()");
    $stmt->execute();
    $ventasHoy = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar empleados
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM empleados WHERE estado = 'activo'");
    $stmt->execute();
    $empleados = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;'>";
    echo "<div style='background: #007bff; color: white; padding: 20px; border-radius: 8px; text-align: center;'>";
    echo "<h3>" . $productos['total'] . "</h3>";
    echo "<p>Productos Disponibles</p>";
    echo "</div>";
    echo "<div style='background: #28a745; color: white; padding: 20px; border-radius: 8px; text-align: center;'>";
    echo "<h3>$" . number_format($ventasHoy['total'], 2) . "</h3>";
    echo "<p>Ventas Hoy</p>";
    echo "</div>";
    echo "<div style='background: #ffc107; color: black; padding: 20px; border-radius: 8px; text-align: center;'>";
    echo "<h3>" . $empleados['total'] . "</h3>";
    echo "<p>Empleados Activos</p>";
    echo "</div>";
    echo "</div>";
    
    echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>💡 Diagnóstico:</strong><br>";
    echo "Los datos están en la base de datos. Si el dashboard no muestra información, ";
    echo "es probable que haya un problema con la autenticación o la comunicación entre frontend y backend.";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545; margin: 15px 0;'>";
    echo "<strong>❌ Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "</div>";
?>
