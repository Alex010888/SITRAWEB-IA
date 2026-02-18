<?php
/**
 * DIAGNÓSTICO COMPLETO - Backend API
 */

echo "<h1>🔍 Diagnóstico Backend API</h1>";
echo "<style>
    .ok { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>";

// ===================================
// 1. VERIFICAR .ENV
// ===================================
echo "<div class='section'>";
echo "<h2>1. Archivo .env</h2>";

$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    echo "<p class='ok'>✅ Archivo .env existe</p>";
    
    require __DIR__ . '/app/config/env.php';
    loadEnv($envPath);
    
    echo "<pre>";
    echo "DB_HOST: " . env('DB_HOST', 'NO DEFINIDO') . "\n";
    echo "DB_NAME: " . env('DB_NAME', 'NO DEFINIDO') . "\n";
    echo "DB_USER: " . env('DB_USER', 'NO DEFINIDO') . "\n";
    echo "DB_PASS: " . (env('DB_PASS') === '' ? '(vacío)' : '***') . "\n";
    echo "JWT_SECRET: " . (env('JWT_SECRET') ? '(definido)' : 'NO DEFINIDO') . "\n";
    echo "</pre>";
} else {
    echo "<p class='error'>❌ Archivo .env NO existe en: {$envPath}</p>";
    echo "<p>Crea el archivo copiando .env.example</p>";
}
echo "</div>";

// ===================================
// 2. CONEXIÓN A BASE DE DATOS
// ===================================
echo "<div class='section'>";
echo "<h2>2. Conexión a Base de Datos</h2>";

try {
    require __DIR__ . '/app/config/database.php';
    
    $pdo = App\Config\Database::getConnection();
    echo "<p class='ok'>✅ Conexión exitosa a MySQL</p>";
    
    // Verificar si la tabla usuarios existe
    $tables = $pdo->query("SHOW TABLES LIKE 'usuarios'")->fetchAll();
    if (count($tables) > 0) {
        echo "<p class='ok'>✅ Tabla 'usuarios' existe</p>";
        
        // Contar usuarios
        $count = $pdo->query("SELECT COUNT(*) as total FROM usuarios")->fetch();
        echo "<p>Total de usuarios en BD: <strong>{$count['total']}</strong></p>";
        
        // Mostrar usuarios
        echo "<h3>Usuarios en la base de datos:</h3>";
        $users = $pdo->query("SELECT id, nombre, email, rol, activo FROM usuarios")->fetchAll();
        
        if (count($users) > 0) {
            echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Activo</th></tr>";
            foreach ($users as $u) {
                $activo = $u['activo'] ? '✅' : '❌';
                echo "<tr><td>{$u['id']}</td><td>{$u['nombre']}</td><td>{$u['email']}</td><td>{$u['rol']}</td><td>{$activo}</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ No hay usuarios en la base de datos</p>";
            echo "<p>Ejecuta el SQL: <code>backend/database_api.sql</code></p>";
        }
        
    } else {
        echo "<p class='error'>❌ Tabla 'usuarios' NO existe</p>";
        echo "<p>Importa el archivo: <code>backend/database_api.sql</code></p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error de conexión: " . $e->getMessage() . "</p>";
    echo "<p>Verifica las credenciales en .env</p>";
}
echo "</div>";

// ===================================
// 3. VERIFICAR PASSWORD HASH
// ===================================
echo "<div class='section'>";
echo "<h2>3. Verificación de Password</h2>";

if (isset($pdo)) {
    $user = $pdo->query("SELECT email, password FROM usuarios WHERE email = 'admin@sitracabana.org'")->fetch();
    
    if ($user) {
        echo "<p class='ok'>✅ Usuario 'admin@sitracabana.org' encontrado</p>";
        echo "<p>Hash en BD: <code style='font-size:10px;'>" . htmlspecialchars($user['password']) . "</code></p>";
        
        $passwordTest = 'admin123';
        $isValid = password_verify($passwordTest, $user['password']);
        
        if ($isValid) {
            echo "<p class='ok'>✅ Password 'admin123' es CORRECTO</p>";
        } else {
            echo "<p class='error'>❌ Password 'admin123' NO coincide</p>";
            
            // Generar nuevo hash correcto
            $newHash = password_hash($passwordTest, PASSWORD_BCRYPT);
            echo "<p><strong>Solución:</strong> Ejecuta este SQL en phpMyAdmin:</p>";
            echo "<pre>UPDATE usuarios SET password = '{$newHash}' WHERE email = 'admin@sitracabana.org';</pre>";
        }
    } else {
        echo "<p class='error'>❌ Usuario 'admin@sitracabana.org' NO encontrado</p>";
        
        // Generar SQL para insertar
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        echo "<p><strong>Solución:</strong> Ejecuta este SQL:</p>";
        echo "<pre>";
        echo "INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES\n";
        echo "('Administrador', 'admin@sitracabana.org', '{$hash}', 'superadmin', 1);";
        echo "</pre>";
    }
}
echo "</div>";

// ===================================
// 4. TEST DE LOGIN
// ===================================
echo "<div class='section'>";
echo "<h2>4. Test de Login (API)</h2>";

if (function_exists('curl_init')) {
    $baseUrl = "http://localhost/sitra_web/backend/public/api";
    
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
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<p><strong>URL:</strong> {$baseUrl}/auth/login</p>";
    echo "<p><strong>HTTP Code:</strong> {$httpCode}</p>";
    
    if ($error) {
        echo "<p class='error'>❌ Error cURL: {$error}</p>";
    }
    
    echo "<h3>Respuesta:</h3>";
    echo "<pre>" . json_encode(json_decode($response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
    if ($httpCode === 200) {
        echo "<p class='ok'>✅ LOGIN EXITOSO</p>";
    } else {
        echo "<p class='error'>❌ LOGIN FALLÓ</p>";
    }
} else {
    echo "<p class='error'>❌ cURL no disponible</p>";
}
echo "</div>";

// ===================================
// 5. VERIFICAR MOD_REWRITE
// ===================================
echo "<div class='section'>";
echo "<h2>5. Verificar mod_rewrite</h2>";

if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (in_array('mod_rewrite', $modules)) {
        echo "<p class='ok'>✅ mod_rewrite está habilitado</p>";
    } else {
        echo "<p class='error'>❌ mod_rewrite NO está habilitado</p>";
        echo "<p>Habilita en httpd.conf: <code>LoadModule rewrite_module modules/mod_rewrite.so</code></p>";
    }
} else {
    echo "<p>⚠️ No se puede verificar mod_rewrite (función no disponible)</p>";
}

echo "</div>";

// ===================================
// RESUMEN
// ===================================
echo "<div class='section' style='background: #f0f8ff;'>";
echo "<h2>📋 Resumen</h2>";
echo "<ol>";
echo "<li>Verifica que .env tenga las credenciales correctas</li>";
echo "<li>Importa backend/database_api.sql si la tabla no existe</li>";
echo "<li>Si el password no coincide, ejecuta el UPDATE sugerido arriba</li>";
echo "<li>Asegúrate que mod_rewrite esté habilitado en Apache</li>";
echo "</ol>";
echo "</div>";
?>
