<?php
// Auditorias/config/tables.php

// Ajustamos la ruta para llegar a init.php que está en src/
require_once __DIR__ . '/../init.php'; 

/**
 * Creación de la tabla 'productos_auditron'
 * Esta tabla es el corazón de la integración. 
 * Guardará los datos limpios del Excel y controlará la subida a Meli.
 */
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
