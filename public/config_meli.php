<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/tables.php';

// Iniciamos sesión para guardar el Client ID y Secret temporalmente
session_start();

$message = "";
$auth_url = "#"; // Por defecto el botón no hace nada hasta que guardes

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_name = $_POST['account_name'];
    $client_id = $_POST['client_id'];
    $client_secret = $_POST['client_secret'];

    // Guardamos en la sesión para que el callback.php pueda usarlos luego
    $_SESSION['meli_client_id'] = $client_id;
    $_SESSION['meli_client_secret'] = $client_secret;

    // Guardamos o actualizamos en la base de datos
    $stmt = $conn->prepare("INSERT INTO mercadolibre_credentials (account_name, client_id, client_secret) 
                            VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE client_secret = VALUES(client_secret), account_name = VALUES(account_name)");
    $stmt->bind_param("sss", $account_name, $client_id, $client_secret);

    if ($stmt->execute()) {
        $message = "✅ Datos guardados localmente.";
        
        // Generamos la URL de Mercado Libre directamente aquí
        $redirect_uri = "https://127.0.0.1/Auditorias/src/callback.php";
        $auth_url = "https://auth.mercadolibre.com.ar/authorization?response_type=code&client_id=" . $client_id . "&redirect_uri=" . urlencode($redirect_uri);
    } else {
        $message = "❌ Error al guardar: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Auditoría - Meli</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 500px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: auto; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #3483fa; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #2969ce; }
        .btn-vincular { background: #00a650; margin-top: 10px; text-decoration: none; display: block; text-align: center; padding: 12px; border-radius: 4px; color: white; font-weight: bold; }
        .btn-disabled { background: #ccc; cursor: not-allowed; }
    </style>
</head>
<body>

<div class="container">
    <h2>Configuración de Cuenta</h2>
    <p>Ingresa los datos de tu aplicación de Mercado Libre Developers.</p>
    
    <?php if($message) echo "<p style='color: green; font-weight: bold;'>$message</p>"; ?>

    <form method="POST">
        <label>Nombre de la Cuenta (Ej: Hugo Tapia)</label>
        <input type="text" name="account_name" required placeholder="Nombre para identificar esta cuenta" value="<?php echo isset($_POST['account_name']) ? $_POST['account_name'] : ''; ?>">
        
        <label>Client ID</label>
        <input type="text" name="client_id" required placeholder="Tu ID de aplicación" value="<?php echo isset($_POST['client_id']) ? $_POST['client_id'] : ''; ?>">
        
        <label>Client Secret Key</label>
        <input type="password" name="client_secret" required placeholder="Tu Secret Key">
        
        <button type="submit">1. Guardar Configuración</button>
    </form>

    <hr>

    <?php if($auth_url !== "#"): ?>
        <p>¡Listo! Ahora vincula la cuenta real:</p>
        <a href="<?php echo $auth_url; ?>" class="btn-vincular">2. Vincular con Mercado Libre</a>
    <?php else: ?>
        <p style="color: #666;">Primero guarda los datos para activar la vinculación.</p>
        <a href="#" class="btn-vincular btn-disabled">Vincular con Mercado Libre</a>
    <?php endif; ?>
</div>

</body>
</html>