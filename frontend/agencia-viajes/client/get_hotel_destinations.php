<?php
include '../config/database.php';

header('Content-Type: application/json');

// Obtener todas las ubicaciones de hoteles únicas
$result = $conn->query("SELECT DISTINCT location FROM hotels ORDER BY location");

$destinations = [];
while ($row = $result->fetch_assoc()) {
    $destinations[] = $row['location'];
}

echo json_encode($destinations);
?>