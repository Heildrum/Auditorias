
     <?php
// src/procesador.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

ini_set('display_errors', 1);
error_reporting(E_ALL);

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
$tablesPath = __DIR__ . '/../config/tables.php'; // Cambiamos init por tables

// 1. Validaciones de archivos
if (!file_exists($autoloadPath)) {
    die(json_encode(['status' => 'error', 'message' => 'Falta vendor/autoload.php']));
}
require_once $autoloadPath;

if (!file_exists($tablesPath)) {
    die(json_encode(['status' => 'error', 'message' => 'No se encontró config/tables.php']));
}

// 2. Ejecutar tables.php (Esto conecta a la BD y limpia la tabla)
// Usamos ob_start para que los "echo" de tables.php no ensucien el JSON de respuesta
ob_start(); 
require_once $tablesPath; 
ob_end_clean(); 

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    try {
        $file = $_FILES['excel_file']['tmp_name'];
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        $insertados = 0;
        $actualizados = 0;

        // Preparamos la consulta una sola vez fuera del bucle para mayor velocidad
        $stmt = $conn->prepare("INSERT INTO productos_auditron (sku, precio) VALUES (?, ?) 
                                ON DUPLICATE KEY UPDATE precio = VALUES(precio)");

        for ($i = 1; $i < count($data); $i++) {
            $fila = $data[$i];
            
            $sku = isset($fila[0]) ? trim($fila[0]) : null;
            $precioRaw = isset($fila[1]) ? $fila[1] : '0';

            if ($sku !== null && $sku !== '') {
                // Si el precio ya es un número (flotante), lo usamos directamente
                if (is_numeric($precioRaw)) {
                    $precioFinal = $precioRaw;
                } else {
                    // Limpieza para formatos de texto como "$ 1.234,50"
                    $precioLimpio = preg_replace('/[^\d,.]/', '', $precioRaw);
                    // Si tiene punto y coma, asumimos punto = miles y coma = decimal
                    $precioFinal = str_replace(',', '.', str_replace('.', '', $precioLimpio));
                }

                $stmt->bind_param("ss", $sku, $precioFinal); // Usamos "s" para evitar pérdida de precisión en PHP
                
                if ($stmt->execute()) {
                    // 1: Insertado, 2: Actualizado, 0: Ya existía con el mismo precio (lo contamos como éxito)
                    if ($stmt->affected_rows === 1) {
                        $insertados++;
                    } else {
                        $actualizados++;
                    }
                }
            }
        }

        $stmt->close();
        $total = $insertados + $actualizados;

        echo json_encode([
            'status' => 'success',
            'count' => $total,
            'details' => [
                'nuevos' => $insertados,
                'actualizados' => $actualizados
            ],
            'message' => "Proceso completado. Total: $total (Nuevos: $insertados, Actualizados: $actualizados)"
        ]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
}   
