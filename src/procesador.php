<?php
// src/procesador.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// CAMBIO: Activamos errores para debug (luego cámbialo a 0)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verifica que estas rutas sean reales en tu servidor
$autoload = __DIR__ . '/../vendor/autoload.php';
$init = __DIR__ . '/../config/init.php';

if (!file_exists($autoload)) {
    die(json_encode(['status' => 'error', 'message' => 'Falta vendor/autoload.php. Ejecuta composer install.']));
}
require_once $autoload;

if (!file_exists($init)) {
    die(json_encode(['status' => 'error', 'message' => 'No se encontró config/init.php']));
}
require_once $init; 

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];

    try {
        // ... (resto de tu lógica de limpieza de precios está excelente) ...
        
        // Un pequeño ajuste: asegúrate de que la tabla esté limpia antes de insertar
        // si quieres que la auditoría sea solo del Excel actual:
        $conn->query("TRUNCATE TABLE productos_auditron");

        // ... (tu bucle foreach y lógica de inserción) ...

        echo json_encode([
            'status' => 'success',
            'count' => $contador,
            'message' => 'Procesamiento completado con éxito'
        ]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error de Spreadsheet: ' . $e->getMessage()]);
    }
}
