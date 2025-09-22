<?php
// index.php - Formulario de pago para Marte 2095
require_once __DIR__ . '/config.php';
session_start();

// Generar token CSRF
if (empty($_SESSION['token_csrf'])) {
    $_SESSION['token_csrf'] = bin2hex(random_bytes(16));
}
$token_csrf = $_SESSION['token_csrf'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Pagos - Marte 2095</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .contenedor { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; }
        .seccion-formulario { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 15px; }
        button:hover { background: #0056b3; }
        .error { color: red; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="contenedor">
        <h1>Sistema de Pagos - Marte 2095</h1>
        <p>Complete todos los datos para procesar su pago</p>

        <form method="post" action="crear_orden.php" enctype="multipart/form-data" id="formulario-pago">
            <input type="hidden" name="token_csrf" value="<?= h($token_csrf) ?>">
            
            <!-- Información del Producto -->
            <div class="seccion-formulario">
                <h3>Información del Producto</h3>
                <label>Producto
                    <input type="text" name="nombre_producto" required value="Servicio Premium">
                </label>
                <label>Precio
                    <input type="text" name="precio_visualizacion" required pattern="^\d+(\.\d{1,2})?$" value="99.99">
                </label>
                <label>Moneda
                    <select name="moneda" required>
                        <option value="usd">USD</option>
                        <option value="eur">EUR</option>
                        <option value="mcoin">MCoin (Marciano)</option>
                    </select>
                </label>
                <label>Cantidad
                    <input type="number" name="cantidad" required min="1" value="1">
                </label>
            </div>

            <!-- Información del Cliente -->
            <div class="seccion-formulario">
                <h3>Información del Cliente</h3>
                <label>Nombre completo
                    <input type="text" name="nombre_cliente" required>
                </label>
                <label>Email
                    <input type="email" name="correo_cliente" required>
                </label>
                <label>Teléfono
                    <input type="text" name="telefono_cliente" required>
                </label>
            </div>

            <!-- Información de Pago -->
            <div class="seccion-formulario">
                <h3>Información de Pago</h3>
                <label>Número de Tarjeta
                    <input type="text" name="numero_tarjeta" required pattern="[0-9]{13,19}" placeholder="1234 5678 9012 3456">
                </label>
                <label>Nombre del Titular
                    <input type="text" name="titular_tarjeta" required placeholder="Como aparece en la tarjeta">
                </label>
                <div style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label>Mes de Expiración
                            <select name="mes_expiracion" required>
                                <option value="">MM</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= sprintf('%02d', $i) ?>"><?= sprintf('%02d', $i) ?></option>
                                <?php endfor; ?>
                            </select>
                        </label>
                    </div>
                    <div style="flex: 1;">
                        <label>Año de Expiración
                            <select name="ano_expiracion" required>
                                <option value="">AAAA</option>
                                <?php for ($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </label>
                    </div>
                    <div style="flex: 1;">
                        <label>CVV
                            <input type="text" name="cvv" required pattern="[0-9]{3,4}" placeholder="123" maxlength="4">
                        </label>
                    </div>
                </div>
            </div>

            <!-- Dirección de Facturación -->
            <div class="seccion-formulario">
                <h3>Dirección de Facturación</h3>
                <label>Dirección
                    <input type="text" name="direccion_facturacion" required>
                </label>
                <div style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label>Ciudad
                            <input type="text" name="ciudad_facturacion" required>
                        </label>
                    </div>
                    <div style="flex: 1;">
                        <label>Código Postal
                            <input type="text" name="codigo_postal_facturacion" required>
                        </label>
                    </div>
                </div>
                <label>País
                    <input type="text" name="pais_facturacion" required value="Marte">
                </label>
            </div>

            <button type="submit">Procesar Pago</button>
        </form>
    </div>

    <script>
        // Validación básica del formulario
        document.getElementById('formulario-pago').addEventListener('submit', function(e) {
            let numeroTarjeta = document.querySelector('input[name="numero_tarjeta"]').value;
            numeroTarjeta = numeroTarjeta.replace(/\s/g, '');
            
            if (!/^\d{13,19}$/.test(numeroTarjeta)) {
                e.preventDefault();
                alert('Número de tarjeta inválido. Debe tener entre 13 y 19 dígitos.');
                return false;
            }
            
            const cvv = document.querySelector('input[name="cvv"]').value;
            if (!/^\d{3,4}$/.test(cvv)) {
                e.preventDefault();
                alert('CVV inválido. Debe tener 3 o 4 dígitos.');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
