<?php
// test_mock.php
$mockFilas = [
    ['sku' => '  prod-001  ', 'precio' => '7.390,00'], // Caso con puntos y comas
    ['sku' => 'PROD-002', 'precio' => '26.990'],     // Caso solo con punto de miles
    ['sku' => 'prod-003', 'precio' => '$ 1.500,50']  // Caso con símbolo de moneda
];

foreach ($mockFilas as $fila) {
    $skuRaw = $fila['sku'];
    $precioRaw = $fila['precio'];

    // Tu lógica de transformación
    $skuMeli = strtoupper(trim($skuRaw));
    $precioSinMiles = str_replace('.', '', $precioRaw);
    $precioConPunto = str_replace(',', '.', $precioSinMiles);
    $precioMeli = floatval(preg_replace('/[^0-9.]/', '', $precioConPunto));

    echo "Original: $skuRaw | $precioRaw <br>";
    echo "Transformado: $skuMeli | $precioMeli <br><br>";
}