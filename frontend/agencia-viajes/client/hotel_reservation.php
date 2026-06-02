<?php
include '../config/database.php';

// Obtener parámetros
$reservation_id = $_GET['reservation_id'] ?? '';
$hotel_id = $_GET['hotel_id'] ?? '';
$room_id = $_GET['room_id'] ?? '';
$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';

// Obtener información de la reserva existente
$reservation = $conn->query("SELECT * FROM reservations WHERE id = $reservation_id")->fetch_assoc();
$reservation_details = $conn->query("SELECT * FROM reservation_details WHERE reservation_id = $reservation_id")->fetch_assoc();

// Obtener información del hotel
$hotel_info = $conn->query("
    SELECT h.*, hr.room_type, hr.price_per_night, hr.amenities 
    FROM hotels h 
    JOIN hotel_rooms hr ON h.id = hr.hotel_id 
    WHERE h.id = $hotel_id AND hr.id = $room_id
")->fetch_assoc();

// Calcular estadía
$nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
$hotel_total = $hotel_info['price_per_night'] * $nights;

// Obtener información de pasajeros
$passengers = json_decode($reservation_details['passengers_data'], true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Hotel - Agencia de Viajes</title>
    <link rel="stylesheet" href="../css/style.css">
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

    <section class="reservation-section">
        <div class="container">
            <h2>Confirmación de Reserva de Hotel</h2>
            
            <div class="reservation-steps">
                <div class="step completed">1. Transporte</div>
                <div class="step completed">2. Pasajeros</div>
                <div class="step active">3. Hotel</div>
                <div class="step">4. Confirmación</div>
            </div>

            <div class="reservation-summary">
                <div class="summary-section">
                    <h3>Información del Hotel</h3>
                    <div class="hotel-summary">
                        <h4><?php echo htmlspecialchars($hotel_info['name']); ?></h4>
                        <p><strong>Ubicación:</strong> <?php echo htmlspecialchars($hotel_info['location']); ?></p>
                        <p><strong>Dirección:</strong> <?php echo htmlspecialchars($hotel_info['address']); ?></p>
                        <p><strong>Habitación:</strong> <?php echo htmlspecialchars($hotel_info['room_type']); ?></p>
                        <p><strong>Check-in:</strong> <?php echo date('d/m/Y', strtotime($check_in)); ?></p>
                        <p><strong>Check-out:</strong> <?php echo date('d/m/Y', strtotime($check_out)); ?></p>
                        <p><strong>Noches:</strong> <?php echo $nights; ?></p>
                        <?php if ($hotel_info['amenities']): ?>
                            <p><strong>Servicios incluidos:</strong> <?php echo htmlspecialchars($hotel_info['amenities']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="summary-section">
                    <h3>Información de los Pasajeros</h3>
                    <div class="passengers-summary">
                        <?php foreach ($passengers as $index => $passenger): ?>
                            <div class="passenger-item">
                                <h4>Pasajero <?php echo $index + 1; ?></h4>
                                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($passenger['name']); ?></p>
                                <p><strong>Fecha de Nacimiento:</strong> <?php echo date('d/m/Y', strtotime($passenger['birthdate'])); ?></p>
                                <p><strong>Género:</strong> <?php echo ucfirst($passenger['gender']); ?></p>
                                <p><strong>País de Nacimiento:</strong> <?php echo htmlspecialchars($passenger['country']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="summary-section">
                    <h3>Resumen de Precios</h3>
                    <div class="price-summary">
                        <div class="price-item">
                            <span>Transporte:</span>
                            <span>$<?php echo number_format($reservation_details['subtotal'], 2); ?></span>
                        </div>
                        <div class="price-item">
                            <span>Hotel (<?php echo $nights; ?> noches):</span>
                            <span>$<?php echo number_format($hotel_total, 2); ?></span>
                        </div>
                        <div class="price-total">
                            <span>Total:</span>
                            <span>$<?php echo number_format($reservation_details['subtotal'] + $hotel_total, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <form action="process_hotel_reservation.php" method="POST" class="reservation-form">
                <input type="hidden" name="reservation_id" value="<?php echo $reservation_id; ?>">
                <input type="hidden" name="hotel_id" value="<?php echo $hotel_id; ?>">
                <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                <input type="hidden" name="check_in" value="<?php echo $check_in; ?>">
                <input type="hidden" name="check_out" value="<?php echo $check_out; ?>">
                <input type="hidden" name="hotel_total" value="<?php echo $hotel_total; ?>">
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-accent btn-large">Confirmar Reserva Completa</button>
                    <a href="hotels.php?reservation_id=<?php echo $reservation_id; ?>" class="btn btn-secondary">Cambiar Hotel</a>
                </div>
            </form>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2023 Agencia de Viajes. Todos los derechos reservados.</p>
        </div>
    </footer>

    <style>
        .reservation-steps {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0;
            max-width: 600px;
        }
        
        .step {
            flex: 1;
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
            margin: 0 0.5rem;
            font-weight: bold;
        }
        
        .step.active {
            background: var(--secondary);
            color: white;
        }
        
        .step.completed {
            background: var(--success);
            color: white;
        }
        
        .reservation-summary {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .summary-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }
        
        .summary-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .hotel-summary, .passengers-summary {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .passenger-item {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #ddd;
        }
        
        .passenger-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .price-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .price-total {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            font-size: 1.2rem;
            font-weight: bold;
            border-top: 2px solid var(--primary);
            margin-top: 1rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .btn-large {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
    </style>
</body>
</html>