# =========================================================
# TEST RÁPIDO - Verificar que la API funciona
# =========================================================

<?php
echo "<h1>Test Backend API - SITRACABAÑA</h1>";
echo "<p><strong>Base URL:</strong> http://localhost/sitra_web/backend/public/api</p>";

$baseUrl = "http://localhost/sitra_web/backend/public/api";

// Test 1: Login
echo "<h2>1. Test Login</h2>";
$loginData = json_encode([
    'email' => 'admin@sitracabana.org',
    'password' => 'admin123'
]);

$ch = curl_init("{$baseUrl}/auth/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<pre>";
echo "HTTP Code: {$httpCode}\n";
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "</pre>";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    $token = $data['data']['token'] ?? null;
    
    if ($token) {
        echo "<p style='color:green;'>✅ Login exitoso. Token obtenido.</p>";
        
        // Test 2: Listar usuarios con el token
        echo "<h2>2. Test Listar Usuarios (con token)</h2>";
        
        $ch = curl_init("{$baseUrl}/users");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "Authorization: Bearer {$token}"
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<pre>";
        echo "HTTP Code: {$httpCode}\n";
        echo "Response:\n";
        echo json_encode(json_decode($response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "</pre>";
        
        if ($httpCode === 200) {
            echo "<p style='color:green;'>✅ Usuarios listados correctamente.</p>";
        } else {
            echo "<p style='color:red;'>❌ Error al listar usuarios.</p>";
        }
    }
} else {
    echo "<p style='color:red;'>❌ Login falló. Verifica la base de datos y credenciales.</p>";
}

echo "<hr>";
echo "<h3>Credenciales de prueba:</h3>";
echo "<ul>";
echo "<li><strong>Email:</strong> admin@sitracabana.org</li>";
echo "<li><strong>Password:</strong> admin123</li>";
echo "</ul>";

echo "<h3>Notas:</h3>";
echo "<ul>";
echo "<li>Si curl falla, prueba con Postman o herramientas similares.</li>";
echo "<li>Asegúrate de haber importado <code>backend/database_api.sql</code></li>";
echo "<li>Verifica <code>backend/.env</code> con credenciales de MySQL correctas.</li>";
echo "</ul>";
?>
