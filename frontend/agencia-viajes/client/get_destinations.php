<?php
include '../config/database.php';

header('Content-Type: application/json');

if (isset($_GET['origin'])) {
    $origin = $conn->real_escape_string($_GET['origin']);
    $package_type = isset($_GET['package_type']) ? $conn->real_escape_string($_GET['package_type']) : '';
    
    $results = [];
    
    // Obtener destinos de vuelos para el origen seleccionado
    if (in_array($package_type, ['flight_only', 'flight_hotel'])) {
        $flights_destinations = $conn->query("
            SELECT DISTINCT destination 
            FROM flights 
            WHERE origin = '$origin' 
            AND departure_date >= CURDATE()
            AND available_seats > 0
            ORDER BY destination
        ");
        
        while ($row = $flights_destinations->fetch_assoc()) {
            $results[$row['destination']] = true;
        }
    }
    
    // Obtener destinos de autobuses para el origen seleccionado
    if (in_array($package_type, ['bus_only', 'bus_hotel'])) {
        $buses_destinations = $conn->query("
            SELECT DISTINCT destination 
            FROM buses 
            WHERE origin = '$origin' 
            AND departure_date >= CURDATE()
            AND available_seats > 0
            ORDER BY destination
        ");
        
        while ($row = $buses_destinations->fetch_assoc()) {
            $results[$row['destination']] = true;
        }
    }
    
    // Ordenar alfabéticamente
    ksort($results);
    
    echo json_encode(array_keys($results));
} else {
    echo json_encode([]);
}
?>