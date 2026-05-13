<?php
// src/MeliHandler.php (o dentro de tu procesador)

function checkAndRefreshPath($conn) {
    // 1. Consultar el token actual
    $result = $conn->query("SELECT * FROM mercadolibre_credentials LIMIT 1");
    $data = $result->fetch_assoc();

    if (!$data) return false;

    $now = new DateTime();
    $expires = new DateTime($data['expires_at']);

    // 2. ¿Falta menos de 5 minutos para que venza o ya venció?
    if ($now >= $expires) {
        
        $url = "https://api.mercadolibre.com/oauth/token";
        $post_data = [
            'grant_type'    => 'refresh_token',
            'client_id'     => $data['client_id'],
            'client_secret' => $data['client_secret'],
            'refresh_token' => $data['refresh_token']
        ];

        // 3. Ejecutar la petición CURL a Mercado Libre
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $new_tokens = json_decode($response, true);
        curl_close($ch);

        if (isset($new_tokens['access_token'])) {
            // 4. IMPORTANTE: Guardar el nuevo access Y el nuevo refresh
            $new_expires = date('Y-m-d H:i:s', time() + $new_tokens['expires_in']);
            
            $stmt = $conn->prepare("UPDATE mercadolibre_credentials SET 
                                    access_token = ?, 
                                    refresh_token = ?, 
                                    expires_at = ? 
                                    WHERE client_id = ?");
            $stmt->bind_param("ssss", 
                $new_tokens['access_token'], 
                $new_tokens['refresh_token'], 
                $new_expires, 
                $data['client_id']
            );
            $stmt->execute();
            
            return $new_tokens['access_token'];
        }
    }

    return $data['access_token'];
}