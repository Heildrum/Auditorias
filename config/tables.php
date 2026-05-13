<?php
// Auditorias/config/tables.php

// Ajustamos la ruta para llegar a init.php que está en src/
require_once __DIR__ . '/../init.php'; 

/**
 * 1. Creación de la tabla 'productos_auditron'
 * Guarda los datos limpios del Excel para la comparación.
 */
$sql_productos = "CREATE TABLE IF NOT EXISTS productos_auditron (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(100) NOT NULL,
    precio DECIMAL(30, 6) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (sku) 
) ENGINE=InnoDB;";

if ($conn->query($sql_productos)) {
    echo " Estructura verificada: La tabla 'productos_auditron' está lista.<br>";
} else {
    die(" Error al crear la tabla productos: " . $conn->error);
}

/**
 * 2. Creación de la tabla 'mercadolibre_credentials'
 * Esta tabla permitirá automatizar la conexión con la cuenta a auditar.
 */
$sql_credentials = "CREATE TABLE IF NOT EXISTS mercadolibre_credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_name VARCHAR(100) NOT NULL, -- Para identificar qué cuenta estás auditando
    client_id VARCHAR(100) NOT NULL,
    client_secret VARCHAR(100) NOT NULL,
    access_token TEXT,                 -- El token que dura 6 horas
    refresh_token VARCHAR(255),        -- El token para renovar automáticamente
    expires_at DATETIME,               -- Fecha y hora de expiración del token
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;";

if ($conn->query($sql_credentials)) {
    echo " Estructura verificada: La tabla 'mercadolibre_credentials' está lista.<br>";
} else {
    die(" Error al crear la tabla de credenciales: " . $conn->error);
}

// 3. Vaciar la tabla de productos (Opcional)
// if ($conn->query("TRUNCATE TABLE productos_auditron")) {
//     echo "Tabla vaciada: Se han borrado los registros de productos.";
// }