<?php
// test_final_auditron.php
require_once 'src/utils/helpers.php';

// 1. SIMULAMOS LA ENTRADA (Lo que vendría del Excel de Chile)
$datosExcel = [
    ['sku' => '  aud-9900  ', 'precio' => '12.500,00'],
    ['sku' => 'AUD-8800', 'precio' => '1.200.000'],
    ['sku' => '  promo-50  ', 'precio' => '5.990,90']
];

echo "<h2>🧪 Mock Final: Flujo Auditron -> Meli</h2>";
echo "<pre>";

foreach ($datosExcel as $indice => $fila) {
    // 2. PROCESAMOS (Lo que hace tu nuevo procesador.php)
    $skuLimpio = limpiarSKU($fila['sku']);
    $precioLimpio = limpiarPrecio($fila['precio']);

    echo "--- Registro #".($indice + 1)." ---\n";
    echo "Entrada Excel: SKU[" . $fila['sku'] . "] Precio[" . $fila['precio'] . "]\n";
    echo "Limpio para DB: SKU[" . $skuLimpio . "] Precio[" . $precioLimpio . "]\n";

    // 3. SIMULAMOS SALIDA (Lo que vería la otra App en get_productos.php)
    $jsonMeli = [
        "id" => $skuLimpio,
        "price" => $precioLimpio,
        "currency_id" => "CLP"
    ];

    echo "JSON para API: " . json_encode($jsonMeli) . "\n\n";
}

echo "</pre>";
?>