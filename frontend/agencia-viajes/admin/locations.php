<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Manejar operaciones CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Agregar nueva ubicación
    if (isset($_POST['add_location'])) {
        $name = $_POST['name'];
        
        // Evitar duplicados
        $check = $conn->prepare("SELECT id FROM locations WHERE name = ?");
        $check->bind_param("s", $name);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows == 0) {
            $sql = "INSERT INTO locations (name) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $name);
            
            if ($stmt->execute()) {
                $success = "Ubicación agregada correctamente";
            } else {
                $error = "Error al agregar la ubicación: " . $conn->error;
            }
        } else {
            $error = "Esa ubicación ya existe.";
        }
    }
    
    // Eliminar ubicación
    if (isset($_POST['delete_location'])) {
        $location_id = $_POST['location_id'];
        
        // Advertencia: Sería ideal comprobar si esta ubicación está en uso
        // en vuelos, buses u hoteles antes de permitir borrarla.
        // Por simplicidad, permitimos borrarla.
        
        $sql = "DELETE FROM locations WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $location_id);
        
        if ($stmt->execute()) {
            $success = "Ubicación eliminada correctamente";
        } else {
            $error = "Error al eliminar la ubicación: " . $conn->error;
        }
    }
}

// Obtener todas las ubicaciones
$locations = $conn->query("SELECT * FROM locations ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ubicaciones - Admin</title>
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
                <a href="hotels.php" class="nav-item">Hoteles</a>
                <a href="locations.php" class="nav-item active">Ubicaciones</a> 
                <a href="reservations.php" class="nav-item">Reservaciones</a>
                <a href="login.php?logout=true" class="nav-item logout">Cerrar Sesión</a>
            </nav>
        </div>

        <div class="main-content">
            <header class="content-header">
                <h1>Gestión de Ubicaciones</h1>
            </header>

            <div class="content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="form-container" style="margin-bottom: 2rem;">
                    <h2>Agregar Nueva Ubicación</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="name">Nombre de la Ubicación</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <button type="submit" name="add_location" class="btn btn-primary">Agregar Ubicación</button>
                    </form>
                </div>

                <div class="data-table">
                    <div class="table-header">
                        <h2>Ubicaciones Registradas</h2>
                        <div class="table-actions">
                            <input type="text" id="search-locations" placeholder="Buscar ubicaciones..." class="table-filter" data-table="locations-table">
                        </div>
                    </div>
                    
                    <table id="locations-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($location = $locations->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $location['id']; ?></td>
                                <td><?php echo htmlspecialchars($location['name']); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="location_id" value="<?php echo $location['id']; ?>">
                                        <button type="submit" name="delete_location" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de eliminar esta ubicación?')">Eliminar</button>
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
    
    <script src="../js/admin.js"></script>
</body>
</html>