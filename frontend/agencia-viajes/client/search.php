<?php 
include '../config/database.php';

// Obtener parámetros de búsqueda
$package_type = $_GET['package_type'] ?? '';
$origin = $_GET['origin'] ?? '';
$destination = $_GET['destination'] ?? '';
$departure_date = $_GET['departure_date'] ?? '';
$return_date = $_GET['return_date'] ?? '';
$adults = $_GET['adults'] ?? 1;
$children = $_GET['children'] ?? 0;
$one_way = isset($_GET['one_way']) ? $_GET['one_way'] : '0';

$total_passengers = $adults + $children;

// Buscar servicios según el tipo de paquete
$flights = [];
$buses = [];
$hotels = [];

if (in_array($package_type, ['flight_only', 'flight_hotel'])) {
    $sql = "SELECT * FROM flights WHERE origin LIKE ? AND destination LIKE ? AND departure_date = ? AND available_seats >= ?";
    $stmt = $conn->prepare($sql);
    $origin_like = "%$origin%";
    $destination_like = "%$destination%";
    $stmt->bind_param("sssi", $origin_like, $destination_like, $departure_date, $total_passengers);
    $stmt->execute();
    $flights = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if (in_array($package_type, ['bus_only', 'bus_hotel'])) {
    $sql = "SELECT * FROM buses WHERE origin LIKE ? AND destination LIKE ? AND departure_date = ? AND available_seats >= ?";
    $stmt = $conn->prepare($sql);
    $origin_like = "%$origin%";
    $destination_like = "%$destination%";
    $stmt->bind_param("sssi", $origin_like, $destination_like, $departure_date, $total_passengers);
    $stmt->execute();
    $buses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if (in_array($package_type, ['hotel_only', 'flight_hotel', 'bus_hotel'])) {
    $sql = "SELECT h.*, hr.room_type, hr.capacity, hr.price_per_night, hr.id as room_id 
            FROM hotels h 
            JOIN hotel_rooms hr ON h.id = hr.hotel_id 
            WHERE h.location LIKE ? AND hr.capacity >= ?";
    $stmt = $conn->prepare($sql);
    $destination_like = "%$destination%";
    $stmt->bind_param("si", $destination_like, $total_passengers);
    $stmt->execute();
    $hotels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Búsqueda - Agencia de Viajes</title>
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
            <h2>Resultados de Búsqueda</h2>
            <p>
                Origen: <?php echo htmlspecialchars($origin); ?> | 
                Destino: <?php echo htmlspecialchars($destination); ?> | 
                Fecha: <?php echo htmlspecialchars($departure_date); ?>
                <?php if (!$one_way && $return_date): ?>
                    | Regreso: <?php echo htmlspecialchars($return_date); ?>
                <?php endif; ?>
            </p>

            <?php if (in_array($package_type, ['flight_only', 'flight_hotel']) && !empty($flights)): ?>
                <h3>Vuelos Disponibles</h3>
                <?php foreach ($flights as $flight): 
                    // Calcular hora de llegada basada en la duración
                    $departure_datetime = $flight['departure_date'] . ' ' . $flight['departure_time'];
                    $arrival_datetime = date('Y-m-d H:i:s', strtotime("+{$flight['duration_minutes']} minutes", strtotime($departure_datetime)));
                    $arrival_date = date('d/m/Y', strtotime($arrival_datetime));
                    $arrival_time = date('H:i', strtotime($arrival_datetime));
                ?>
                    <div class="result-card">
                        <div class="result-info">
                            <h3><?php echo htmlspecialchars($flight['airline']); ?></h3>
                            <p><?php echo htmlspecialchars($flight['origin']); ?> → <?php echo htmlspecialchars($flight['destination']); ?></p>
                            <p>Salida: <?php echo date('d/m/Y H:i', strtotime($departure_datetime)); ?></p>
                            <p>Llegada: <?php echo $arrival_date . ' ' . $arrival_time; ?></p>
                            <p>Duración: <?php echo floor($flight['duration_minutes'] / 60); ?>h <?php echo $flight['duration_minutes'] % 60; ?>m</p>
                            <p>Asientos disponibles: <?php echo $flight['available_seats']; ?></p>
                        </div>
                        <div class="result-price">
                            <div class="price">$<?php echo number_format($flight['price'], 2); ?></div>
                            <a href="flight_passengers.php?flight_id=<?php echo $flight['id']; ?>&package_type=<?php echo $package_type; ?>&adults=<?php echo $adults; ?>&children=<?php echo $children; ?>&return_date=<?php echo urlencode($return_date); ?>&one_way=<?php echo $one_way; ?>" class="btn">Seleccionar</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif (in_array($package_type, ['flight_only', 'flight_hotel'])): ?>
                <p>No se encontraron vuelos para los criterios de búsqueda.</p>
            <?php endif; ?>

            <?php if (in_array($package_type, ['bus_only', 'bus_hotel']) && !empty($buses)): ?>
                <h3>Autobuses Disponibles</h3>
                <?php foreach ($buses as $bus): 
                    // Calcular hora de llegada basada en la duración
                    $departure_datetime = $bus['departure_date'] . ' ' . $bus['departure_time'];
                    $arrival_datetime = date('Y-m-d H:i:s', strtotime("+{$bus['duration_minutes']} minutes", strtotime($departure_datetime)));
                    $arrival_date = date('d/m/Y', strtotime($arrival_datetime));
                    $arrival_time = date('H:i', strtotime($arrival_datetime));
                ?>
                    <div class="result-card">
                        <div class="result-info">
                            <h3><?php echo htmlspecialchars($bus['company']); ?></h3>
                            <p><?php echo htmlspecialchars($bus['origin']); ?> → <?php echo htmlspecialchars($bus['destination']); ?></p>
                            <p>Salida: <?php echo date('d/m/Y H:i', strtotime($departure_datetime)); ?></p>
                            <p>Llegada: <?php echo $arrival_date . ' ' . $arrival_time; ?></p>
                            <p>Duración: <?php echo floor($bus['duration_minutes'] / 60); ?>h <?php echo $bus['duration_minutes'] % 60; ?>m</p>
                            <p>Asientos disponibles: <?php echo $bus['available_seats']; ?></p>
                        </div>
                        <div class="result-price">
                            <div class="price">$<?php echo number_format($bus['price'], 2); ?></div>
                            <a href="bus_passengers.php?bus_id=<?php echo $bus['id']; ?>&package_type=<?php echo $package_type; ?>&adults=<?php echo $adults; ?>&children=<?php echo $children; ?>&return_date=<?php echo urlencode($return_date); ?>&one_way=<?php echo $one_way; ?>" class="btn">Seleccionar</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif (in_array($package_type, ['bus_only', 'bus_hotel'])): ?>
                <p>No se encontraron autobuses para los criterios de búsqueda.</p>
            <?php endif; ?>

            <?php if ($package_type == 'hotel_only' && !empty($hotels)): ?>
                <h3>Hoteles Disponibles</h3>
                <?php foreach ($hotels as $hotel): ?>
                    <div class="result-card">
                        <div class="result-info">
                            <h3><?php echo htmlspecialchars($hotel['name']); ?> - <?php echo htmlspecialchars($hotel['room_type']); ?></h3>
                            <p><?php echo htmlspecialchars($hotel['location']); ?></p>
                            <p>Capacidad: <?php echo $hotel['capacity']; ?> personas</p>
                            <p>Dirección: <?php echo htmlspecialchars($hotel['address']); ?></p>
                        </div>
                        <div class="result-price">
                            <div class="price">$<?php echo number_format($hotel['price_per_night'], 2); ?>/noche</div>
                            <a href="hotels.php?hotel_id=<?php echo $hotel['id']; ?>&room_id=<?php echo $hotel['room_id']; ?>&package_type=<?php echo $package_type; ?>&adults=<?php echo $adults; ?>&children=<?php echo $children; ?>&departure_date=<?php echo $departure_date; ?>&return_date=<?php echo urlencode($return_date); ?>" class="btn">Seleccionar</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif ($package_type == 'hotel_only'): ?>
                <p>No se encontraron hoteles para los criterios de búsqueda.</p>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2023 Agencia de Viajes. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>