<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Manejar operaciones CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_flight'])) {
        $airline = $_POST['airline'];
        $origin = $_POST['origin'];
        $destination = $_POST['destination'];
        $departure_date = $_POST['departure_date'];
        $departure_time = $_POST['departure_time'];
        $duration_minutes = $_POST['duration_minutes'];
        $price = $_POST['price'];
        $capacity = $_POST['capacity'];
        $available_seats = $_POST['available_seats'];
        
        $sql = "INSERT INTO flights (airline, origin, destination, departure_date, departure_time, duration_minutes, price, capacity, available_seats) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssidii", $airline, $origin, $destination, $departure_date, $departure_time, $duration_minutes, $price, $capacity, $available_seats);
        
        if ($stmt->execute()) {
            $success = "Vuelo agregado correctamente";
        } else {
            $error = "Error al agregar el vuelo: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_flight'])) {
        $flight_id = $_POST['flight_id'];
        $sql = "DELETE FROM flights WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $flight_id);
        
        if ($stmt->execute()) {
            $success = "Vuelo eliminado correctamente";
        } else {
            $error = "Error al eliminar el vuelo: " . $conn->error;
        }
    }
}

// Obtener todos los vuelos
$flights = $conn->query("SELECT * FROM flights ORDER BY departure_date, departure_time");

// *** ¡MODIFICADO! Obtener lugares únicos de la tabla global ***
$locations_list = $conn->query("SELECT name FROM locations ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Vuelos - Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <p>Bienvenido, <?php echo $_SESSION['admin_name']; ?></p>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">Dashboard</a>
                <a href="agencies.php" class="nav-item">Agencias</a>
                <a href="flights.php" class="nav-item active">Vuelos</a>
                <a href="buses.php" class="nav-item">Autobuses</a>
                <a href="hotels.php" class="nav-item">Hoteles</a>
                <a href="locations.php" class="nav-item">Ubicaciones</a>
                <a href="reservations.php" class="nav-item">Reservaciones</a>
                <a href="login.php?logout=true" class="nav-item logout">Cerrar Sesión</a>
            </nav>
        </div>

        <div class="main-content">
            <header class="content-header">
                <h1>Gestión de Vuelos</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="toggleForm()">Agregar Vuelo</button>
                </div>
            </header>

            <div class="content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="form-container" id="flight-form" style="display: none; margin-bottom: 2rem;">
                    <h2>Agregar Nuevo Vuelo</h2>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="airline">Aerolínea</label>
                                <input type="text" id="airline" name="airline" required>
                            </div>
                            <div class="form-group">
                                <label for="price">Precio</label>
                                <input type="number" id="price" name="price" step="0.01" min="0" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="origin">Origen</label>
                                <select id="origin" name="origin" required>
                                    <option value="">Seleccionar origen</option>
                                    <?php mysqli_data_seek($locations_list, 0); // Reiniciar puntero de la lista ?>
                                    <?php while ($location = $locations_list->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($location['name']); ?>">
                                            <?php echo htmlspecialchars($location['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="destination">Destino</label>
                                <select id="destination" name="destination" required>
                                    <option value="">Seleccionar destino</option>
                                    <?php mysqli_data_seek($locations_list, 0); // Reiniciar puntero de la lista ?>
                                    <?php while ($location = $locations_list->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($location['name']); ?>">
                                            <?php echo htmlspecialchars($location['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="departure_date">Fecha de Salida</label>
                                <input type="date" id="departure_date" name="departure_date" required>
                            </div>
                            <div class="form-group">
                                <label for="departure_time">Hora de Salida</label>
                                <input type="time" id="departure_time" name="departure_time" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="duration_minutes">Duración (minutos)</label>
                                <input type="number" id="duration_minutes" name="duration_minutes" min="1" required>
                            </div>
                            <div class="form-group">
                                <label for="capacity">Capacidad Total</label>
                                <input type="number" id="capacity" name="capacity" min="1" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="available_seats">Asientos Disponibles</label>
                            <input type="number" id="available_seats" name="available_seats" min="0" required>
                        </div>
                        
                        <button type="submit" name="add_flight" class="btn btn-primary">Agregar Vuelo</button>
                        <button type="button" class="btn" onclick="toggleForm()">Cancelar</button>
                    </form>
                </div>

                <div class="data-table">
                    <div class="table-header">
                        <h2>Vuelos Registrados</h2>
                        <div class="table-actions">
                            <input type="text" id="search-flights" placeholder="Buscar vuelos..." class="table-filter" data-table="flights-table">
                        </div>
                    </div>
                    
                    <table id="flights-table">
                        <thead>
                            <tr>
                                <th>Aerolínea</th>
                                <th>Ruta</th>
                                <th>Fecha y Hora</th>
                                <th>Duración</th>
                                <th>Precio</th>
                                <th>Disponibilidad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($flight = $flights->fetch_assoc()): 
                                $arrival_time = date('H:i', strtotime($flight['departure_time']) + $flight['duration_minutes'] * 60);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($flight['airline']); ?></td>
                                <td><?php echo htmlspecialchars($flight['origin']); ?> → <?php echo htmlspecialchars($flight['destination']); ?></td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($flight['departure_date'])); ?><br>
                                    <small><?php echo $flight['departure_time']; ?> - <?php echo $arrival_time; ?></small>
                                </td>
                                <td><?php echo floor($flight['duration_minutes'] / 60); ?>h <?php echo $flight['duration_minutes'] % 60; ?>m</td>
                                <td>$<?php echo number_format($flight['price'], 2); ?></td>
                                <td>
                                    <?php echo $flight['available_seats']; ?> / <?php echo $flight['capacity']; ?>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo ($flight['available_seats'] / $flight['capacity']) * 100; ?>%"></div>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="flight_id" value="<?php echo $flight['id']; ?>">
                                        <button type="submit" name="delete_flight" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de eliminar este vuelo?')">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleForm() {
            const form = document.getElementById('flight-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
        
        // Configurar fechas mínimas
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('departure_date').min = today;
    </script>
</body>
</html>