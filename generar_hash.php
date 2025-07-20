<?php
// Generar hash de contraseña para 'password'
$password = 'password';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Hash generado para 'password': " . $hash . "\n\n";

// Verificar que funciona
if (password_verify('password', $hash)) {
    echo "✅ Hash verificado correctamente\n\n";
} else {
    echo "❌ Error en la verificación del hash\n\n";
}

// Actualizar base de datos
try {
    require_once 'api/config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("UPDATE empleados SET password_hash = ? WHERE id <= 5");
    $stmt->execute([$hash]);
    
    echo "✅ Hash actualizado en la base de datos para los primeros 5 empleados\n";
    echo "Ahora puedes usar:\n";
    echo "- Email: admin@panaderia.com\n";
    echo "- Contraseña: password\n";
    
} catch (Exception $e) {
    echo "❌ Error actualizando base de datos: " . $e->getMessage() . "\n";
}
?>
