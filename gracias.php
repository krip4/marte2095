<?php
// gracias.php - Página de confirmación para Marte 2095
require_once __DIR__ . '/config.php';
$mid = $_GET['mid'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Exitoso - Marte 2095</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; text-align: center; }
        .contenedor { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .exito { color: green; font-size: 24px; }
    </style>
</head>
<body>
    <div class="contenedor">
        <div class="exito">✓</div>
        <h1>¡Pago Procesado Exitosamente!</h1>
        <?php if ($mid): ?>
            <p>Su número de orden es: <strong><?= h($mid) ?></strong></p>
        <?php endif; ?>
        <p>Hemos recibido su pago correctamente. Nuestro equipo procesará su orden shortly.</p>
        <p><a href="index.php">Volver al inicio</a></p>
    </div>
</body>
</html>

