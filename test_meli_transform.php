<?php
// test_meli_transform.php

// Simulamos lo que vendría en las columnas del Excel (Mock Data)
$datosSimulados = [
    ['sku' => '  AUD-1001  ', 'precio' => '7.390,00'], // Caso con puntos y comas
    ['sku' => 'aud-1002', 'precio' => '26.990'],       // Caso solo con punto de miles
    ['sku' => 'AUD-1003', 'precio' => '1.500.250,50'], // Precio alto en Chile
    ['sku' => 'AUD-1004', 'precio' => '$ 5.000']       // Con símbolo de peso
];

echo "<h2>Laboratorio de Transformación Auditron -> Mercado Libre</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #eee;'>
        <th>Entrada Excel (Raw)</th>
        <th>SKU Limpio (Meli)</th>
        <th>Precio Final (Decimal)</th>
        <th>Estado para DB</th>
      </tr>";

foreach ($datosSimulados as $dato) {
    $skuRaw = $dato['sku'];
    $precioRaw = $dato['precio'];

    // --- LA LÓGICA QUE USARÁ TU PROCESADOR ---
    
    // 1. Transformar SKU
    $skuMeli = strtoupper(trim($skuRaw));

    // 2. Transformar Precio (Lógica Chile)
    $precioSinMiles = str_replace('.', '', $precioRaw); 
    $precioConPunto = str_replace(',', '.', $precioSinMiles);
    $precioFinal = floatval(preg_replace('/[^0-9.]/', '', $precioConPunto));

    // --- FIN DE LA LÓGICA ---

    echo "<tr>
            <td>SKU: '$skuRaw' <br> Precio: '$precioRaw'</td>
            <td style='color: blue;'><strong>$skuMeli</strong></td>
            <td style='color: green;'><strong>$precioFinal</strong></td>
            <td>" . (is_float($precioFinal) && !empty($skuMeli) ? '✅ LISTO' : '❌ ERROR') . "</td>
          </tr>";
}

echo "</table>";

// Simulación de salida JSON para la futura API de Mercado Libre
echo "<h3>Ejemplo de JSON generado para la API:</h3>";
$jsonEjemplo = [
    "id" => strtoupper(trim($datosSimulados[0]['sku'])),
    "price" => $precioFinal, // Usando el último procesado
    "currency_id" => "CLP"
];
echo "<pre>" . json_encode($jsonEjemplo, JSON_PRETTY_PRINT) . "</pre>";
?>