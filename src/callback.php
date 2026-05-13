<?php
// Auditorias/src/callback.php
require_once __DIR__ . '/init.php';

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // 1. Recuperamos tus llaves de la base de datos
    $result = $conn->query("SELECT client_id, client_secret FROM mercadolibre_credentials LIMIT 1");
    $row = $result->fetch_assoc();

    if (!$row) {
        die("Error: No hay credenciales configuradas en la base de datos.");
    }

    $client_id = $row['client_id'];
    $client_secret = $row['client_secret'];
    $redirect_uri = "http://localhost/Auditorias/src/callback.php";

    // 2. Petición a Mercado Libre para cambiar el CODE por TOKENS
    $url = "https://api.mercadolibre.com/oauth/token";
    $post_data = [
        'grant_type' => 'authorization_code',
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'code' => $code,
        'redirect_uri' => $redirect_uri
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    if (isset($data['access_token'])) {
        // 3. Calculamos la expiración (6 horas desde ahora)
        $expires_at = date('Y-m-d H:i:s', time() + $data['expires_in']);

        // 4. Guardamos TODO en la base de datos
        $stmt = $conn->prepare("UPDATE mercadolibre_credentials SET 
                                access_token = ?, 
                                refresh_token = ?, 
                                expires_at = ? 
                                WHERE client_id = ?");
        $stmt->bind_param("ssss", $data['access_token'], $data['refresh_token'], $expires_at, $client_id);
        
        if ($stmt->execute()) {
            echo "<h1>¡Vinculación Exitosa!</h1>";
            echo "El token se ha guardado y vencerá el: " . $expires_at;
            echo "<br><a href='../public/config_meli.php'>Volver al Panel</a>";
        }
    } else {
        echo "Error de Mercado Libre: " . ($data['message'] ?? 'Desconocido');
    }
} else {
    echo "No se recibió el código de autorización.";
}
?>