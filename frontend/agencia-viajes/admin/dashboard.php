<?php
session_start();
include '../config/database.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Obtener estadísticas
$stats = [
    'total_flights' => $conn->query("SELECT COUNT(*) as count FROM flights")->fetch_assoc()['count'],
    'total_buses' => $conn->query("SELECT COUNT(*) as count FROM buses")->fetch_assoc()['count'],
    'total_hotels' => $conn->query("SELECT COUNT(*) as count FROM hotels")->fetch_assoc()['count'],
    'total_reservations' => $conn->query("SELECT COUNT(*) as count FROM reservations")->fetch_assoc()['count'],
    'pending_reservations' => $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'pending'")->fetch_assoc()['count']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin</title>
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
                <a href="dashboard.php" class="nav-item active">Dashboard</a>
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
                <h1>Dashboard</h1>
                <div class="header-actions">
                    <span><?php echo date('d/m/Y H:i'); ?></span>
                </div>
            </header>

            <div class="content">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon flight">✈️</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_flights']; ?></h3>
                            <p>Vuelos</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bus">🚌</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_buses']; ?></h3>
                            <p>Autobuses</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon hotel">🏨</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_hotels']; ?></h3>
                            <p>Hoteles</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon reservation">📋</div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_reservations']; ?></h3>
                            <p>Reservaciones</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h2>Acciones Rápidas</h2>
                    <div class="actions-grid">
                        <a href="flights.php" class="action-card">
                            <div class="action-icon">✈️</div>
                            <h3>Gestionar Vuelos</h3>
                            <p>Agregar, editar o eliminar vuelos</p>
                        </a>
                        
                        <a href="buses.php" class="action-card">
                            <div class="action-icon">🚌</div>
                            <h3>Gestionar Autobuses</h3>
                            <p>Administrar servicios de autobús</p>
                        </a>
                        
                        <a href="hotels.php" class="action-card">
                            <div class="action-icon">🏨</div>
                            <h3>Gestionar Hoteles</h3>
                            <p>Configurar hoteles y habitaciones</p>
                        </a>
                        
                        <a href="reservations.php" class="action-card">
                            <div class="action-icon">📋</div>
                            <h3>Ver Reservaciones</h3>
                            <p>Revisar y gestionar reservas</p>
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="recent-activity">
                    <h2>Actividad Reciente</h2>
                    <div class="activity-list">
                        <?php
                        $recent_reservations = $conn->query("
                            SELECT r.*, COUNT(rd.id) as services_count 
                            FROM reservations r 
                            LEFT JOIN reservation_details rd ON r.id = rd.reservation_id 
                            GROUP BY r.id 
                            ORDER BY r.created_at DESC 
                            LIMIT 5
                        ");
                        
                        while ($reservation = $recent_reservations->fetch_assoc()):
                        ?>
                            <div class="activity-item">
                                <div class="activity-icon">📝</div>
                                <div class="activity-content">
                                    <p><strong>Reserva #<?php echo $reservation['reservation_code']; ?></strong></p>
                                    <p><?php echo $reservation['customer_name']; ?> - $<?php echo number_format($reservation['total_price'], 2); ?></p>
                                    <small><?php echo date('d/m/Y H:i', strtotime($reservation['created_at'])); ?></small>
                                </div>
                                <div class="activity-status <?php echo $reservation['status']; ?>">
                                    <?php echo ucfirst($reservation['status']); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/admin.js"></script>
</body>
</html>