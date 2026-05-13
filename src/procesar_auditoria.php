<?php
require_once __DIR__ . '/../config/init.php';
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// 1. Obtener Token de la DB (Hugo Tapia)
$sql = "SELECT access_token FROM mercadolibre_credentials LIMIT 1";
$res = $conn->query($sql);
$cred = $res->fetch_assoc();
$token = $cred['access_token'];

if (isset($_FILES['excel_file'])) {
    $archivoTmp = $_FILES['excel_file']['tmp_name'];
    $spreadsheet = IOFactory::load($archivoTmp);
    $datosExcel = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

    // 2. Preparar Excel de Salida (Reporte de errores)
    $reporte = new Spreadsheet();
    $sheet = $reporte->getActiveSheet();
    $sheet->fromArray(['SKU', 'Precio Lista Actual (Excel)', 'Precio Antiguo (MeLi)', 'Diferencia'], NULL, 'A1');
    
    $filaReporte = 2;

    foreach ($datosExcel as $index => $col) {
        if ($index == 1) continue; // Saltar cabecera

        $sku = trim($col['A']);
        $precioActual = (float)$col['B'];

        // 3. Identificar ítem en MeLi por SKU
        $searchUrl = "https://api.mercadolibre.com/items/search?seller_custom_field=" . urlencode($sku);
        
        $ch = curl_init($searchUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
        $searchRes = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!empty($searchRes['results'])) {
            $itemId = $searchRes['results'][0];

            // 4. Consultar precio en Mercado Libre (Precio Antiguo)
            $itemUrl = "https://api.mercadolibre.com/items/$itemId";
            $ch2 = curl_init($itemUrl);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            $itemData = json_decode(curl_exec($ch2), true);
            curl_close($ch2);

            $precioMeli = (float)$itemData['price'];

            // 5. Detectar Error / Diferencia
            if ($precioActual != $precioMeli) {
                $diferencia = $precioActual - $precioMeli;

                $sheet->setCellValue("A$filaReporte", $sku);
                $sheet->setCellValue("B$filaReporte", $precioActual);
                $sheet->setCellValue("C$filaReporte", $precioMeli);
                $sheet->setCellValue("D$filaReporte", $diferencia);
                $filaReporte++;
            }
        }
        usleep(100000); // Evitar saturar la API
    }

    // 6. Enviar el Excel con las discrepancias
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="discrepancias_precios.xlsx"');
    $writer = new Xlsx($reporte);
    $writer->save('php://output');
    exit;
}