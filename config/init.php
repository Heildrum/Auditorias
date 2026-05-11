<?php
// config/init.php

$host = "localhost";
$user = "root";  // Usuario estándar de XAMPP
$pass = "";      // Contraseña vacía por defecto
$db   = "auditron_db";

// Crear la conexión
$conn = new mysqli($host, $user, $pass);

// Verificar si hay errores de entrada
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

// Crear la base de datos si no existe
$conn->query("CREATE DATABASE IF NOT EXISTS $db");

// Seleccionar la base de datos para los siguientes archivos
$conn->select_db($db);
$conn->set_charset("utf8mb4");