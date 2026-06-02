<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Manejar operaciones CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_agency'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        
        $sql = "INSERT INTO agencies (name, email, phone, address) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $email, $phone, $address);
        
        if ($stmt->execute()) {
            $success = "Agencia agregada correctamente";
        } else {
            $error = "Error al agregar la agencia: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_agency'])) {
        $agency_id = $_POST['agency_id'];
        $sql = "DELETE FROM agencies WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $agency_id);
        
        if ($stmt->execute()) {
            $success = "Agencia eliminada correctamente";
        } else {
            $error = "Error al eliminar la agencia: " . $conn->error;
        }
    }
}

// Obtener todas las agencias
$agencies = $conn->query("SELECT * FROM agencies");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Agencias - Admin</title>
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
                <a href="agencies.php" class="nav-item active">Agencias</a>
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
                <h1>Gestión de Agencias Afiliadas</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="toggleForm()">Agregar Agencia</button>
                </div>
            </header>

            <div class="content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Formulario para agregar agencia -->
                <div class="form-container" id="agency-form" style="display: none; margin-bottom: 2rem;">
                    <h2>Agregar Nueva Agencia</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="name">Nombre de la Agencia</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Teléfono</label>
                            <input type="text" id="phone" name="phone" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Dirección</label>
                            <textarea id="address" name="address" rows="3" required></textarea>
                        </div>
                        
                        <button type="submit" name="add_agency" class="btn btn-primary">Agregar Agencia</button>
                        <button type="button" class="btn" onclick="toggleForm()">Cancelar</button>
                    </form>
                </div>

                <!-- Lista de agencias -->
                <div class="data-table">
                    <div class="table-header">
                        <h2>Agencias Afiliadas</h2>
                        <div class="table-actions">
                            <input type="text" id="search-agencies" placeholder="Buscar agencias..." class="table-filter" data-table="agencies-table">
                        </div>
                    </div>
                    
                    <table id="agencies-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Contacto</th>
                                <th>Dirección</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($agency = $agencies->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($agency['name']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($agency['email']); ?><br>
                                    <small><?php echo htmlspecialchars($agency['phone']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($agency['address']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($agency['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="agency_id" value="<?php echo $agency['id']; ?>">
                                        <button type="submit" name="delete_agency" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de eliminar esta agencia?')">Eliminar</button>
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
            const form = document.getElementById('agency-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>