<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Manejar operaciones CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_bus'])) {
        $company = $_POST['company'];
        $origin = $_POST['origin'];
        $destination = $_POST['destination'];
        $departure_date = $_POST['departure_date'];
        $departure_time = $_POST['departure_time'];
        $duration_minutes = $_POST['duration_minutes'];
        $price = $_POST['price'];
        $capacity = $_POST['capacity'];
        $available_seats = $_POST['available_seats'];
        
        $sql = "INSERT INTO buses (company, origin, destination, departure_date, departure_time, duration_minutes, price, capacity, available_seats) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssidii", $company, $origin, $destination, $departure_date, $departure_time, $duration_minutes, $price, $capacity, $available_seats);
        
        if ($stmt->execute()) {
            $success = "Autobús agregado correctamente";
        } else {
            $error = "Error al agregar el autobús: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_bus'])) {
        $bus_id = $_POST['bus_id'];
        $sql = "DELETE FROM buses WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $bus_id);
        
        if ($stmt->execute()) {
            $success = "Autobús eliminado correctamente";
        } else {
            $error = "Error al eliminar el autobús: " . $conn->error;
        }
    }
}

// Obtener todos los autobuses
$buses = $conn->query("SELECT * FROM buses ORDER BY departure_date, departure_time");

// *** ¡MODIFICADO! Obtener lugares únicos de la tabla global ***
$locations_list = $conn->query("SELECT name FROM locations ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Autobuses - Admin</title>
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
                <a href="flights.php" class="nav-item">Vuelos</a>
                <a href="buses.php" class="nav-item active">Autobuses</a>
                <a href="hotels.php" class="nav-item">Hoteles</a>
                <a href="locations.php" class="nav-item">Ubicaciones</a>
                <a href="reservations.php" class="nav-item">Reservaciones</a>
                <a href="login.php?logout=true" class="nav-item logout">Cerrar Sesión</a>
            </nav>
        </div>

        <div class="main-content">
            <header class="content-header">
                <h1>Gestión de Autobuses</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="toggleForm()">Agregar Autobús</button>
                </div>
            </header>

            <div class="content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="form-container" id="bus-form" style="display: none; margin-bottom: 2rem;">
                    <h2>Agregar Nuevo Autobús</h2>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="company">Empresa</label>
                                <input type="text" id="company" name="company" required>
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
                                    <?php mysqli_data_seek($locations_list, 0); // Reiniciar puntero ?>
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
                                    <?php mysqli_data_seek($locations_list, 0); // Reiniciar puntero ?>
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
                        
                        <button type="submit" name="add_bus" class="btn btn-primary">Agregar Autobús</button>
                        <button type="button" class="btn" onclick="toggleForm()">Cancelar</button>
                    </form>
                </div>

                <div class="data-table">
                    <div class="table-header">
                        <h2>Autobuses Registrados</h2>
                        <div class="table-actions">
                            <input type="text" id="search-buses" placeholder="Buscar autobuses..." class="table-filter" data-table="buses-table">
                        </div>
                    </div>
                    
                    <table id="buses-table">
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>Ruta</th>
                                <th>Fecha y Hora</th>
                                <th>Duración</th>
                                <th>Precio</th>
                                <th>Disponibilidad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($bus = $buses->fetch_assoc()): 
                                $arrival_time = date('H:i', strtotime($bus['departure_time']) + $bus['duration_minutes'] * 60);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bus['company']); ?></td>
                                <td><?php echo htmlspecialchars($bus['origin']); ?> → <?php echo htmlspecialchars($bus['destination']); ?></td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($bus['departure_date'])); ?><br>
                                    <small><?php echo $bus['departure_time']; ?> - <?php echo $arrival_time; ?></small>
                                </td>
                                <td><?php echo floor($bus['duration_minutes'] / 60); ?>h <?php echo $bus['duration_minutes'] % 60; ?>m</td>
                                <td>$<?php echo number_format($bus['price'], 2); ?></td>
                                <td>
                                    <?php echo $bus['available_seats']; ?> / <?php echo $bus['capacity']; ?>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo ($bus['available_seats'] / $bus['capacity']) * 100; ?>%"></div>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                                        <button type="submit" name="delete_bus" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de eliminar este autobús?')">Eliminar</button>
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
            const form = document.getElementById('bus-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
        
        // Configurar fechas mínimas
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('departure_date').min = today;
    </script>
</body>
</html>