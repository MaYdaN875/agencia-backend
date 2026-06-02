<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Manejar operaciones CRUD para hoteles
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_hotel'])) {
        $name = $_POST['name'];
        $location = $_POST['location'];
        $address = $_POST['address'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $stars = $_POST['stars'];
        $description = $_POST['description'];
        
        $sql = "INSERT INTO hotels (name, location, address, phone, email, stars, description) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssis", $name, $location, $address, $phone, $email, $stars, $description);
        
        if ($stmt->execute()) {
            $hotel_id = $stmt->insert_id;
            $success = "Hotel agregado correctamente";
        } else {
            $error = "Error al agregar el hotel: " . $conn->error;
        }
    }
    
    if (isset($_POST['add_room'])) {
        $hotel_id = $_POST['hotel_id'];
        $room_type = $_POST['room_type'];
        $capacity = $_POST['capacity'];
        $price_per_night = $_POST['price_per_night'];
        $available_rooms = $_POST['available_rooms'];
        $amenities = $_POST['amenities'];
        
        $sql = "INSERT INTO hotel_rooms (hotel_id, room_type, capacity, price_per_night, available_rooms, amenities) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isidis", $hotel_id, $room_type, $capacity, $price_per_night, $available_rooms, $amenities);
        
        if ($stmt->execute()) {
            // Actualizar disponibilidad
            $room_id = $stmt->insert_id;
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime('+60 days'));
            
            $availability_sql = "INSERT INTO hotel_availability (hotel_room_id, date, available_rooms) 
                                SELECT ?, date, ? 
                                FROM (SELECT DATE_ADD(?, INTERVAL t0.i + t1.i*10 + t2.i*100 DAY) as date
                                      FROM (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t0,
                                           (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,
                                           (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t2
                                      WHERE DATE_ADD(?, INTERVAL t0.i + t1.i*10 + t2.i*100 DAY) <= ?) dates";
            $stmt_avail = $conn->prepare($availability_sql);
            $stmt_avail->bind_param("iiss", $room_id, $available_rooms, $start_date, $start_date, $end_date);
            $stmt_avail->execute();
            
            $success = "Habitación agregada correctamente";
        } else {
            $error = "Error al agregar la habitación: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_hotel'])) {
        $hotel_id = $_POST['hotel_id'];
        $sql = "DELETE FROM hotels WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $hotel_id);
        
        if ($stmt->execute()) {
            $success = "Hotel eliminado correctamente";
        } else {
            $error = "Error al eliminar el hotel: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_room'])) {
        $room_id = $_POST['room_id'];
        $sql = "DELETE FROM hotel_rooms WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $room_id);
        
        if ($stmt->execute()) {
            $success = "Habitación eliminada correctamente";
        } else {
            $error = "Error al eliminar la habitación: " . $conn->error;
        }
    }
}

// Obtener todos los hoteles con sus habitaciones
$hotels = $conn->query("
    SELECT h.*, COUNT(hr.id) as room_count 
    FROM hotels h 
    LEFT JOIN hotel_rooms hr ON h.id = hr.hotel_id 
    GROUP BY h.id
");

// *** ¡NUEVO! Obtener la lista global de ubicaciones ***
$locations_list = $conn->query("SELECT * FROM locations ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Hoteles - Admin</title>
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
                <a href="buses.php" class="nav-item">Autobuses</a>
                <a href="hotels.php" class="nav-item active">Hoteles</a>
                <a href="locations.php" class="nav-item">Ubicaciones</a>
                <a href="reservations.php" class="nav-item">Reservaciones</a>
                <a href="login.php?logout=true" class="nav-item logout">Cerrar Sesión</a>
            </nav>
        </div>

        <div class="main-content">
            <header class="content-header">
                <h1>Gestión de Hoteles</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="toggleHotelForm()">Agregar Hotel</button>
                    <button class="btn btn-secondary" onclick="toggleRoomForm()">Agregar Habitación</button>
                </div>
            </header>

            <div class="content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="form-container" id="hotel-form" style="display: none; margin-bottom: 2rem;">
                    <h2>Agregar Nuevo Hotel</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="name">Nombre del Hotel</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="location">Ubicación</label>
                                <select id="location" name="location" required>
                                    <option value="">Seleccionar ubicación</option>
                                    <?php while ($location = $locations_list->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($location['name']); ?>">
                                            <?php echo htmlspecialchars($location['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                </div>
                            <div class="form-group">
                                <label for="stars">Categoría (Estrellas)</label>
                                <select id="stars" name="stars" required>
                                    <option value="1">1 Estrella</option>
                                    <option value="2">2 Estrellas</option>
                                    <option value="3">3 Estrellas</option>
                                    <option value="4">4 Estrellas</option>
                                    <option value="5">5 Estrellas</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Dirección</label>
                            <textarea id="address" name="address" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Teléfono</label>
                                <input type="text" id="phone" name="phone" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Descripción</label>
                            <textarea id="description" name="description" rows="4"></textarea>
                        </div>
                        
                        <button type="submit" name="add_hotel" class="btn btn-primary">Agregar Hotel</button>
                        <button type="button" class="btn" onclick="toggleHotelForm()">Cancelar</button>
                    </form>
                </div>
                <div class="form-container" id="room-form" style="display: none; margin-bottom: 2rem;">
                    <h2>Agregar Nueva Habitación</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="hotel_id">Hotel</label>
                            <select id="hotel_id" name="hotel_id" required>
                                <option value="">Seleccionar hotel</option>
                                <?php 
                                $hotels_list = $conn->query("SELECT * FROM hotels ORDER BY name");
                                while ($hotel = $hotels_list->fetch_assoc()): ?>
                                    <option value="<?php echo $hotel['id']; ?>">
                                        <?php echo htmlspecialchars($hotel['name']); ?> - <?php echo htmlspecialchars($hotel['location']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="room_type">Tipo de Habitación</label>
                                <input type="text" id="room_type" name="room_type" required>
                            </div>
                            <div class="form-group">
                                <label for="capacity">Capacidad (personas)</label>
                                <input type="number" id="capacity" name="capacity" min="1" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="price_per_night">Precio por Noche</label>
                                <input type="number" id="price_per_night" name="price_per_night" step="0.01" min="0" required>
                            </div>
                            <div class="form-group">
                                <label for="available_rooms">Habitaciones Disponibles</label>
                                <input type="number" id="available_rooms" name="available_rooms" min="0" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="amenities">Servicios incluidos</label>
                            <textarea id="amenities" name="amenities" rows="3" placeholder="TV, A/C, Wi-Fi, etc."></textarea>
                        </div>
                        
                        <button type="submit" name="add_room" class="btn btn-primary">Agregar Habitación</button>
                        <button type="button" class="btn" onclick="toggleRoomForm()">Cancelar</button>
                    </form>
                </div>

                <div class="data-table">
                    <div class="table-header">
                        <h2>Hoteles Registrados</h2>
                        <div class="table-actions">
                            <input type="text" id="search-hotels" placeholder="Buscar hoteles..." class="table-filter" data-table="hotels-table">
                        </div>
                    </div>
                    
                    <table id="hotels-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Ubicación</th>
                                <th>Categoría</th>
                                <th>Contacto</th>
                                <th>Habitaciones</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($hotel = $hotels->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($hotel['name']); ?></strong>
                                    <?php if ($hotel['description']): ?>
                                        <br><small><?php echo htmlspecialchars(substr($hotel['description'], 0, 100)); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($hotel['location']); ?><br>
                                    <small><?php echo htmlspecialchars(substr($hotel['address'], 0, 50)); ?>...</small>
                                </td>
                                <td>
                                    <?php echo str_repeat('★', $hotel['stars']); ?><br>
                                    <small><?php echo $hotel['stars']; ?> estrellas</small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($hotel['phone']); ?><br>
                                    <small><?php echo htmlspecialchars($hotel['email']); ?></small>
                                </td>
                                <td><?php echo $hotel['room_count']; ?> tipos</td>
                                <td>
                                    <a href="hotel_rooms.php?hotel_id=<?php echo $hotel['id']; ?>" class="btn btn-primary btn-sm">Ver Habitaciones</a>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="hotel_id" value="<?php echo $hotel['id']; ?>">
                                        <button type="submit" name="delete_hotel" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de eliminar este hotel?')">Eliminar</button>
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
        function toggleHotelForm() {
            const form = document.getElementById('hotel-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            // Ocultar el otro formulario si está visible
            document.getElementById('room-form').style.display = 'none';
        }
        
        function toggleRoomForm() {
            const form = document.getElementById('room-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            // Ocultar el otro formulario si está visible
            document.getElementById('hotel-form').style.display = 'none';
        }
    </script>
</body>
</html>