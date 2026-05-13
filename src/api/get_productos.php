<?php
// src/api/get_productos.php

// 1. Incluimos la conexión a la base de datos
require_once '../init.php'; 

// 2. Definimos que la respuesta será un JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite que la otra App lo lea aunque esté en otro dominio

try {
    // 3. Consultamos solo los productos que NO han sido sincronizados (sincronizado_meli = 0)
    // Usamos el nombre de la tabla de tu repositorio: productos_auditron
    $sql = "SELECT id, sku, precio FROM productos_auditron WHERE sincronizado_meli = 0";
    $result = $conn->query($sql);

    $productos = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Estructuramos el dato para que sea "llegar y usar" por la API de Mercado Libre
            $productos[] = [
                "local_id" => $row['id'],    // ID interno por si la otra App necesita reportar error
                "sku"      => $row['sku'],   // SKU ya en mayúsculas y limpio
                "price"    => (float)$row['precio'], // Precio como número (decimal)
                "currency" => "CLP"          // Moneda fija para Chile
            ];
        }
    }

    // 4. Entregamos el resultado
    echo json_encode([
        "status" => "success",
        "total_pendientes" => count($productos),
        "data" => $productos
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al obtener productos: " . $e->getMessage()
    ]);
}