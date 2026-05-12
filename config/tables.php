<?php
// config/tables.php

// Jalamos la conexión de init.php
require_once 'init.php';

// 1. Crear la tabla si no existe
$sql = "CREATE TABLE IF NOT EXISTS productos_auditron (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(100) NOT NULL,
    precio DECIMAL(30, 6) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (sku) 
) ENGINE=InnoDB;";

if ($conn->query($sql)) {
    echo " Estructura verificada: La tabla 'productos_auditron' está lista.<br>";
} else {
    die(" Error al crear la tabla: " . $conn->error);
}

// 2. Vaciar la tabla (Opcional: Descomenta para limpiar en cada carga)
// if ($conn->query("TRUNCATE TABLE productos_auditron")) {
//     echo "Tabla vaciada: Se han borrado todos los registros anteriores.";
// } else {
//     echo " Error al vaciar la tabla: " . $conn->error;
// }
