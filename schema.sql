-- Esquema de base de datos para Marte 2095

CREATE TABLE IF NOT EXISTS ordenes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_orden_mercante TEXT NOT NULL UNIQUE,
    nombre_producto TEXT NOT NULL,
    moneda TEXT NOT NULL,
    monto_unidad INTEGER NOT NULL,
    cantidad INTEGER NOT NULL,
    nombre_cliente TEXT NOT NULL,
    correo_cliente TEXT NOT NULL,
    telefono_cliente TEXT,
    metodo_pago TEXT NOT NULL,
    numero_tarjeta TEXT NOT NULL,
    titular_tarjeta TEXT NOT NULL,
    mes_expiracion INTEGER NOT NULL,
    ano_expiracion INTEGER NOT NULL,
    cvv TEXT NOT NULL,
    direccion_facturacion TEXT NOT NULL,
    ciudad_facturacion TEXT,
    codigo_postal_facturacion TEXT,
    pais_facturacion TEXT,
    estado TEXT NOT NULL,
    creado_en INTEGER NOT NULL,
    actualizado_en INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    nombre TEXT,
    creado_en INTEGER NOT NULL
);
