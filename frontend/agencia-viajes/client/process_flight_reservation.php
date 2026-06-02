<?php
include '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recoger datos del formulario
    $flight_id = $_POST['flight_id'];
    $return_flight_id = $_POST['return_flight_id'] ?? '';
    $package_type = $_POST['package_type'];
    $customer_name = $_POST['customer_name'];
    $customer_email = $_POST['customer_email'];
    $customer_phone = $_POST['customer_phone'];
    $passengers = $_POST['passengers'] ?? [];
    $action = $_POST['action'];
    $one_way = $_POST['one_way'] ?? '0';
    
    // LÍNEA AÑADIDA
    $return_date = $_POST['return_date'] ?? '';
    
    // Validar datos requeridos
    if (empty($customer_name) || empty($customer_email) || empty($customer_phone) || empty($passengers)) {
        die("Error: Faltan datos requeridos");
    }
    
    // Calcular precio total
    $flight = $conn->query("SELECT * FROM flights WHERE id = $flight_id")->fetch_assoc();
    $total_price = $flight['price'] * count($passengers);
    
    if ($return_flight_id && $one_way == '0') {
        $return_flight = $conn->query("SELECT * FROM flights WHERE id = $return_flight_id")->fetch_assoc();
        $total_price += $return_flight['price'] * count($passengers);
    }
    
    // Generar código de reserva único
    $reservation_code = 'RES' . strtoupper(substr(uniqid(), -8));
    
    // Insertar reserva principal
    $sql = "INSERT INTO reservations (reservation_code, customer_name, customer_email, customer_phone, total_price, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssd", $reservation_code, $customer_name, $customer_email, $customer_phone, $total_price);
    
    if ($stmt->execute()) {
        $reservation_id = $stmt->insert_id;
        
        // Insertar detalles del vuelo de ida
        $passengers_json = json_encode($passengers);
        $flight_price = $flight['price'] * count($passengers);
        $sql_detail = "INSERT INTO reservation_details (reservation_id, service_type, service_id, passengers_data, subtotal) 
                       VALUES (?, 'flight', ?, ?, ?)";
        $stmt_detail = $conn->prepare($sql_detail);
        $stmt_detail->bind_param("iisd", $reservation_id, $flight_id, $passengers_json, $flight_price);
        $stmt_detail->execute();
        
        // Insertar detalles del vuelo de regreso si existe
        if ($return_flight_id && $one_way == '0') {
            $return_flight_price = $return_flight['price'] * count($passengers);
            $sql_return = "INSERT INTO reservation_details (reservation_id, service_type, service_id, passengers_data, subtotal) 
                           VALUES (?, 'flight', ?, ?, ?)";
            $stmt_return = $conn->prepare($sql_return);
            $stmt_return->bind_param("iisd", $reservation_id, $return_flight_id, $passengers_json, $return_flight_price);
            $stmt_return->execute();
            
            // Actualizar disponibilidad del vuelo de regreso
            $conn->query("UPDATE flights SET available_seats = available_seats - " . count($passengers) . " WHERE id = $return_flight_id");
        }
        
        // Actualizar disponibilidad del vuelo de ida
        $conn->query("UPDATE flights SET available_seats = available_seats - " . count($passengers) . " WHERE id = $flight_id");
        
        // Redirigir según la acción
        
        // BLOQUE MODIFICADO
        if ($action == 'continue_to_hotel' && in_array($package_type, ['flight_hotel'])) {
            // Pasamos los parámetros de fecha a la página de hoteles
            $query_params = http_build_query([
                'reservation_id' => $reservation_id,
                'package_type' => $package_type,
                'return_date' => $return_date,
                'one_way' => $one_way
            ]);
            header("Location: hotels.php?" . $query_params);
            exit;
        } else {
            header("Location: reservation_summary.php?reservation_id=$reservation_id");
            exit;
        }
        // FIN DE BLOQUE MODIFICADO
        
    } else {
        echo "Error al crear la reserva: " . $conn->error;
    }
} else {
    header("Location: index.php");
    exit;
}
?>