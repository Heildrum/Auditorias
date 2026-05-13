<?php
session_start();
require_once __DIR__ . '/../config/init.php';

// 1. Verificamos que Mercado Libre nos envió el código de vuelta
if (!isset($_GET['code'])) {
    die("Error: No se recibió el código de autorización de Mercado Libre.");
}

$code = $_GET['code'];

// 2. Recuperamos el Client ID y Secret que guardamos en la sesión en config_meli.php
$client_id = $_SESSION['meli_client_id'] ?? null;
$client_secret = $_SESSION['meli_client_secret'] ?? null;

if (!$client_id || !$client_secret) {
    die("Error: Se perdieron las credenciales de la sesión. Intenta guardar los datos de nuevo en el dashboard.");
}

// 3. Preparamos la llamada a Mercado Libre para cambiar el código por un TOKEN
$url = "https://api.mercadolibre.com/oauth/token";

$post_data = [
    'grant_type' => 'authorization_code',
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'code' => $code,
    'redirect_uri' => "https://127.0.0.1/Auditorias/src/callback.php"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

// Esto es clave para que no te rebote el HTTPS local
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

// 4. Si todo salió bien, guardamos el Token en la base de datos
if (isset($data['access_token'])) {
    $access_token = $data['access_token'];
    $refresh_token = $data['refresh_token'];
    $user_id = $data['user_id']; // El ID de usuario de Hugo Tapia

    // Actualizamos la tabla (asumiendo que tienes columnas para tokens)
    $stmt = $conn->prepare("UPDATE mercadolibre_credentials SET access_token = ?, refresh_token = ?, user_id = ? WHERE client_id = ?");
    $stmt->bind_param("ssss", $access_token, $refresh_token, $user_id, $client_id);
    
    if ($stmt->execute()) {
        echo "<h1>✅ ¡Vinculación Exitosa!</h1>";
        echo "<p>La cuenta de Hugo Tapia ha sido vinculada. Ya puedes cerrar esta ventana y volver al sistema.</p>";
    } else {
        echo "Error al guardar los tokens: " . $conn->error;
    }
} else {
    echo "<h1>❌ Error en la vinculación</h1>";
    echo "<pre>"; print_r($data); echo "</pre>";
}