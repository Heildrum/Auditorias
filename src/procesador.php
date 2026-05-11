<?php
// src/procesador.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

error_reporting(0); // Desactivamos errores visuales para no corromper el JSON

// Salir de 'src' para encontrar las dependencias en la raíz
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/init.php'; 

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];

    try {
        ini_set('memory_limit', '512M');
        $spreadsheet = IOFactory::load($file);
        
        // Selección de hoja inteligente
        $sheet = $spreadsheet->getActiveSheet();
        if ($spreadsheet->sheetNameExists('(1) Sostenes')) {
            $sheet = $spreadsheet->getSheetByName('(1) Sostenes');
        }

        $data = $sheet->toArray();
        $colSKU = null; $colPrecio = null; $filaInicio = 0;

        // --- BUSCADOR DE COLUMNAS ---
        for ($i = 0; $i < 20; $i++) {
            if (!isset($data[$i])) break;
            foreach ($data[$i] as $indice => $valor) {
                if ($valor === null) continue;
                $texto = mb_strtolower(trim($valor), 'UTF-8');
                if (str_contains($texto, 'sku') || str_contains($texto, 'código')) $colSKU = $indice;
                if (str_contains($texto, 'precio') || str_contains($texto, 'monto')) $colPrecio = $indice;
            }
            if ($colSKU !== null && $colPrecio !== null) {
                $filaInicio = $i + 1; 
                break;
            }
        }

        if ($colSKU === null || $colPrecio === null) {
            echo json_encode(['status' => 'error', 'message' => 'No se encontraron las columnas SKU o Precio en el Excel.']);
            exit;
        }

        $contador = 0;
        $errores = 0;
        $details = [];
        $conn->begin_transaction();

        foreach ($data as $index => $row) {
            // Ignorar encabezados y filas de arriba
            if ($index < $filaInicio) continue;

            $skuRaw = $row[$colSKU] ?? '';
            $precioRaw = $row[$colPrecio] ?? '';

            // Limpieza del SKU (Lo guardamos en MAYÚSCULAS)
            $sku = strtoupper(trim((string)$skuRaw));
            if (empty($sku) || str_contains(strtolower($sku), 'escribe')) continue;

            // --- LÓGICA DE LIMPIEZA DE PRECIO CORREGIDA ---
            $precioLimpio = trim((string)$precioRaw);
            
            // Caso 1: Si el precio viene como "1.980" (el punto es de miles)
            // Lo quitamos si hay exactamente 3 dígitos después del punto
            if (str_contains($precioLimpio, '.') && !str_contains($precioLimpio, ',')) {
                $partes = explode('.', $precioLimpio);
                if (isset($partes[1]) && strlen($partes[1]) == 3) {
                    $precioLimpio = str_replace('.', '', $precioLimpio);
                }
            }
            
            // Caso 2: Si viene con formato "1.980,00" (punto miles, coma decimal)
            if (str_contains($precioLimpio, '.') && str_contains($precioLimpio, ',')) {
                $precioLimpio = str_replace('.', '', $precioLimpio); // Quita el punto
                $precioLimpio = str_replace(',', '.', $precioLimpio); // Cambia coma por punto para MySQL
            }

            // Dejamos solo números y el punto decimal final si existiera
            $precioFinal = preg_replace('/[^0-9.]/', '', $precioLimpio);

            if (!is_numeric($precioFinal) || $precioFinal <= 0) {
                $precioFinal = 0; 
            }

            try {
                // Inserción en la base de datos
                $stmt = $conn->prepare("INSERT INTO productos_auditron (sku, precio) VALUES (?, ?)");
                $stmt->bind_param("sd", $sku, $precioFinal);
                $stmt->execute();
                
                $details[] = [
                    'sku' => $sku,
                    'nombre' => 'Procesado en Auditron',
                    'precio_local' => $precioFinal,
                    'status' => 'processed'
                ];
                $contador++;
            } catch (Exception $e) {
                $errores++;
            }
        }

        $conn->commit();
        echo json_encode([
            'status' => 'success',
            'count' => $contador,
            'details' => $details,
            'errors' => $errores
        ]);

    } catch (Exception $e) {
        if (isset($conn)) $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}