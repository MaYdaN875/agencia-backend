<?php include '../config/database.php'; 

// *** ¡MODIFICADO! Cargar orígenes desde la tabla global de ubicaciones ***
$origins = $conn->query("
    SELECT name as origin FROM locations 
    ORDER BY name ASC
");

// La consulta de destinos se elimina, ya que la API se encarga de eso.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agencia de Viajes - Inicio</title>
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

    <section class="hero">
        <div class="container">
            <h1>Descubre el Mundo con Nosotros</h1>
            <p>Encuentra los mejores precios en vuelos, hoteles y paquetes vacacionales</p>
            <a href="#search-form" class="btn btn-accent">Buscar Viajes</a>
        </div>
    </section>

    <section class="search-form" id="search-form">
        <div class="container">
            <form action="search.php" method="GET" id="search-form-element">
                <div class="form-group">
                    <label for="package_type">Tipo de Paquete</label>
                    <select id="package_type" name="package_type" required onchange="toggleFormFields()">
                        <option value="">Seleccione una opción</option>
                        <option value="flight_only">Vuelo solamente</option>
                        <option value="bus_only">Autobús solamente</option>
                        <option value="hotel_only">Hotel solamente</option>
                        <option value="flight_hotel">Paquete Vuelo + Hotel</option>
                        <option value="bus_hotel">Paquete Autobús + Hotel</option>
                    </select>
                </div>
                
                <div class="form-row" id="origin_destination_row">
                    <div class="form-group" id="origin_group">
                        <label for="origin">Origen</label>
                        <select id="origin" name="origin" required onchange="updateDestinations()">
                            <option value="">Seleccionar origen</option>
                            <?php while ($origin = $origins->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($origin['origin']); ?>">
                                    <?php echo htmlspecialchars($origin['origin']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group" id="destination_group">
                        <label for="destination">Destino</label>
                        <select id="destination" name="destination" required onchange="updateAvailableDates()">
                            <option value="">Primero seleccione origen</option>
                        </select>
                    </div>
                </div>

                <div class="form-row" id="dates_row">
                    <div class="form-group">
                        <label for="departure_date">Fecha de Ida</label>
                        <input type="date" id="departure_date" name="departure_date" required>
                        <div id="departure_dates_info" class="dates-info" style="display: none;"></div>
                    </div>
                    <div class="form-group" id="return_date_group">
                        <label for="return_date">Fecha de Regreso</label>
                        <input type="date" id="return_date" name="return_date">
                        <div id="return_dates_info" class="dates-info" style="display: none;"></div>
                    </div>
                </div>

                <div class="form-group" id="one_way_group">
                    <div class="checkbox-container">
                        <input type="checkbox" id="one_way" name="one_way" value="1" onchange="toggleOneWay()">
                        <label for="one_way" id="one_way_label">¿Vuelo solo ida?</label>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="adults">Adultos</label>
                        <select id="adults" name="adults" required>
                            <option value="1">1</option>
                            <option value="2" selected>2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="children">Niños (0-17 años)</label>
                        <select id="children" name="children">
                            <option value="0" selected>0</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-accent" style="width: 100%;" id="search_button">Buscar Viajes</button>
            </form>
        </div>
    </section>

    <section class="services">
        <div class="container">
            <div class="section-title">
                <h2>Nuestros Servicios</h2>
                <p>Ofrecemos una amplia gama de servicios para hacer de tu viaje una experiencia inolvidable</p>
            </div>
            
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">✈️</div>
                    <div class="service-content">
                        <h3>Vuelos</h3>
                        <p>Encuentra los mejores precios en vuelos nacionales e internacionales con las principales aerolíneas.</p>
                    </div>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">🚌</div>
                    <div class="service-content">
                        <h3>Autobuses</h3>
                        <p>Viaja cómodamente en autobús con las mejores empresas de transporte terrestre.</p>
                    </div>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">🏨</div>
                    <div class="service-content">
                        <h3>Hoteles</h3>
                        <p>Reserva alojamientos en los mejores hoteles y resorts en tu destino favorito.</p>
                    </div>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">🎁</div>
                    <div class="service-content">
                        <h3>Paquetes</h3>
                        <p>Aprovecha nuestros paquetes todo incluido con vuelo + hotel a precios increíbles.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2023 Agencia de Viajes. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
        // Función para mostrar/ocultar campos según el tipo de paquete
        function toggleFormFields() {
            const packageType = document.getElementById('package_type').value;
            const originGroup = document.getElementById('origin_group');
            const destinationGroup = document.getElementById('destination_group');
            const oneWayGroup = document.getElementById('one_way_group');
            const oneWayLabel = document.getElementById('one_way_label');
            const originDestinationRow = document.getElementById('origin_destination_row');

            // Resetear todos los campos primero
            originGroup.style.display = 'block';
            destinationGroup.style.display = 'block';
            oneWayGroup.style.display = 'block';
            originDestinationRow.style.display = 'flex';

            switch(packageType) {
                case 'hotel_only':
                    originGroup.style.display = 'none';
                    oneWayGroup.style.display = 'none';
                    // Para hotel only, cargar destinos de hoteles
                    loadHotelDestinations();
                    break;
                    
                case 'bus_only':
                case 'bus_hotel':
                    oneWayLabel.textContent = '¿Autobús solo ida?';
                    updateDestinations();
                    break;
                    
                case 'flight_only':
                case 'flight_hotel':
                    oneWayLabel.textContent = '¿Vuelo solo ida?';
                    updateDestinations();
                    break;
                    
                default:
                    oneWayLabel.textContent = '¿Vuelo solo ida?';
            }
            
            // Actualizar fechas disponibles
            updateAvailableDates();
        }

        // Función para cargar destinos de hoteles
        function loadHotelDestinations() {
            const destinationSelect = document.getElementById('destination');
            destinationSelect.innerHTML = '<option value="">Cargando destinos de hoteles...</option>';
            
            fetch('get_hotel_destinations.php')
                .then(response => response.json())
                .then(data => {
                    destinationSelect.innerHTML = '<option value="">Seleccionar destino</option>';
                    data.forEach(dest => {
                        const option = document.createElement('option');
                        option.value = dest;
                        option.textContent = dest;
                        destinationSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    destinationSelect.innerHTML = '<option value="">Error al cargar destinos</option>';
                });
        }

        // Función para vuelo solo ida
        function toggleOneWay() {
            const oneWayCheckbox = document.getElementById('one_way');
            const returnDateInput = document.getElementById('return_date');

            if (oneWayCheckbox.checked) {
                returnDateInput.disabled = true;
                returnDateInput.value = '';
            } else {
                returnDateInput.disabled = false;
            }
        }

        // Función para actualizar destinos basado en el origen seleccionado
        function updateDestinations() {
            const origin = document.getElementById('origin').value;
            const destinationSelect = document.getElementById('destination');
            const packageType = document.getElementById('package_type').value;

            if (origin) {
                destinationSelect.innerHTML = '<option value="">Cargando destinos...</option>';
                destinationSelect.disabled = true;
                
                fetch(`get_destinations.php?origin=${encodeURIComponent(origin)}&package_type=${packageType}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error en la respuesta del servidor');
                        }
                        return response.json();
                    })
                    .then(data => {
                        destinationSelect.innerHTML = '<option value="">Seleccionar destino</option>';
                        data.forEach(dest => {
                            const option = document.createElement('option');
                            option.value = dest;
                            option.textContent = dest;
                            destinationSelect.appendChild(option);
                        });
                        destinationSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        destinationSelect.innerHTML = '<option value="">Error al cargar destinos</option>';
                        destinationSelect.disabled = false;
                    });
            } else {
                destinationSelect.innerHTML = '<option value="">Primero seleccione origen</option>';
                destinationSelect.disabled = true;
            }
        }

        // Función para actualizar fechas disponibles
        function updateAvailableDates() {
            const origin = document.getElementById('origin').value;
            const destination = document.getElementById('destination').value;
            const packageType = document.getElementById('package_type').value;
            const departureDateInput = document.getElementById('departure_date');
            const returnDateInput = document.getElementById('return_date');
            const departureDatesInfo = document.getElementById('departure_dates_info');
            const returnDatesInfo = document.getElementById('return_dates_info');

            // Limpiar información previa
            departureDatesInfo.style.display = 'none';
            returnDatesInfo.style.display = 'none';

            // Habilitar campos de fecha por defecto
            departureDateInput.disabled = false;
            returnDateInput.disabled = document.getElementById('one_way').checked;

            // Si es hotel only, no necesitamos consultar fechas específicas
            if (packageType === 'hotel_only') {
                if (destination) {
                    const today = new Date().toISOString().split('T')[0];
                    departureDateInput.min = today;
                    returnDateInput.min = today;
                    
                    departureDatesInfo.innerHTML = '✅ Puede seleccionar cualquier fecha futura';
                    departureDatesInfo.style.display = 'block';
                    departureDatesInfo.className = 'dates-info available';
                    
                    returnDatesInfo.innerHTML = '✅ Puede seleccionar cualquier fecha futura';
                    returnDatesInfo.style.display = 'block';
                    returnDatesInfo.className = 'dates-info available';
                }
                return;
            }

            // Para vuelos y autobuses, necesitamos origen y destino
            if (!origin || !destination) {
                departureDateInput.disabled = true;
                returnDateInput.disabled = true;
                return;
            }

            // Consultar fechas disponibles
            fetch(`get_available_dates.php?origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(destination)}&package_type=${packageType}`)
                .then(response => response.json())
                .then(data => {
                    if (data.departure_dates && data.departure_dates.length > 0) {
                        // Mostrar fechas disponibles para ida
                        departureDatesInfo.innerHTML = `✅ Fechas disponibles: ${data.departure_dates.join(', ')}`;
                        departureDatesInfo.style.display = 'block';
                        departureDatesInfo.className = 'dates-info available';
                        
                        // Configurar el input de fecha de ida
                        departureDateInput.min = data.departure_dates[0];
                        departureDateInput.max = data.departure_dates[data.departure_dates.length - 1];
                        
                        if (data.return_dates && data.return_dates.length > 0) {
                            // Mostrar fechas disponibles para regreso
                            returnDatesInfo.innerHTML = `✅ Fechas de regreso disponibles: ${data.return_dates.join(', ')}`;
                            returnDatesInfo.style.display = 'block';
                            returnDatesInfo.className = 'dates-info available';
                            
                            // Configurar el input de fecha de regreso
                            returnDateInput.disabled = document.getElementById('one_way').checked;
                            returnDateInput.min = data.return_dates[0];
                            returnDateInput.max = data.return_dates[data.return_dates.length - 1];
                        } else {
                            returnDatesInfo.innerHTML = '❌ No hay fechas de regreso disponibles';
                            returnDatesInfo.style.display = 'block';
                            returnDatesInfo.className = 'dates-info not-available';
                            returnDateInput.disabled = true;
                        }
                    } else {
                        departureDatesInfo.innerHTML = '❌ No hay fechas disponibles para esta ruta';
                        departureDatesInfo.style.display = 'block';
                        departureDatesInfo.className = 'dates-info not-available';
                        departureDateInput.disabled = true;
                        returnDateInput.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // En caso de error, permitir seleccionar cualquier fecha pero mostrar advertencia
                    departureDatesInfo.innerHTML = '⚠️ No se pudieron cargar las fechas disponibles. Puede seleccionar cualquier fecha.';
                    departureDatesInfo.style.display = 'block';
                    departureDatesInfo.className = 'dates-info warning';
                    
                    returnDatesInfo.innerHTML = '⚠️ No se pudieron cargar las fechas de regreso. Puede seleccionar cualquier fecha.';
                    returnDatesInfo.style.display = 'block';
                    returnDatesInfo.className = 'dates-info warning';
                    
                    const today = new Date().toISOString().split('T')[0];
                    departureDateInput.min = today;
                    returnDateInput.min = today;
                });
        }

        // Inicializar el formulario al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar fechas mínimas
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('departure_date').min = today;
            document.getElementById('return_date').min = today;
            
            // Actualizar fecha mínima de regreso cuando cambia la fecha de ida
            document.getElementById('departure_date').addEventListener('change', function() {
                const returnDate = document.getElementById('return_date');
                returnDate.min = this.value;
                if (returnDate.value && returnDate.value < this.value) {
                    returnDate.value = this.value;
                }
            });

            // Ejecutar toggleFormFields para configurar el estado inicial
            toggleFormFields();
        });
    </script>

    <style>
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0;
        }
        
        .checkbox-container input[type="checkbox"] {
            margin: 0;
            width: auto;
            transform: scale(1.2);
        }
        
        .checkbox-container label {
            margin: 0;
            font-weight: normal;
            cursor: pointer;
        }
        
        #one_way_group {
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            border: 1px solid #e9ecef;
            margin-bottom: 1rem;
        }
        
        .dates-info {
            font-size: 0.8rem;
            margin-top: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
        }
        
        .dates-info.available {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .dates-info.not-available {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .dates-info.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .checkbox-container {
                justify-content: flex-start;
            }
        }
    </style>
</body>
</html>