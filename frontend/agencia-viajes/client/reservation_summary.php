<?php
include '../config/database.php';

// Obtener parámetros
$reservation_id = $_GET['reservation_id'] ?? '';

// Obtener información de la reserva
$reservation = $conn->query("SELECT * FROM reservations WHERE id = $reservation_id")->fetch_assoc();
$reservation_details = $conn->query("SELECT * FROM reservation_details WHERE reservation_id = $reservation_id")->fetch_all(MYSQLI_ASSOC);

if (!$reservation) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen de Reserva - Agencia de Viajes</title>
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

    <section class="reservation-summary-page">
        <div class="container">
            <div class="success-message">
                <h1>¡Reserva Confirmada!</h1>
                <p>Tu reserva ha sido procesada exitosamente. Aquí tienes el resumen:</p>
            </div>

            <div class="reservation-details">
                <div class="detail-card">
                    <h2>Información de la Reserva</h2>
                    <div class="detail-item">
                        <strong>Código de Reserva:</strong>
                        <span><?php echo htmlspecialchars($reservation['reservation_code']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Estado:</strong>
                        <span class="status-confirmed">Confirmada</span>
                    </div>
                    <div class="detail-item">
                        <strong>Fecha de Reserva:</strong>
                        <span><?php echo date('d/m/Y H:i', strtotime($reservation['created_at'])); ?></span>
                    </div>
                </div>

                <div class="detail-card">
                    <h2>Información del Contacto</h2>
                    <div class="detail-item">
                        <strong>Nombre:</strong>
                        <span><?php echo htmlspecialchars($reservation['customer_name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Email:</strong>
                        <span><?php echo htmlspecialchars($reservation['customer_email']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>Teléfono:</strong>
                        <span><?php echo htmlspecialchars($reservation['customer_phone']); ?></span>
                    </div>
                </div>

                <?php foreach ($reservation_details as $detail): ?>
                    <div class="detail-card">
                        <?php if ($detail['service_type'] == 'flight'): ?>
                            <h2>Información del Vuelo</h2>
                            <?php
                            $flight = $conn->query("SELECT * FROM flights WHERE id = {$detail['service_id']}")->fetch_assoc();
                            $passengers = json_decode($detail['passengers_data'], true);
                            ?>
                            <div class="detail-item">
                                <strong>Aerolínea:</strong>
                                <span><?php echo htmlspecialchars($flight['airline']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Ruta:</strong>
                                <span><?php echo htmlspecialchars($flight['origin']); ?> → <?php echo htmlspecialchars($flight['destination']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Fecha:</strong>
                                <span><?php echo date('d/m/Y', strtotime($flight['departure_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Hora:</strong>
                                <span><?php echo $flight['departure_time']; ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Pasajeros:</strong>
                                <span><?php echo count($passengers); ?></span>
                            </div>
                            <div class="detail-item price">
                                <strong>Subtotal:</strong>
                                <span>$<?php echo number_format($detail['subtotal'], 2); ?></span>
                            </div>

                        <?php elseif ($detail['service_type'] == 'bus'): ?>
                            <h2>Información del Autobús</h2>
                            <?php
                            $bus = $conn->query("SELECT * FROM buses WHERE id = {$detail['service_id']}")->fetch_assoc();
                            $passengers = json_decode($detail['passengers_data'], true);
                            ?>
                            <div class="detail-item">
                                <strong>Empresa:</strong>
                                <span><?php echo htmlspecialchars($bus['company']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Ruta:</strong>
                                <span><?php echo htmlspecialchars($bus['origin']); ?> → <?php echo htmlspecialchars($bus['destination']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Fecha:</strong>
                                <span><?php echo date('d/m/Y', strtotime($bus['departure_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Hora:</strong>
                                <span><?php echo $bus['departure_time']; ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Pasajeros:</strong>
                                <span><?php echo count($passengers); ?></span>
                            </div>
                            <div class="detail-item price">
                                <strong>Subtotal:</strong>
                                <span>$<?php echo number_format($detail['subtotal'], 2); ?></span>
                            </div>

                        <?php elseif ($detail['service_type'] == 'hotel'): ?>
                            <h2>Información del Hotel</h2>
                            <?php
                            $hotel = $conn->query("SELECT * FROM hotels WHERE id = {$detail['service_id']}")->fetch_assoc();
                            ?>
                            <div class="detail-item">
                                <strong>Hotel:</strong>
                                <span><?php echo htmlspecialchars($hotel['name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Ubicación:</strong>
                                <span><?php echo htmlspecialchars($hotel['location']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Habitación:</strong>
                                <span><?php echo htmlspecialchars($detail['room_type']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Check-in:</strong>
                                <span><?php echo date('d/m/Y', strtotime($detail['check_in'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Check-out:</strong>
                                <span><?php echo date('d/m/Y', strtotime($detail['check_out'])); ?></span>
                            </div>
                            <div class="detail-item price">
                                <strong>Subtotal:</strong>
                                <span>$<?php echo number_format($detail['subtotal'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="detail-card total-card">
                    <h2>Resumen de Pagos</h2>
                    <?php foreach ($reservation_details as $detail): ?>
                        <div class="detail-item">
                            <span>
                                <?php 
                                if ($detail['service_type'] == 'flight') echo 'Vuelo';
                                elseif ($detail['service_type'] == 'bus') echo 'Autobús';
                                elseif ($detail['service_type'] == 'hotel') echo 'Hotel';
                                ?>:
                            </span>
                            <span>$<?php echo number_format($detail['subtotal'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="detail-item total">
                        <strong>Total Pagado:</strong>
                        <strong>$<?php echo number_format($reservation['total_price'], 2); ?></strong>
                    </div>
                </div>
            </div>

            <div class="reservation-actions">
                <a href="index.php" class="btn btn-primary">Volver al Inicio</a>
                <button onclick="window.print()" class="btn btn-secondary">Imprimir Comprobante</button>
            </div>

            <div class="important-info">
                <h3>Información Importante</h3>
                <ul>
                    <li>Presenta tu código de reserva al check-in</li>
                    <li>Llega al aeropuerto/terminal con al menos 2 horas de anticipación</li>
                    <li>El check-in en el hotel es a partir de las 15:00 hrs</li>
                    <li>Para cancelaciones o modificaciones, contacta a nuestro servicio al cliente</li>
                </ul>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2023 Agencia de Viajes. Todos los derechos reservados.</p>
        </div>
    </footer>

    <style>
        .reservation-summary-page {
            padding: 2rem 0;
        }
        
        .success-message {
            text-align: center;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .success-message h1 {
            color: var(--success);
            margin-bottom: 1rem;
        }
        
        .reservation-details {
            display: grid;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .detail-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .detail-card h2 {
            color: var(--primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light);
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-item.price {
            font-weight: bold;
            color: var(--primary);
        }
        
        .detail-item.total {
            font-size: 1.2rem;
            border-top: 2px solid var(--primary);
            margin-top: 0.5rem;
            padding-top: 1rem;
        }
        
        .status-confirmed {
            color: var(--success);
            font-weight: bold;
        }
        
        .total-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .total-card h2 {
            color: white;
            border-bottom-color: rgba(255,255,255,0.3);
        }
        
        .total-card .detail-item {
            border-bottom-color: rgba(255,255,255,0.2);
        }
        
        .reservation-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .important-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 1.5rem;
        }
        
        .important-info h3 {
            color: #856404;
            margin-bottom: 1rem;
        }
        
        .important-info ul {
            color: #856404;
            padding-left: 1.5rem;
        }
        
        .important-info li {
            margin-bottom: 0.5rem;
        }
        
        @media print {
            header, footer, .reservation-actions, .important-info {
                display: none;
            }
            
            .reservation-summary-page {
                padding: 0;
            }
        }
    </style>
</body>
</html>