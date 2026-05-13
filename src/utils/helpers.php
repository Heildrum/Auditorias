<?php
// src/utils/helpers.php

/**
 * Estandariza el SKU: Quita espacios y convierte a MAYÚSCULAS.
 * Es vital para que la API de Mercado Libre no duplique productos.
 */
function limpiarSKU($sku) {
    return strtoupper(trim($sku));
}

/**
 * Transforma precios de Chile (ej: 7.390,00) a formato decimal (7390.00).
 * Elimina puntos de miles y cambia coma decimal por punto.
 */
function limpiarPrecio($precioRaw) {
    // 1. Quitar puntos (separadores de miles en Chile)
    $sinMiles = str_replace('.', '', $precioRaw);
    
    // 2. Cambiar coma por punto (separador decimal estándar en SQL/APIs)
    $conPunto = str_replace(',', '.', $sinMiles);
    
    // 3. Dejar solo números y el punto decimal, luego convertir a float
    return floatval(preg_replace('/[^0-9.]/', '', $conPunto));
}