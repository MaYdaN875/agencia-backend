<?php
include '../config/database.php';

// Obtener parámetros
$reservation_id = $_GET['reservation_id'] ?? '';
$package_type = $_GET['package_type'] ?? '';

// Validar que tengamos la reserva
if (!$reservation_id) {
    header('Location: search.php');
    exit;
}

// Obtener información de la reserva existente
$reservation = $conn->query("SELECT * FROM reservations WHERE id = $reservation_id")->fetch_assoc();
$reservation_details = $conn->query("SELECT * FROM reservation_details WHERE reservation_id = $reservation_id AND service_type != 'hotel'")->fetch_assoc();

if (!$reservation || !$reservation_details) {
    die("Reserva no encontrada");
}

// Obtener información del transporte
if ($reservation_details['service_type'] == 'flight') {
    $transport = $conn->query("SELECT * FROM flights WHERE id = {$reservation_details['service_id']}")->fetch_assoc();
    $destination = $transport['destination'];
    $departure_date = $transport['departure_date'];
} else {
    $transport = $conn->query("SELECT * FROM buses WHERE id = {$reservation_details['service_id']}")->fetch_assoc();
    $destination = $transport['destination'];
    $departure_date = $transport['departure_date'];
}

// Obtener fecha de regreso si existe
$return_date = $_GET['return_date'] ?? '';
$one_way = isset($_GET['one_way']) ? $_GET['one_way'] : '0';

$check_in = $departure_date;
$check_out = ($one_way == '1') ? date('Y-m-d', strtotime($departure_date . ' + 1 day')) : $return_date;

// Validar fechas
if (empty($check_out)) {
    $check_out = date('Y-m-d', strtotime($check_in . ' + 1 day'));
}

// Obtener número de pasajeros
$passengers_data = json_decode($reservation_details['passengers_data'], true);
$total_passengers = count($passengers_data);

// Obtener hoteles disponibles - CORREGIDO EL ERROR DE BIND_PARAM
$sql = "SELECT h.*, hr.room_type, hr.capacity, hr.price_per_night, hr.id as room_id, hr.amenities
        FROM hotels h 
        JOIN hotel_rooms hr ON h.id = hr.hotel_id 
        WHERE h.location LIKE ? AND hr.capacity >= ?
        AND hr.id IN (
            SELECT hotel_room_id 
            FROM hotel_availability 
            WHERE date BETWEEN ? AND ? 
            AND available_rooms > 0
            GROUP BY hotel_room_id 
            HAVING COUNT(*) = DATEDIFF(?, ?)
        )";
        
$stmt = $conn->prepare($sql);
$destination_like = "%$destination%";

// LÍNEA 65 MODIFICADA
$stmt->bind_param("sissss", $destination_like, $total_passengers, $check_in, $check_out, $check_out, $check_in);

$stmt->execute();
$hotels_result = $stmt->get_result();
$hotels = $hotels_result->fetch_all(MYSQLI_ASSOC);

// Calcular número de noches
$nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selección de Hotel - Agencia de Viajes</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">Viajes<span>Express</span></div>
                <nav>
                    <ul>
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="search.php">Buscar Viajes</a></li>
                        <li><a href="../admin/login.php">Admin</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <section class="results">
        <div class="container">
            <h2>Selecciona tu Hotel</h2>
            <div class="search-info" style="background: #f8f9fa; padding: 1rem; border-radius: 5px; margin-bottom: 1.5rem;">
                <p>
                    <strong>Destino:</strong> <?php echo htmlspecialchars($destination); ?> | 
                    <strong>Check-in:</strong> <?php echo date('d/m/Y', strtotime($check_in)); ?> | 
                    <strong>Check-out:</strong> <?php echo date('d/m/Y', strtotime($check_out)); ?> |
                    <strong>Noches:</strong> <?php echo $nights; ?> |
                    <strong>Huéspedes:</strong> <?php echo $total_passengers; ?>
                </p>
            </div>

            <?php if (!empty($hotels)): ?>
                <div class="hotels-grid">
                    <?php foreach ($hotels as $hotel): 
                        $total_price = $hotel['price_per_night'] * $nights;
                    ?>
                        <div class="hotel-card">
                            <div class="hotel-image">
                                <div style="background: #f0f0f0; height: 200px; display: flex; align-items: center; justify-content: center; color: #666; border-radius: 8px 8px 0 0;">
                                    <span>🏨 Imagen del Hotel</span>
                                </div>
                            </div>
                            <div class="hotel-info">
                                <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
                                <div class="hotel-stars">
                                    <?php echo str_repeat('★', $hotel['stars']); ?>
                                    <span style="color: #666; margin-left: 0.5rem;"><?php echo $hotel['stars']; ?> estrellas</span>
                                </div>
                                <p class="hotel-location">📍 <?php echo htmlspecialchars($hotel['location']); ?></p>
                                <p class="hotel-address"><?php echo htmlspecialchars($hotel['address']); ?></p>
                                
                                <div class="room-details">
                                    <h4><?php echo htmlspecialchars($hotel['room_type']); ?></h4>
                                    <p><strong>Capacidad:</strong> <?php echo $hotel['capacity']; ?> personas</p>
                                    <?php if ($hotel['amenities']): ?>
                                        <p><strong>Servicios:</strong> <?php echo htmlspecialchars($hotel['amenities']); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="hotel-price">
                                    <div class="price-per-night">$<?php echo number_format($hotel['price_per_night'], 2); ?> por noche</div>
                                    <div class="total-price">Total: $<?php echo number_format($total_price, 2); ?> por <?php echo $nights; ?> noche(s)</div>
                                </div>
                                
                                <a href="hotel_reservation.php?reservation_id=<?php echo $reservation_id; ?>&hotel_id=<?php echo $hotel['id']; ?>&room_id=<?php echo $hotel['room_id']; ?>&check_in=<?php echo $check_in; ?>&check_out=<?php echo $check_out; ?>" 
                                   class="btn btn-accent">
                                    Seleccionar Habitación
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <h3>No se encontraron hoteles disponibles</h3>
                    <p>No hay hoteles disponibles para las fechas seleccionadas en <?php echo htmlspecialchars($destination); ?>.</p>
                    <p>Intenta con otras fechas o comunícate con nuestro servicio al cliente.</p>
                    <a href="search.php" class="btn btn-primary">Buscar Nuevamente</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2023 Agencia de Viajes. Todos los derechos reservados.</p>
        </div>
    </footer>

    <style>
        .hotels-grid {
            display: grid;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .hotel-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .hotel-info {
            padding: 1.5rem;
        }
        
        .hotel-stars {
            color: #ffc107;
            font-size: 1.2rem;
            margin: 0.5rem 0;
        }
        
        .hotel-location {
            color: #666;
            margin: 0.5rem 0;
        }
        
        .hotel-address {
            color: #888;
            font-size: 0.9rem;
            margin: 0.5rem 0;
        }
        
        .room-details {
            margin: 1rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .hotel-price {
            margin: 1rem 0;
            text-align: center;
        }
        
        .price-per-night {
            font-size: 1.1rem;
            color: #333;
        }
        
        .total-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: #2c3e50;
            margin-top: 0.5rem;
        }
        
        .no-results {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .search-info {
            border-left: 4px solid var(--secondary);
        }
    </style>
</body>
</html>