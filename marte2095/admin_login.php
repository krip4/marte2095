<?php
// admin_login.php - Login de administradores para Marte 2095 (usa tabla admins)
require_once __DIR__ . '/config.php';
session_start();

$error = '';

/**
 * Aseguramos que exista una función h() para escapar HTML.
 * Si tu config.php ya define h(), no hay problema: esta definición sólo se usará si no existe.
 */
if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

try {
    // Conectar a la BD usando la función obtener_pdo() que usa tu proyecto (si existe)
    if (!function_exists('obtener_pdo')) {
        throw new Exception('Falta la función obtener_pdo() en config.php. Asegúrate de tenerla definida.');
    }
    $pdo = obtener_pdo();
} catch (Exception $e) {
    // Si no podemos conectarnos, lo mostramos en la pantalla para debug
    $error = 'Error interno de configuración: ' . h($e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $usuario = trim($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if ($usuario === '') {
        $error = 'Usuario requerido';
    } elseif ($contrasena === '') {
        $error = 'Contraseña requerida';
    } else {
        // Buscar usuario en la tabla admins
        $stmt = $pdo->prepare('SELECT id, username, password_hash, nombre FROM admins WHERE username = ? LIMIT 1');
        $stmt->execute([$usuario]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fila && !empty($fila['password_hash']) && password_verify($contrasena, $fila['password_hash'])) {
            // Login correcto
            $_SESSION['admin_conectado'] = true;
            $_SESSION['usuario_admin'] = $fila['username'];
            $_SESSION['admin_nombre'] = $fila['nombre'] ?? $fila['username'];
            header('Location: admin_orders.php'); // redirect a admin_orders (nombre en inglés usado en tus archivos)
            exit;
        } else {
            $error = 'Credenciales inválidas';
        }
    }
}

// Si ya está logueado, redirigir
if (isset($_SESSION['admin_conectado']) && $_SESSION['admin_conectado'] === true) {
    header('Location: admin_orders.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Marte 2095</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .contenedor-login { max-width: 400px; margin: 100px auto; padding: 20px; background: white; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .error { color: red; text-align: center; }
    </style>
</head>
<body>
    <div class="contenedor-login">
        <h2>Acceso Administrativo</h2>
        <?php if ($error): ?>
            <p class="error"><?= h($error) ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="usuario" placeholder="Usuario" required>
            <input type="password" name="contrasena" placeholder="Contraseña" required>
            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>
