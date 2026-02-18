<?php
/**
 * Test de Permisos RBAC
 * 
 * Script para validar el funcionamiento del sistema de permisos.
 * Ejecutar: php test_rbac.php
 */

// Autoloader manual para el backend
spl_autoload_register(function($class) {
    $class = str_replace('App\\', '', $class);
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = __DIR__ . '/app/' . $class . '.php';
    
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    return false;
});

use App\Services\PermissionService;

$permissionService = new PermissionService();

echo "\n";
echo "============================================\n";
echo "   TEST: Sistema RBAC - PermissionService\n";
echo "============================================\n\n";

// ===================================
// TEST 1: superadmin tiene todo
// ===================================
echo "TEST 1: superadmin tiene todos los permisos\n";
echo "-------------------------------------------\n";

$tests = [
    'view_content',
    'edit_content',
    'delete_content',
    'manage_users',
    'manage_settings',
    'view_analytics',
];

$passed = 0;
$failed = 0;

foreach ($tests as $permission) {
    $can = $permissionService->can('superadmin', $permission);
    if ($can) {
        echo "✅ superadmin puede: {$permission}\n";
        $passed++;
    } else {
        echo "❌ superadmin NO puede: {$permission} (FALLO)\n";
        $failed++;
    }
}

echo "\n";

// ===================================
// TEST 2: directivo tiene permisos limitados
// ===================================
echo "TEST 2: directivo tiene permisos específicos\n";
echo "--------------------------------------------\n";

$shouldHave = ['view_content', 'edit_content', 'view_analytics'];
$shouldNotHave = ['delete_content', 'manage_users', 'manage_settings'];

foreach ($shouldHave as $permission) {
    $can = $permissionService->can('directivo', $permission);
    if ($can) {
        echo "✅ directivo puede: {$permission}\n";
        $passed++;
    } else {
        echo "❌ directivo NO puede: {$permission} (FALLO)\n";
        $failed++;
    }
}

foreach ($shouldNotHave as $permission) {
    $can = $permissionService->can('directivo', $permission);
    if (!$can) {
        echo "✅ directivo NO puede: {$permission} (correcto)\n";
        $passed++;
    } else {
        echo "❌ directivo puede: {$permission} (FALLO - no debería)\n";
        $failed++;
    }
}

echo "\n";

// ===================================
// TEST 3: editor solo edit_content
// ===================================
echo "TEST 3: editor tiene solo edit_content\n";
echo "--------------------------------------\n";

$shouldHave = ['edit_content'];
$shouldNotHave = ['view_content', 'delete_content', 'manage_users'];

foreach ($shouldHave as $permission) {
    $can = $permissionService->can('editor', $permission);
    if ($can) {
        echo "✅ editor puede: {$permission}\n";
        $passed++;
    } else {
        echo "❌ editor NO puede: {$permission} (FALLO)\n";
        $failed++;
    }
}

foreach ($shouldNotHave as $permission) {
    $can = $permissionService->can('editor', $permission);
    if (!$can) {
        echo "✅ editor NO puede: {$permission} (correcto)\n";
        $passed++;
    } else {
        echo "❌ editor puede: {$permission} (FALLO - no debería)\n";
        $failed++;
    }
}

echo "\n";

// ===================================
// TEST 4: hasAny
// ===================================
echo "TEST 4: hasAny - verificar múltiples permisos (ANY)\n";
echo "---------------------------------------------------\n";

$hasAny = $permissionService->hasAny('editor', ['view_content', 'edit_content']);
if ($hasAny) {
    echo "✅ editor tiene AL MENOS UNO de [view_content, edit_content]\n";
    $passed++;
} else {
    echo "❌ editor NO tiene ninguno (FALLO)\n";
    $failed++;
}

$hasAny = $permissionService->hasAny('editor', ['view_content', 'delete_content']);
if (!$hasAny) {
    echo "✅ editor NO tiene ninguno de [view_content, delete_content] (correcto)\n";
    $passed++;
} else {
    echo "❌ editor tiene uno (FALLO)\n";
    $failed++;
}

echo "\n";

// ===================================
// TEST 5: hasAll
// ===================================
echo "TEST 5: hasAll - verificar múltiples permisos (ALL)\n";
echo "----------------------------------------------------\n";

$hasAll = $permissionService->hasAll('directivo', ['view_content', 'edit_content']);
if ($hasAll) {
    echo "✅ directivo tiene TODOS [view_content, edit_content]\n";
    $passed++;
} else {
    echo "❌ directivo NO tiene todos (FALLO)\n";
    $failed++;
}

$hasAll = $permissionService->hasAll('directivo', ['edit_content', 'delete_content']);
if (!$hasAll) {
    echo "✅ directivo NO tiene TODOS [edit_content, delete_content] (correcto)\n";
    $passed++;
} else {
    echo "❌ directivo tiene todos (FALLO)\n";
    $failed++;
}

echo "\n";

// ===================================
// TEST 6: getPermissions
// ===================================
echo "TEST 6: getPermissions - obtener lista de permisos\n";
echo "---------------------------------------------------\n";

$permissions = $permissionService->getPermissions('directivo');
$expected = ['view_content', 'edit_content', 'view_analytics'];

if ($permissions === $expected) {
    echo "✅ getPermissions('directivo') devuelve array correcto\n";
    $passed++;
} else {
    echo "❌ getPermissions('directivo') devuelve array incorrecto\n";
    echo "Esperado: " . json_encode($expected) . "\n";
    echo "Obtenido: " . json_encode($permissions) . "\n";
    $failed++;
}

echo "\n";

// ===================================
// TEST 7: roleExists
// ===================================
echo "TEST 7: roleExists - verificar existencia de rol\n";
echo "------------------------------------------------\n";

$exists = $permissionService->roleExists('superadmin');
if ($exists) {
    echo "✅ roleExists('superadmin') = true\n";
    $passed++;
} else {
    echo "❌ roleExists('superadmin') = false (FALLO)\n";
    $failed++;
}

$exists = $permissionService->roleExists('admin');
if (!$exists) {
    echo "✅ roleExists('admin') = false (correcto, no existe)\n";
    $passed++;
} else {
    echo "❌ roleExists('admin') = true (FALLO)\n";
    $failed++;
}

echo "\n";

// ===================================
// RESUMEN
// ===================================
echo "============================================\n";
echo "RESUMEN:\n";
echo "✅ Pasados: {$passed}\n";
echo "❌ Fallidos: {$failed}\n";

if ($failed === 0) {
    echo "\n🎉 TODOS LOS TESTS PASARON ✅\n";
    exit(0);
} else {
    echo "\n⚠️ ALGUNOS TESTS FALLARON ❌\n";
    exit(1);
}
