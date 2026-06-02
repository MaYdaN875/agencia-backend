<?php
session_start();
include '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM admin_users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        // En un sistema real, usaríamos password_verify()
        if (password_verify($password, $user['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_name'] = $user['full_name'];
            header('Location: dashboard.php');
            exit;
        }
    }
    
    $error = "Usuario o contraseña incorrectos";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Agencia de Viajes</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-form">
            <h1>Panel de Administración</h1>
            <p>Inicia sesión para acceder al sistema</p>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
            </form>
            
            <div class="login-info">
                <p><strong>Credenciales de prueba:</strong></p>
                <p>Usuario: admin</p>
                <p>Contraseña: password</p>
            </div>
        </div>
    </div>
</body>
</html>