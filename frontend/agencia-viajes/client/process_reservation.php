<?php
include '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recoger datos del formulario
    $service_type = $_POST['service_type'];
    $service_id = $_POST['service_id'];
    $package_type = $_POST['package_type'];
    $customer_name = $_POST['customer_name'];
    $customer_email = $_POST['customer_email'];
    $customer_phone = $_POST['customer_phone'];
    $passengers = $_POST['passengers'] ?? [];
    $action = $_POST['action'];
    
    // LÍNEAS AÑADIDAS
    $one_way = $_POST['one_way'] ?? '0';
    $return_date = $_POST['return_date'] ?? '';
    
    // Calcular precio total del transporte
    $transport_price = 0;
    
    if ($service_type == 'flight') {
        $transport = $conn->query("SELECT * FROM flights WHERE id = $service_id")->fetch_assoc();
        $transport_price = $transport['price'] * count($passengers);
    } elseif ($service_type == 'bus') {
        $transport = $conn->query("SELECT * FROM buses WHERE id = $service_id")->fetch_assoc();
        $transport_price = $transport['price'] * count($passengers);
    }
    
    // Generar código de reserva único
    $reservation_code = 'RES' . strtoupper(substr(uniqid(), -8));
    
    // Insertar reserva principal
    $sql = "INSERT INTO reservations (reservation_code, customer_name, customer_email, customer_phone, total_price, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssd", $reservation_code, $customer_name, $customer_email, $customer_phone, $transport_price);
    
    if ($stmt->execute()) {
        $reservation_id = $stmt->insert_id;
        
        // Insertar detalles de la reserva del transporte
        $passengers_json = json_encode($passengers);
        $sql_detail = "INSERT INTO reservation_details (reservation_id, service_type, service_id, passengers_data, subtotal) 
                       VALUES (?, ?, ?, ?, ?)";
        $stmt_detail = $conn->prepare($sql_detail);
        $stmt_detail->bind_param("isisd", $reservation_id, $service_type, $service_id, $passengers_json, $transport_price);
        $stmt_detail->execute();
        
        // Actualizar disponibilidad del transporte
        if ($service_type == 'flight') {
            $conn->query("UPDATE flights SET available_seats = available_seats - " . count($passengers) . " WHERE id = $service_id");
        } elseif ($service_type == 'bus') {
            $conn->query("UPDATE buses SET available_seats = available_seats - " . count($passengers) . " WHERE id = $service_id");
        }
        
        // Redirigir según la acción
        
        // BLOQUE MODIFICADO
        if ($action == 'continue_to_hotel') {
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