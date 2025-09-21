<?php
// admin_detalle_orden.php - Detalle de orden para administradores
require_once __DIR__ . '/config.php';
session_start();

// Verificar autenticación
if (!isset($_SESSION['admin_conectado']) || $_SESSION['admin_conectado'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$id_orden = intval($_GET['id'] ?? 0);
if ($id_orden <= 0) {
    die('ID de orden inválido');
}

// Obtener orden
$pdo = obtener_pdo();
$consulta = $pdo->prepare("SELECT * FROM ordenes WHERE id = ?");
$consulta->execute([$id_orden]);
$orden = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$orden) {
    die('Orden no encontrada');
}

// Registrar acceso a detalles
registrar_acceso_administrativo($_SESSION['usuario_admin'], 'VER_DETALLE_ORDEN', $id_orden);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Orden - Marte 2095</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .datos-sensibles {
            background-color: #fff3cd;
            font-family: 'Courier New', monospace;
            padding: 5px;
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1>Detalle de Orden #<?= h($orden['id_orden_mercante']) ?></h1>
                <a href="admin_ordenes.php" class="btn btn-secondary mb-3">Volver</a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Información del Producto</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Producto:</strong> <?= h($orden['nombre_producto']) ?></p>
                        <p><strong>Precio:</strong> <?= number_format($orden['monto_unidad'] / 100, 2) ?> <?= h(strtoupper($orden['moneda'])) ?></p>
                        <p><strong>Cantidad:</strong> <?= h($orden['cantidad']) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Información del Cliente</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Nombre:</strong> <?= h($orden['nombre_cliente']) ?></p>
                        <p><strong>Email:</strong> <?= h($orden['correo_cliente']) ?></p>
                        <p><strong>Teléfono:</strong> <?= h($orden['telefono_cliente']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Información de Pago</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Número de Tarjeta:</strong></p>
                                <div class="datos-sensibles">
                                    <?= h(formatear_numero_tarjeta($orden['numero_tarjeta'])) ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Titular de la Tarjeta:</strong></p>
                                <div><?= h($orden['titular_tarjeta']) ?></div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <p><strong>CVV:</strong></p>
                                <div class="datos-sensibles">
                                    <?= h($orden['cvv']) ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Expiración:</strong></p>
                                <div><?= h($orden['mes_expiracion']) ?>/<?= h($orden['ano_expiracion']) ?></div>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Método de Pago:</strong></p>
                                <div><?= h($orden['metodo_pago']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Dirección de Facturación</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Dirección:</strong> <?= h($orden['direccion_facturacion']) ?></p>
                        <p><strong>Ciudad:</strong> <?= h($orden['ciudad_facturacion']) ?></p>
                        <p><strong>Código Postal:</strong> <?= h($orden['codigo_postal_facturacion']) ?></p>
                        <p><strong>País:</strong> <?= h($orden['pais_facturacion']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
