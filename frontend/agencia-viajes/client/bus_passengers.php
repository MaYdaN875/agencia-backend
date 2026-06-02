<?php
include '../config/database.php';

// Obtener parámetros
$bus_id = $_GET['bus_id'] ?? '';
$package_type = $_GET['package_type'] ?? '';
$adults = $_GET['adults'] ?? 1;
$children = $_GET['children'] ?? 0;
$return_date = $_GET['return_date'] ?? '';
$one_way = isset($_GET['one_way']) ? true : false;

// Obtener información del autobús
$bus = $conn->query("SELECT * FROM buses WHERE id = $bus_id")->fetch_assoc();

$total_passengers = $adults + $children;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datos de Pasajeros - Autobús</title>
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

    <section class="passenger-form-section">
        <div class="container">
            <h2>Datos de los Pasajeros</h2>
            
            <div class="transport-info" style="background: white; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                <h3>Información del Autobús</h3>
                <p><strong>Empresa:</strong> <?php echo htmlspecialchars($bus['company']); ?></p>
                <p><strong>Ruta:</strong> <?php echo htmlspecialchars($bus['origin']); ?> → <?php echo htmlspecialchars($bus['destination']); ?></p>
                <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($bus['departure_date'])); ?></p>
                <p><strong>Hora:</strong> <?php echo $bus['departure_time']; ?></p>
                <p><strong>Duración:</strong> <?php echo floor($bus['duration_minutes'] / 60); ?>h <?php echo $bus['duration_minutes'] % 60; ?>m</p>
                <p><strong>Precio por persona:</strong> $<?php echo number_format($bus['price'], 2); ?></p>
            </div>

            <form action="process_reservation.php" method="POST" id="passengers-form">
                <input type="hidden" name="service_type" value="bus">
                <input type="hidden" name="service_id" value="<?php echo $bus_id; ?>">
                <input type="hidden" name="package_type" value="<?php echo $package_type; ?>">
                <input type="hidden" name="adults" value="<?php echo $adults; ?>">
                <input type="hidden" name="children" value="<?php echo $children; ?>">
                <input type="hidden" name="return_date" value="<?php echo $return_date; ?>">
                <input type="hidden" name="one_way" value="<?php echo $one_way ? '1' : '0'; ?>">

                <div id="passengers-container">
                    <?php for ($i = 1; $i <= $total_passengers; $i++): ?>
                        <div class="passenger-form">
                            <h3>Pasajero <?php echo $i; ?> <?php echo $i <= $adults ? '(Adulto)' : '(Niño)'; ?></h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="passenger_name_<?php echo $i; ?>">Nombre Completo</label>
                                    <input type="text" id="passenger_name_<?php echo $i; ?>" name="passengers[<?php echo $i; ?>][name]" required>
                                </div>
                                <div class="form-group">
                                    <label for="passenger_birthdate_<?php echo $i; ?>">Fecha de Nacimiento</label>
                                    <input type="date" id="passenger_birthdate_<?php echo $i; ?>" name="passengers[<?php echo $i; ?>][birthdate]" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="passenger_gender_<?php echo $i; ?>">Género</label>
                                    <select id="passenger_gender_<?php echo $i; ?>" name="passengers[<?php echo $i; ?>][gender]" required>
                                        <option value="">Seleccionar</option>
                                        <option value="male">Masculino</option>
                                        <option value="female">Femenino</option>
                                        <option value="other">Otro</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="passenger_country_<?php echo $i; ?>">País de Nacimiento</label>
                                    <input type="text" id="passenger_country_<?php echo $i; ?>" name="passengers[<?php echo $i; ?>][country]" required>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="customer_name">Nombre del Contacto</label>
                        <input type="text" id="customer_name" name="customer_name" required>
                    </div>
                    <div class="form-group">
                        <label for="customer_email">Email</label>
                        <input type="email" id="customer_email" name="customer_email" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="customer_phone">Teléfono</label>
                    <input type="text" id="customer_phone" name="customer_phone" required>
                </div>

                <div class="price-summary" style="background: white; padding: 1.5rem; border-radius: 10px; margin: 2rem 0; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                    <h3>Resumen de Precios</h3>
                    <div class="summary-item">
                        <span>Pasajeros adultos (<?php echo $adults; ?>):</span>
                        <span>$<?php echo number_format($bus['price'] * $adults, 2); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Pasajeros niños (<?php echo $children; ?>):</span>
                        <span>$<?php echo number_format($bus['price'] * $children, 2); ?></span>
                    </div>
                    <div class="summary-total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($bus['price'] * $total_passengers, 2); ?></span>
                    </div>
                </div>

                <?php if (in_array($package_type, ['bus_hotel'])): ?>
                    <button type="submit" name="action" value="continue_to_hotel" class="btn btn-accent" style="width: 100%; margin-bottom: 1rem;">
                        Continuar a Selección de Hotel
                    </button>
                <?php endif; ?>

                <button type="submit" name="action" value="reserve_only" class="btn btn-primary" style="width: 100%;">
                    Reservar <?php echo in_array($package_type, ['bus_hotel']) ? 'Solo Autobús' : 'Ahora'; ?>
                </button>
            </form>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2023 Agencia de Viajes. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
        // Validación de fechas de nacimiento para niños
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const eighteenYearsAgo = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
            
            <?php for ($i = 1; $i <= $total_passengers; $i++): ?>
                <?php if ($i > $adults): // Es un niño ?>
                    const birthdateInput<?php echo $i; ?> = document.getElementById('passenger_birthdate_<?php echo $i; ?>');
                    if (birthdateInput<?php echo $i; ?>) {
                        birthdateInput<?php echo $i; ?>.max = eighteenYearsAgo.toISOString().split('T')[0];
                    }
                <?php endif; ?>
            <?php endfor; ?>
        });
    </script>
</body>
</html>