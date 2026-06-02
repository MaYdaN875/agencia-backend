<?php
include '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recoger datos del formulario
    $reservation_id = $_POST['reservation_id'];
    $hotel_id = $_POST['hotel_id'];
    $room_id = $_POST['room_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $hotel_total = $_POST['hotel_total'];
    
    // Obtener información de la reserva existente
    $reservation = $conn->query("SELECT * FROM reservations WHERE id = $reservation_id")->fetch_assoc();
    $reservation_details = $conn->query("SELECT * FROM reservation_details WHERE reservation_id = $reservation_id")->fetch_assoc();
    
    // Obtener información del hotel
    $hotel_info = $conn->query("
        SELECT h.name, hr.room_type 
        FROM hotels h 
        JOIN hotel_rooms hr ON h.id = hr.hotel_id 
        WHERE h.id = $hotel_id AND hr.id = $room_id
    ")->fetch_assoc();
    
    // Insertar detalles del hotel en la reserva
    $sql_hotel = "INSERT INTO reservation_details (reservation_id, service_type, service_id, room_type, check_in, check_out, subtotal) 
                  VALUES (?, 'hotel', ?, ?, ?, ?, ?)";
    $stmt_hotel = $conn->prepare($sql_hotel);
    $stmt_hotel->bind_param("iissd", $reservation_id, $hotel_id, $hotel_info['room_type'], $check_in, $check_out, $hotel_total);
    $stmt_hotel->execute();
    
    // Actualizar precio total de la reserva
    $new_total = $reservation['total_price'] + $hotel_total;
    $conn->query("UPDATE reservations SET total_price = $new_total WHERE id = $reservation_id");
    
    // Actualizar disponibilidad del hotel
    $nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
    $current_date = $check_in;
    for ($i = 0; $i < $nights; $i++) {
        $conn->query("
            UPDATE hotel_availability 
            SET available_rooms = available_rooms - 1 
            WHERE hotel_room_id = $room_id 
            AND date = '$current_date'
        ");
        $current_date = date('Y-m-d', strtotime($current_date . ' + 1 day'));
    }
    
    // Redirigir al resumen final
    header("Location: reservation_summary.php?reservation_id=$reservation_id");
    exit;
} else {
    header("Location: index.php");
    exit;
}
?>