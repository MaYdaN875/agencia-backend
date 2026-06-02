<?php
include '../config/database.php';

// Array de países en orden alfabético
$paises = [
    "Afganistán", "Albania", "Alemania", "Andorra", "Angola", "Antigua y Barbuda", 
    "Arabia Saudita", "Argelia", "Argentina", "Armenia", "Australia", "Austria", 
    "Azerbaiyán", "Bahamas", "Bangladés", "Barbados", "Baréin", "Bélgica", 
    "Belice", "Benín", "Bielorrusia", "Bolivia", "Bosnia y Herzegovina", 
    "Botsuana", "Brasil", "Brunéi", "Bulgaria", "Burkina Faso", "Burundi", 
    "Bután", "Cabo Verde", "Camboya", "Camerún", "Canadá", "Catar", "Chad", 
    "Chile", "China", "Chipre", "Colombia", "Comoras", "Corea del Norte", 
    "Corea del Sur", "Costa de Marfil", "Costa Rica", "Croacia", "Cuba", 
    "Dinamarca", "Dominica", "Ecuador", "Egipto", "El Salvador", "Emiratos Árabes Unidos", 
    "Eritrea", "Eslovaquia", "Eslovenia", "España", "Estados Unidos", "Estonia", 
    "Etiopía", "Filipinas", "Finlandia", "Fiyi", "Francia", "Gabón", "Gambia", 
    "Georgia", "Ghana", "Granada", "Grecia", "Guatemala", "Guinea", 
    "Guinea-Bisáu", "Guinea Ecuatorial", "Guyana", "Haití", "Honduras", "Hungría", 
    "India", "Indonesia", "Irak", "Irán", "Irlanda", "Islandia", "Islas Marshall", 
    "Islas Salomón", "Israel", "Italia", "Jamaica", "Japón", "Jordania", 
    "Kazajistán", "Kenia", "Kirguistán", "Kiribati", "Kuwait", "Laos", "Lesoto", 
    "Letonia", "Líbano", "Liberia", "Libia", "Liechtenstein", "Lituania", 
    "Luxemburgo", "Macedonia del Norte", "Madagascar", "Malasia", "Malaui", 
    "Maldivas", "Malí", "Malta", "Marruecos", "Mauricio", "Mauritania", "México", 
    "Micronesia", "Moldavia", "Mónaco", "Mongolia", "Montenegro", "Mozambique", 
    "Namibia", "Nauru", "Nepal", "Nicaragua", "Níger", "Nigeria", "Noruega", 
    "Nueva Zelanda", "Omán", "Países Bajos", "Pakistán", "Palaos", "Palestina", 
    "Panamá", "Papúa Nueva Guinea", "Paraguay", "Perú", "Polonia", "Portugal", 
    "Reino Unido", "República Centroafricana", "República Checa", 
    "República del Congo", "República Democrática del Congo", "República Dominicana", 
    "Ruanda", "Rumanía", "Rusia", "Samoa", "San Cristóbal y Nieves", "San Marino", 
    "San Vicente y las Granadinas", "Santa Lucía", "Santo Tomé y Príncipe", 
    "Senegal", "Serbia", "Seychelles", "Sierra Leona", "Singapur", "Siria", 
    "Somalia", "Sri Lanka", "Suazilandia", "Sudáfrica", "Sudán", "Sudán del Sur", 
    "Suecia", "Suiza", "Surinam", "Tailandia", "Tanzania", "Tayikistán", 
    "Timor Oriental", "Togo", "Tonga", "Trinidad y Tobago", "Túnez", "Turkmenistán", 
    "Turquía", "Tuvalu", "Ucrania", "Uganda", "Uruguay", "Uzbekistán", "Vanuatu", 
    "Ciudad del Vaticano", "Venezuela", "Vietnam", "Yemen", "Yibuti", "Zambia", "Zimbabue"
];
sort($paises);

// Array de códigos de teléfono por país
$codigos_telefono = [
    "+52" => "México (+52)",
    "+1" => "Estados Unidos/Canadá (+1)",
    "+34" => "España (+34)",
    "+51" => "Perú (+51)",
    "+54" => "Argentina (+54)",
    "+55" => "Brasil (+55)",
    "+56" => "Chile (+56)",
    "+57" => "Colombia (+57)",
    "+58" => "Venezuela (+58)",
    "+502" => "Guatemala (+502)",
    "+503" => "El Salvador (+503)",
    "+504" => "Honduras (+504)",
    "+505" => "Nicaragua (+505)",
    "+506" => "Costa Rica (+506)",
    "+507" => "Panamá (+507)",
    "+593" => "Ecuador (+593)",
    "+595" => "Paraguay (+595)",
    "+598" => "Uruguay (+598)",
    "+591" => "Bolivia (+591)"
];

// Obtener parámetros
$flight_id = $_GET['flight_id'] ?? '';
$package_type = $_GET['package_type'] ?? '';
$adults = $_GET['adults'] ?? 1;
$children = $_GET['children'] ?? 0;
$return_date = $_GET['return_date'] ?? '';
$one_way = $_GET['one_way'] ?? '0';

