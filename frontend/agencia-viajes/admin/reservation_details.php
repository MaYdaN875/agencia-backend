<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Obtener ID de la reserva
$reservation_id = $_GET['id'] ?? '';

if (!$reservation_id) {
    header('Location: reservations.php');
    exit;
}

// Obtener información de la reserva
$reservation = $conn->query("SELECT * FROM reservations WHERE id = $reservation_id")->fetch_assoc();
$reservation_details = $conn->query("SELECT * FROM reservation_details WHERE reservation_id = $reservation_id")->fetch_all(MYSQLI_ASSOC);

if (!$reservation) {
    header('Location: reservations.php');
    exit;
}

// Cambiar estado de reserva
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';
    
    $sql = "UPDATE reservations SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $reservation_id);
    
    if ($stmt->execute()) {
        $success = "Estado de reservación actualizado correctamente";
        $reservation['status'] = $status;
    } else {
        $error = "Error al actualizar el estado: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Reservación - Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <p>Bienvenido, <?php echo $_SESSION['admin_name']; ?></p>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">Dashboard</a>
                <a href="agencies.php" class="nav-item">Agencias</a>
                <a href="flights.php" class="nav-item">Vuelos</a>
                <a href="buses.php" class="nav-item">Autobuses</a>
                <a href="hotels.php" class="nav-item">Hoteles</a>
                <a href="reservations.php" class="nav-item">Reservaciones</a>
                <a href="login.php?logout=true" class="nav-item logout">Cerrar Sesión</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <header class="content-header">
                <h1>Detalles de Reservación</h1>
                <div class="header-actions">
                    <a href="reservations.php" class="btn btn-secondary">Volver a Reservaciones</a>
                </div>
            </header>

            <div class="content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="reservation-details-admin">
                    <!-- Información Principal -->
                    <div class="detail-section">
                        <h2>Información Principal</h2>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <strong>Código de Reserva:</strong>
                                <span><?php echo htmlspecialchars($reservation['reservation_code']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Estado:</strong>
                                <span class="status-badge status-<?php echo $reservation['status']; ?>">
                                    <?php echo ucfirst($reservation['status']); ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <strong>Fecha de Reserva:</strong>
                                <span><?php echo date('d/m/Y H:i', strtotime($reservation['created_at'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Total:</strong>
                                <span class="price">$<?php echo number_format($reservation['total_price'], 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Información del Cliente -->
                    <div class="detail-section">
                        <h2>Información del Cliente</h2>
                        <div class="detail-grid">
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
                    </div>

                    <!-- Servicios Contratados -->
                    <div class="detail-section">
                        <h2>Servicios Contratados</h2>
                        <?php foreach ($reservation_details as $detail): ?>
                            <div class="service-detail">
                                <?php if ($detail['service_type'] == 'flight'): ?>
                                    <h3>✈️ Vuelo</h3>
                                    <?php
                                    $flight = $conn->query("SELECT * FROM flights WHERE id = {$detail['service_id']}")->fetch_assoc();
                                    $passengers = json_decode($detail['passengers_data'], true);
                                    ?>
                                    <div class="service-info">
                                        <p><strong>Aerolínea:</strong> <?php echo htmlspecialchars($flight['airline']); ?></p>
                                        <p><strong>Ruta:</strong> <?php echo htmlspecialchars($flight['origin']); ?> → <?php echo htmlspecialchars($flight['destination']); ?></p>
                                        <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($flight['departure_date'])); ?></p>
                                        <p><strong>Hora:</strong> <?php echo $flight['departure_time']; ?></p>
                                        <p><strong>Duración:</strong> <?php echo floor($flight['duration_minutes'] / 60); ?>h <?php echo $flight['duration_minutes'] % 60; ?>m</p>
                                        <p><strong>Pasajeros:</strong> <?php echo count($passengers); ?></p>
                                        <p><strong>Subtotal:</strong> $<?php echo number_format($detail['subtotal'], 2); ?></p>
                                    </div>

                                    <!-- Lista de Pasajeros -->
                                    <div class="passengers-list">
                                        <h4>Pasajeros</h4>
                                        <?php foreach ($passengers as $index => $passenger): ?>
                                            <div class="passenger-item">
                                                <strong>Pasajero <?php echo $index + 1; ?>:</strong>
                                                <?php echo htmlspecialchars($passenger['name']); ?> |
                                                <?php echo date('d/m/Y', strtotime($passenger['birthdate'])); ?> |
                                                <?php echo ucfirst($passenger['gender']); ?> |
                                                <?php echo htmlspecialchars($passenger['country']); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                <?php elseif ($detail['service_type'] == 'bus'): ?>
                                    <h3>🚌 Autobús</h3>
                                    <?php
                                    $bus = $conn->query("SELECT * FROM buses WHERE id = {$detail['service_id']}")->fetch_assoc();
                                    $passengers = json_decode($detail['passengers_data'], true);
                                    ?>
                                    <div class="service-info">
                                        <p><strong>Empresa:</strong> <?php echo htmlspecialchars($bus['company']); ?></p>
                                        <p><strong>Ruta:</strong> <?php echo htmlspecialchars($bus['origin']); ?> → <?php echo htmlspecialchars($bus['destination']); ?></p>
                                        <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($bus['departure_date'])); ?></p>
                                        <p><strong>Hora:</strong> <?php echo $bus['departure_time']; ?></p>
                                        <p><strong>Duración:</strong> <?php echo floor($bus['duration_minutes'] / 60); ?>h <?php echo $bus['duration_minutes'] % 60; ?>m</p>
                                        <p><strong>Pasajeros:</strong> <?php echo count($passengers); ?></p>
                                        <p><strong>Subtotal:</strong> $<?php echo number_format($detail['subtotal'], 2); ?></p>
                                    </div>

                                    <!-- Lista de Pasajeros -->
                                    <div class="passengers-list">
                                        <h4>Pasajeros</h4>
                                        <?php foreach ($passengers as $index => $passenger): ?>
                                            <div class="passenger-item">
                                                <strong>Pasajero <?php echo $index + 1; ?>:</strong>
                                                <?php echo htmlspecialchars($passenger['name']); ?> |
                                                <?php echo date('d/m/Y', strtotime($passenger['birthdate'])); ?> |
                                                <?php echo ucfirst($passenger['gender']); ?> |
                                                <?php echo htmlspecialchars($passenger['country']); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                <?php elseif ($detail['service_type'] == 'hotel'): ?>
                                    <h3>🏨 Hotel</h3>
                                    <?php
                                    $hotel = $conn->query("SELECT * FROM hotels WHERE id = {$detail['service_id']}")->fetch_assoc();
                                    ?>
                                    <div class="service-info">
                                        <p><strong>Hotel:</strong> <?php echo htmlspecialchars($hotel['name']); ?></p>
                                        <p><strong>Ubicación:</strong> <?php echo htmlspecialchars($hotel['location']); ?></p>
                                        <p><strong>Habitación:</strong> <?php echo htmlspecialchars($detail['room_type']); ?></p>
                                        <p><strong>Check-in:</strong> <?php echo date('d/m/Y', strtotime($detail['check_in'])); ?></p>
                                        <p><strong>Check-out:</strong> <?php echo date('d/m/Y', strtotime($detail['check_out'])); ?></p>
                                        <p><strong>Noches:</strong> <?php echo (strtotime($detail['check_out']) - strtotime($detail['check_in'])) / (60 * 60 * 24); ?></p>
                                        <p><strong>Subtotal:</strong> $<?php echo number_format($detail['subtotal'], 2); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Actualizar Estado -->
                    <div class="detail-section">
                        <h2>Gestión de Reservación</h2>
                        <form method="POST" class="status-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="status">Estado de la Reservación</label>
                                    <select id="status" name="status" class="status-select status-<?php echo $reservation['status']; ?>">
                                        <option value="pending" <?php echo $reservation['status'] == 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="confirmed" <?php echo $reservation['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmado</option>
                                        <option value="cancelled" <?php echo $reservation['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" name="update_status" class="btn btn-primary">Actualizar Estado</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .reservation-details-admin {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .detail-section {
            padding: 2rem;
            border-bottom: 1px solid #e1e8ed;
        }
        
        .detail-section:last-child {
            border-bottom: none;
        }
        
        .detail-section h2 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light);
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .price {
            font-weight: bold;
            color: var(--success);
            font-size: 1.1rem;
        }
        
        .service-detail {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .service-detail:last-child {
            margin-bottom: 0;
        }
        
        .service-detail h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .service-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .service-info p {
            margin: 0;
            padding: 0.25rem 0;
        }
        
        .passengers-list {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }
        
        .passengers-list h4 {
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .passenger-item {
            padding: 0.5rem;
            background: white;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            border-left: 3px solid var(--secondary);
        }
        
        .status-form {
            max-width: 400px;
        }
        
        .status-select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .status-select.status-pending {
            border-color: #ffc107;
            color: #856404;
        }
        
        .status-select.status-confirmed {
            border-color: #28a745;
            color: #155724;
        }
        
        .status-select.status-cancelled {
            border-color: #dc3545;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
            
            .detail-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .service-info {
                grid-template-columns: 1fr;
            }
            
            .detail-section {
                padding: 1.5rem;
            }
        }
    </style>
</body>
</html>