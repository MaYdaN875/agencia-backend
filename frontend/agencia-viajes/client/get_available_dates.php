<?php
include '../config/database.php';

header('Content-Type: application/json');

if (isset($_GET['origin']) && isset($_GET['destination']) && isset($_GET['package_type'])) {
    $origin = $conn->real_escape_string($_GET['origin']);
    $destination = $conn->real_escape_string($_GET['destination']);
    $package_type = $conn->real_escape_string($_GET['package_type']);
    
    $response = [
        'departure_dates' => [],
        'return_dates' => []
    ];
    
    // Obtener fechas disponibles de vuelos
    if (in_array($package_type, ['flight_only', 'flight_hotel'])) {
        $flight_dates = $conn->query("
            SELECT DISTINCT departure_date 
            FROM flights 
            WHERE origin = '$origin' 
            AND destination = '$destination'
            AND departure_date >= CURDATE()
            AND available_seats > 0
            ORDER BY departure_date
        ");
        
        while ($row = $flight_dates->fetch_assoc()) {
            $response['departure_dates'][] = $row['departure_date'];
        }
        
        // Obtener fechas de regreso disponibles
        $return_dates = $conn->query("
            SELECT DISTINCT departure_date 
            FROM flights 
            WHERE origin = '$destination' 
            AND destination = '$origin'
            AND departure_date >= CURDATE()
            AND available_seats > 0
            ORDER BY departure_date
        ");
        
        while ($row = $return_dates->fetch_assoc()) {
            $response['return_dates'][] = $row['departure_date'];
        }
    }
    
    // Obtener fechas disponibles de autobuses
    if (in_array($package_type, ['bus_only', 'bus_hotel'])) {
        $bus_dates = $conn->query("
            SELECT DISTINCT departure_date 
            FROM buses 
            WHERE origin = '$origin' 
            AND destination = '$destination'
            AND departure_date >= CURDATE()
            AND available_seats > 0
            ORDER BY departure_date
        ");
        
        while ($row = $bus_dates->fetch_assoc()) {
            if (!in_array($row['departure_date'], $response['departure_dates'])) {
                $response['departure_dates'][] = $row['departure_date'];
            }
        }
        
        // Obtener fechas de regreso disponibles para autobuses
        $return_bus_dates = $conn->query("
            SELECT DISTINCT departure_date 
            FROM buses 
            WHERE origin = '$destination' 
            AND destination = '$origin'
            AND departure_date >= CURDATE()
            AND available_seats > 0
            ORDER BY departure_date
        ");
        
        while ($row = $return_bus_dates->fetch_assoc()) {
            if (!in_array($row['departure_date'], $response['return_dates'])) {
                $response['return_dates'][] = $row['departure_date'];
            }
        }
    }
    
    // Formatear fechas para mejor visualización
    $response['departure_dates'] = array_map(function($date) {
        return date('Y-m-d', strtotime($date));
    }, $response['departure_dates']);
    
    $response['return_dates'] = array_map(function($date) {
        return date('Y-m-d', strtotime($date));
    }, $response['return_dates']);
    
    // Eliminar duplicados y ordenar
    $response['departure_dates'] = array_unique($response['departure_dates']);
    $response['return_dates'] = array_unique($response['return_dates']);
    sort($response['departure_dates']);
    sort($response['return_dates']);
    
    echo json_encode($response);
} else {
    echo json_encode(['departure_dates' => [], 'return_dates' => []]);
}
?>