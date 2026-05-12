<?php
// config/init.php

$host = "localhost";
$user = "root";    // Asegúrate de que este sea el usuario que creaste en phpMyAdmin
$pass = "";    // La contraseña que pusiste en phpMyAdmin
$db   = "auditoria_db";

// Crear la conexión
$conn = new mysqli($host, $user, $pass);

// Verificar si hay errores de entrada
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

// Crear la base de datos si no existe
$conn->query("CREATE DATABASE IF NOT EXISTS $db");

// Seleccionar la base de datos
$conn->select_db($db);
$conn->set_charset("utf8mb4");
?>
