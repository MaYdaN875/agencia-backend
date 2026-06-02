<?php
$servername = "127.0.0.1";
$username = "root";
$password = "rootpassword";
$dbname = "mi_app";


// Crear conexión
$conn = new mysqli($servername, $username, $password);

// Verificar conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Crear base de datos si no existe
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    // Seleccionar la base de datos
    $conn->select_db($dbname);
} else {
    die("Error creating database: " . $conn->error);
}
?>