// Validar que tengamos los parámetros necesarios
if (!$flight_id) {
    header('Location: search.php');
    exit;
}

// Obtener información del vuelo
$flight = $conn->query("SELECT * FROM flights WHERE id = $flight_id")->fetch_assoc();

if (!$flight) {
    die("Vuelo no encontrado");
}

// Si hay vuelo de regreso, obtenerlo también
$return_flight = null;
if ($one_way == '0' && $return_date) {
    $return_flight_result = $conn->query("
        SELECT * FROM flights 
        WHERE origin = '{$flight['destination']}' 
        AND destination = '{$flight['origin']}' 
        AND departure_date = '$return_date'
        AND available_seats >= " . ($adults + $children) . "
        LIMIT 1
    ");
    
    if ($return_flight_result && $return_flight_result->num_rows > 0) {
        $return_flight = $return_flight_result->fetch_assoc();
    }
}

$total_passengers = $adults + $children;
$total_price = $flight['price'] * $total_passengers;
if ($return_flight) {
    $total_price += $return_flight['price'] * $total_passengers;
}

// Calcular horas de llegada
$departure_datetime = $flight['departure_date'] . ' ' . $flight['departure_time'];
$arrival_datetime = date('Y-m-d H:i:s', strtotime("+{$flight['duration_minutes']} minutes", strtotime($departure_datetime)));
$arrival_date_formatted = date('d/m/Y', strtotime($arrival_datetime));
$arrival_time_formatted = date('H:i', strtotime($arrival_datetime));

if ($return_flight) {
    $return_departure_datetime = $return_flight['departure_date'] . ' ' . $return_flight['departure_time'];
    $return_arrival_datetime = date('Y-m-d H:i:s', strtotime("+{$return_flight['duration_minutes']} minutes", strtotime($return_departure_datetime)));
    $return_arrival_date_formatted = date('d/m/Y', strtotime($return_arrival_datetime));
    $return_arrival_time_formatted = date('H:i', strtotime($return_arrival_datetime));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datos de Pasajeros - Vuelo</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
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
            
            <div class="transport-info">
                <h3>Información del Vuelo</h3>
                <p><strong>Aerolínea:</strong> <?php echo htmlspecialchars($flight['airline']); ?></p>
                <p><strong>Ruta:</strong> <?php echo htmlspecialchars($flight['origin']); ?> → <?php echo htmlspecialchars($flight['destination']); ?></p>
                <p><strong>Fecha Salida:</strong> <?php echo date('d/m/Y', strtotime($flight['departure_date'])); ?></p>
                <p><strong>Hora Salida:</strong> <?php echo $flight['departure_time']; ?></p>
                <p><strong>Llegada Estimada:</strong> <?php echo $arrival_date_formatted . ' ' . $arrival_time_formatted; ?></p>
                <p><strong>Duración:</strong> <?php echo floor($flight['duration_minutes'] / 60); ?>h <?php echo $flight['duration_minutes'] % 60; ?>m</p>
                <p><strong>Precio por persona:</strong> $<?php echo number_format($flight['price'], 2); ?></p>
                
                <?php if ($return_flight): ?>
                <div class="return-flight-info">
                    <h4>Vuelo de Regreso</h4>
                    <p><strong>Aerolínea:</strong> <?php echo htmlspecialchars($return_flight['airline']); ?></p>
                    <p><strong>Ruta:</strong> <?php echo htmlspecialchars($return_flight['origin']); ?> → <?php echo htmlspecialchars($return_flight['destination']); ?></p>
                    <p><strong>Fecha Salida:</strong> <?php echo date('d/m/Y', strtotime($return_flight['departure_date'])); ?></p>
                    <p><strong>Hora Salida:</strong> <?php echo $return_flight['departure_time']; ?></p>
                    <p><strong>Llegada Estimada:</strong> <?php echo $return_arrival_date_formatted . ' ' . $return_arrival_time_formatted; ?></p>
                    <p><strong>Precio por persona:</strong> $<?php echo number_format($return_flight['price'], 2); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <form action="process_flight_reservation.php" method="POST" id="passengers-form">
                <input type="hidden" name="flight_id" value="<?php echo $flight_id; ?>">
                <input type="hidden" name="return_flight_id" value="<?php echo $return_flight['id'] ?? ''; ?>">
                <input type="hidden" name="package_type" value="<?php echo $package_type; ?>">
                <input type="hidden" name="adults" value="<?php echo $adults; ?>">
                <input type="hidden" name="children" value="<?php echo $children; ?>">
                <input type="hidden" name="one_way" value="<?php echo $one_way; ?>">
                
                <input type="hidden" name="return_date" value="<?php echo htmlspecialchars($return_date); ?>">

                <div id="passengers-container">
                    <?php for ($i = 1; $i <= $total_passengers; $i++): ?>
                        <div class="passenger-form">
                            <h3>Pasajero <?php echo $i; ?> <?php echo $i <= $adults ? '(Adulto)' : '(Niño)'; ?></h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="passenger_name_<?php echo $i; ?>">Nombre Completo *</label>
                                    <input type="text" id="passenger_name_<?php echo $i; ?>" name="passengers[<?php echo $i; ?>][name]" required>
                                </div>
                                <div class="form-group">
                                    <label for="passenger_birthdate_<?php echo $i; ?>">Fecha de Nacimiento *</label>
                                    <input type="date" id="passenger_birthdate_<?php echo $i; ?>" name="passengers[<?php echo $i; ?>][birthdate]" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="passenger_gender_<?php echo $i; ?>">Género *</label>
                                    <select id="passenger_gender_<?php echo $i; ?>" name="passengers[<?php echo $i; ?>][gender]" required>
                                        <option value="">Seleccionar</option>
                                        <option value="male">Masculino</option>
                                        <option value="female">Femenino</option>
                                        <option value="other">Otro</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="passenger_country_<?php echo $i; ?>">País de Nacimiento *</label>
                                    <select id="passenger_country_<?php echo $i; ?>" name="passengers[<?php echo $i; ?>][country]" required>
                                        <option value="">Seleccionar país</option>
                                        <?php foreach ($paises as $pais): ?>
                                            <option value="<?php echo htmlspecialchars($pais); ?>"><?php echo htmlspecialchars($pais); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="copy-data-section">
                    <button type="button" id="copy-passenger-data" class="btn btn-secondary">
                        📋 Usar los mismos datos que el Pasajero 1
                    </button>
                </div>

                <div class="form-section">
                    <h3>Información de Contacto</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="customer_name">Nombre del Contacto *</label>
                            <input type="text" id="customer_name" name="customer_name" required>
                        </div>
                        <div class="form-group">
                            <label for="customer_email">Email *</label>
                            <input type="email" id="customer_email" name="customer_email" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="customer_phone_code">Código de País *</label>
                            <select id="customer_phone_code" name="customer_phone_code" required>
                                <option value="">Seleccionar código</option>
                                <?php foreach ($codigos_telefono as $codigo => $texto): ?>
                                    <option value="<?php echo $codigo; ?>"><?php echo $texto; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="customer_phone">Teléfono * (10 dígitos)</label>
                            <input type="tel" id="customer_phone" name="customer_phone" maxlength="10" pattern="[0-9]{10}" title="Por favor ingresa exactamente 10 dígitos" required>
                            <small style="display: block; margin-top: 0.25rem; color: #666;">Solo números, sin espacios ni guiones</small>
                        </div>
                    </div>
                </div>

                <div class="price-summary">
                    <h3>Resumen de Precios</h3>
                    
                    <div class="summary-item">
                        <span>Vuelo de ida - <?php echo $adults; ?> adulto(s):</span>
                        <span>$<?php echo number_format($flight['price'] * $adults, 2); ?></span>
                    </div>
                    
                    <?php if ($children > 0): ?>
                    <div class="summary-item">
                        <span>Vuelo de ida - <?php echo $children; ?> niño(s):</span>
                        <span>$<?php echo number_format($flight['price'] * $children, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($return_flight): ?>
                    <div class="summary-item">
                        <span>Vuelo de regreso - <?php echo $adults; ?> adulto(s):</span>
                        <span>$<?php echo number_format($return_flight['price'] * $adults, 2); ?></span>
                    </div>
                    
                    <?php if ($children > 0): ?>
                    <div class="summary-item">
                        <span>Vuelo de regreso - <?php echo $children; ?> niño(s):</span>
                        <span>$<?php echo number_format($return_flight['price'] * $children, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <div class="summary-total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($total_price, 2); ?></span>
                    </div>
                </div>

                <?php if (in_array($package_type, ['flight_hotel'])): ?>
                    <button type="submit" name="action" value="continue_to_hotel" class="btn btn-accent btn-large">
                        🏨 Continuar a Selección de Hotel
                    </button>
                <?php endif; ?>

                <button type="submit" name="action" value="reserve_only" class="btn btn-primary btn-large">
                    ✅ Reservar <?php echo in_array($package_type, ['flight_hotel']) ? 'Solo Vuelo' : 'Ahora'; ?>
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

            // Botón para copiar datos del primer pasajero
            document.getElementById('copy-passenger-data').addEventListener('click', function() {
                const passengerName = document.getElementById('passenger_name_1').value;
                const passengerCountry = document.getElementById('passenger_country_1').value;
                
                if (!passengerName) {
                    alert('Por favor, primero complete los datos del Pasajero 1');
                    return;
                }
                
                document.getElementById('customer_name').value = passengerName;
                alert('Nombre del Pasajero 1 copiado al campo de contacto');
            });

            // Validación de teléfono (solo números)
            document.getElementById('customer_phone').addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        });
    </script>

    <style>
        .transport-info {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .return-flight-info {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .copy-data-section {
            text-align: center;
            margin: 2rem 0;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .btn-large {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            margin: 0.5rem 0;
        }

        .form-section {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 2px solid #eee;
        }

        .price-summary {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 2rem 0;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
    </style>
</body>
</html>