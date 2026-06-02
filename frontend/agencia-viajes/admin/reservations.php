<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Cambiar estado de reserva
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $reservation_id = $_POST['reservation_id'];
    $status = $_POST['status'];
    
    $sql = "UPDATE reservations SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $reservation_id);
    
    if ($stmt->execute()) {
        $success = "Estado de reservación actualizado correctamente";
    } else {
        $error = "Error al actualizar el estado: " . $conn->error;
    }
}

// Obtener todas las reservaciones con detalles
$reservations = $conn->query("
    SELECT r.*, 
           GROUP_CONCAT(DISTINCT rd.service_type) as services,
           COUNT(rd.id) as services_count
    FROM reservations r
    LEFT JOIN reservation_details rd ON r.id = rd.reservation_id
    GROUP BY r.id
    ORDER BY r.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reservaciones - Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
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
                <a href="reservations.php" class="nav-item active">Reservaciones</a>
                <a href="login.php?logout=true" class="nav-item logout">Cerrar Sesión</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <header class="content-header">
                <h1>Gestión de Reservaciones</h1>
            </header>

            <div class="content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Lista de reservaciones -->
                <div class="data-table">
                    <div class="table-header">
                        <h2>Reservaciones</h2>
                        <div class="table-actions">
                            <input type="text" id="search-reservations" placeholder="Buscar reservaciones..." class="table-filter" data-table="reservations-table">
                        </div>
                    </div>
                    
                    <table id="reservations-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Servicios</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($reservation = $reservations->fetch_assoc()): 
                                $status_class = '';
                                switch ($reservation['status']) {
                                    case 'confirmed': $status_class = 'status-confirmed'; break;
                                    case 'pending': $status_class = 'status-pending'; break;
                                    case 'cancelled': $status_class = 'status-cancelled'; break;
                                }
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($reservation['reservation_code']); ?></strong>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($reservation['customer_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($reservation['customer_email']); ?></small><br>
                                    <small><?php echo htmlspecialchars($reservation['customer_phone']); ?></small>
                                </td>
                                <td>
                                    <?php 
                                    $services = explode(',', $reservation['services']);
                                    foreach ($services as $service) {
                                        if (!empty($service)) {
                                            echo '<span class="service-tag">' . htmlspecialchars($service) . '</span> ';
                                        }
                                    }
                                    ?>
                                    <br><small><?php echo $reservation['services_count']; ?> servicio(s)</small>
                                </td>
                                <td><strong>$<?php echo number_format($reservation['total_price'], 2); ?></strong></td>
                                <td>
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                        <select name="status" class="status-select <?php echo $status_class; ?>" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $reservation['status'] == 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                                            <option value="confirmed" <?php echo $reservation['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmado</option>
                                            <option value="cancelled" <?php echo $reservation['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($reservation['created_at'])); ?></td>
                                <td>
                                    <a href="reservation_details.php?id=<?php echo $reservation['id']; ?>" class="btn btn-primary btn-sm">Ver Detalles</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        .service-tag {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-right: 4px;
            display: inline-block;
            margin-bottom: 2px;
        }
        
        .status-form {
            display: inline;
        }
        
        .status-select {
            padding: 6px 10px;
            border-radius: 6px;
            border: 2px solid #ddd;
            font-weight: bold;
            cursor: pointer;
        }
        
        .status-select.status-pending {
            border-color: #ff9800;
            color: #ff9800;
        }
        
        .status-select.status-confirmed {
            border-color: #4caf50;
            color: #4caf50;
        }
        
        .status-select.status-cancelled {
            border-color: #f44336;
            color: #f44336;
        }
    </style>
</body>
</html>