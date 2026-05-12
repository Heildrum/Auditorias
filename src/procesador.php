<?php
// procesador.php
require_once 'init.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $filas = $sheet->toArray();

        $nuevos = 0;
        $actualizados = 0;
        $total = 0;

        // Omitir la primera fila si es encabezado (Título de columnas)
        $primeraFila = true;

        foreach ($filas as $fila) {
            if ($primeraFila) {
                $primeraFila = false;
                continue;
            }

            // 1. CAPTURA DE DATOS (Columna A = SKU, Columna B = Precio)
            $skuRaw = isset($fila[0]) ? $fila[0] : '';
            $precioRaw = isset($fila[1]) ? $fila[1] : '0';

            // 2. TRANSFORMACIÓN PARA MERCADO LIBRE
            // SKU: Quitamos espacios y pasamos a mayúsculas para evitar duplicados
            $skuMeli = strtoupper(trim($skuRaw));

            // PRECIO: Manejo de formato Chile (7.390,00 -> 7390.00)
            // Primero quitamos el punto de miles
            $precioSinMiles = str_replace('.', '', $precioRaw);
            // Luego cambiamos la coma decimal por punto
            $precioConPunto = str_replace(',', '.', $precioSinMiles);
            // Limpiamos cualquier otro carácter (como $) y convertimos a número
            $precioMeli = floatval(preg_replace('/[^0-9.]/', '', $precioConPunto));

            // 3. PROCESAMIENTO EN BASE DE DATOS
            if (!empty($skuMeli)) {
                // Usamos ON DUPLICATE KEY UPDATE para saber si es nuevo o actualizado
                $stmt = $conn->prepare("INSERT INTO productos_auditron (sku, precio) 
                                        VALUES (?, ?) 
                                        ON DUPLICATE KEY UPDATE precio = VALUES(precio)");
                
                $stmt->bind_param("sd", $skuMeli, $precioMeli);
                
                if ($stmt->execute()) {
                    if ($stmt->affected_rows === 1) {
                        $nuevos++;
                    } elseif ($stmt->affected_rows === 2) {
                        $actualizados++;
                    }
                    $total++;
                }
                $stmt->close();
            }
        }

        echo json_encode([
            "status" => "success",
            "count" => $total,
            "details" => ["nuevos" => $nuevos, "actualizados" => $actualizados],
            "message" => "Proceso completado. Total: $total (Nuevos: $nuevos, Actualizados: $actualizados)"
        ]);

    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Error al procesar el archivo: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "No se recibió ningún archivo."
    ]);
}
