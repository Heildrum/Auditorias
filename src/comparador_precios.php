<?php
require_once __DIR__ . '/../config/init.php';
// Usaremos una librería simple como Spout o PhpSpreadsheet para manejar el Excel
require 'vendor/autoload.php'; 

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// 1. Obtener credenciales de la DB (Ya las tienes guardadas)
$sql = "SELECT access_token FROM mercadolibre_credentials LIMIT 1";
$res = $conn->query($sql);
$cred = $res->fetch_assoc();
$token = $cred['access_token'];

// 2. Cargar el Excel local (Asegúrate de que la ruta sea correcta en tu master)
$inputFileName = __DIR__ . '/../uploads/lista_productos.xlsx';
$spreadsheet = IOFactory::load($inputFileName);
$sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

// 3. Preparar el archivo de resultados
$outputSpreadsheet = new Spreadsheet();
$outSheet = $outputSpreadsheet->getActiveSheet();
$outSheet->setCellValue('A1', 'SKU');
$outSheet->setCellValue('B1', 'Precio Excel');
$outSheet->setCellValue('C1', 'Precio MeLi');
$outSheet->setCellValue('D1', 'Diferencia');
$outSheet->setCellValue('E1', 'Estado');

$rowNum = 2;

// 4. Bucle de comparación
foreach ($sheetData as $index => $row) {
    if ($index == 1) continue; // Saltar cabecera del Excel

    $sku = $row['A']; // Columna A: SKU
    $precioExcel = $row['B']; // Columna B: Precio esperado

    // Consultar el ítem por SKU en MeLi
    // Usamos el recurso /items/search con el filtro seller_custom_field
    $url = "https://api.mercadolibre.com/items/search?seller_id=ME_ID&seller_custom_field=" . urlencode($sku);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (!empty($response['results'])) {
        $itemId = $response['results'][0];
        
        // Obtenemos el detalle del ítem para ver el precio actual
        $itemUrl = "https://api.mercadolibre.com/items/$itemId";
        $chItem = curl_init($item_Url);
        curl_setopt($chItem, CURLOPT_RETURNTRANSFER, true);
        $itemData = json_decode(curl_exec($chItem), true);
        curl_close($chItem);

        $precioMeli = $itemData['price'];
        $diferencia = $precioExcel - $precioMeli;
        $estado = ($diferencia == 0) ? "OK" : "DISCREPANCIA";

        // Escribir en el Excel de Auditoría
        $outSheet->setCellValue("A$rowNum", $sku);
        $outSheet->setCellValue("B$rowNum", $precioExcel);
        $outSheet->setCellValue("C$rowNum", $precioMeli);
        $outSheet->setCellValue("D$rowNum", $diferencia);
        $outSheet->setCellValue("E$rowNum", $estado);
        $rowNum++;
    }
}

// 5. Guardar el reporte final
$writer = new Xlsx($outputSpreadsheet);
$reporteNombre = 'auditoria_precios_' . date('Ymd_His') . '.xlsx';
$writer->save(__DIR__ . '/../reports/' . $reporteNombre);

echo "Auditoría finalizada. Se encontró el reporte en la carpeta /reports/";