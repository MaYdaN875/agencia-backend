-- Crear tablas
CREATE TABLE agencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Modificar tabla flights para quitar llegada y usar solo salida
CREATE TABLE flights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    airline VARCHAR(255) NOT NULL,
    origin VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    departure_date DATE NOT NULL,
    departure_time TIME NOT NULL,
    duration_minutes INT NOT NULL, -- Duración en minutos
    price DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL,
    available_seats INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE buses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company VARCHAR(255) NOT NULL,
    origin VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    departure_date DATE NOT NULL,
    departure_time TIME NOT NULL,
    duration_minutes INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL,
    available_seats INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE hotels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(255),
    stars INT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE hotel_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT,
    room_type VARCHAR(100) NOT NULL,
    capacity INT NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    available_rooms INT NOT NULL,
    amenities TEXT,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
);

CREATE TABLE hotel_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_room_id INT,
    date DATE NOT NULL,
    available_rooms INT NOT NULL,
    FOREIGN KEY (hotel_room_id) REFERENCES hotel_rooms(id) ON DELETE CASCADE
);

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_code VARCHAR(20) UNIQUE NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20),
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reservation_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT,
    service_type ENUM('flight', 'bus', 'hotel') NOT NULL,
    service_id INT NOT NULL,
    passengers_data JSON,
    room_type VARCHAR(100),
    check_in DATE,
    check_out DATE,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
);

CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar datos de ejemplo
INSERT INTO admin_users (username, password, full_name, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador Principal', 'admin@agencia.com');

INSERT INTO agencies (name, email, phone, address) VALUES 
('Viajes Express', 'info@viajesexpress.com', '+1234567890', 'Av. Principal 123, Ciudad'),
('Turismo Global', 'contacto@turismoglobal.com', '+0987654321', 'Calle Central 456, Ciudad');

-- Estados mexicanos para vuelos
INSERT INTO flights (airline, origin, destination, departure_date, departure_time, duration_minutes, price, capacity, available_seats) VALUES 
('AeroMéxico', 'Ciudad de México', 'Cancún', '2024-03-15', '08:00:00', 150, 2500.00, 180, 150),
('AeroMéxico', 'Ciudad de México', 'Guadalajara', '2024-03-15', '10:30:00', 90, 1800.00, 180, 120),
('Volaris', 'Guadalajara', 'Cancún', '2024-03-16', '14:30:00', 120, 2200.00, 150, 100),
('Volaris', 'Guadalajara', 'Monterrey', '2024-03-16', '16:45:00', 80, 1500.00, 150, 130),
('Interjet', 'Monterrey', 'Ciudad de México', '2024-03-17', '11:15:00', 95, 1700.00, 160, 140),
('Interjet', 'Monterrey', 'Puerto Vallarta', '2024-03-17', '13:00:00', 110, 1900.00, 160, 110);

INSERT INTO buses (company, origin, destination, departure_date, departure_time, duration_minutes, price, capacity, available_seats) VALUES 
('ETN', 'Ciudad de México', 'Guadalajara', '2024-03-16', '22:00:00', 480, 800.00, 40, 35),
('Primera Plus', 'Guadalajara', 'Puerto Vallarta', '2024-03-19', '08:30:00', 210, 450.00, 45, 40),
('Futura', 'Monterrey', 'Cancún', '2024-03-22', '20:00:00', 840, 1200.00, 35, 30),
('ETN', 'Cancún', 'Ciudad de México', '2024-03-25', '18:00:00', 480, 800.00, 40, 38);

INSERT INTO hotels (name, location, address, phone, email, stars, description) VALUES 
('Grand Paradise Cancún', 'Cancún', 'Blvd. Kukulcan KM 16.5, Zona Hotelera', '+529988123456', 'reservaciones@grandparadise.com', 5, 'Lujoso resort todo incluido con vista al mar Caribe'),
('Sunset Resort Puerto Vallarta', 'Puerto Vallarta', 'Av. de las Garzas 123, Marina Vallarta', '+523221234567', 'info@sunsetresort.com', 4, 'Hotel boutique con spa y alberca infinita'),
('Mountain View Los Cabos', 'Los Cabos', 'Carretera Transpeninsular KM 19.5', '+526241234567', 'contact@mountainview.com', 4, 'Eco-resort con actividades al aire libre'),
('City Express Guadalajara', 'Guadalajara', 'Av. Vallarta 1234', '+523312345678', 'guadalajara@cityexpress.com', 3, 'Hotel de negocios en el centro de la ciudad');

INSERT INTO hotel_rooms (hotel_id, room_type, capacity, price_per_night, available_rooms, amenities) VALUES 
(1, 'Habitación Estándar', 2, 1200.00, 10, 'TV, A/C, Wi-Fi, Caja fuerte'),
(1, 'Suite Junior', 3, 2000.00, 5, 'TV, A/C, Wi-Fi, Caja fuerte, Mini bar, Vista al mar'),
(1, 'Suite Presidencial', 4, 3500.00, 2, 'TV, A/C, Wi-Fi, Caja fuerte, Mini bar, Vista al mar, Jacuzzi'),
(2, 'Habitación con Vista al Mar', 2, 1500.00, 8, 'TV, A/C, Wi-Fi, Balcón con vista al mar'),
(2, 'Suite Familiar', 4, 2500.00, 4, 'TV, A/C, Wi-Fi, 2 habitaciones, Cocina pequeña'),
(3, 'Bungalow Jardín', 2, 1800.00, 6, 'TV, A/C, Wi-Fi, Terraza privada, Hamaca'),
(4, 'Habitación Ejecutiva', 2, 800.00, 12, 'TV, A/C, Wi-Fi, Escritorio');

-- Procedimiento corregido para disponibilidad de habitaciones
DELIMITER $$

CREATE PROCEDURE InsertHotelAvailability()
BEGIN
    DECLARE start_dt DATE;
    DECLARE end_dt DATE;
    DECLARE current_dt DATE;
    DECLARE room_id INT;
    DECLARE available_count INT;
    DECLARE done INT DEFAULT FALSE;
    DECLARE cur CURSOR FOR SELECT id, available_rooms FROM hotel_rooms;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    SET start_dt = CURDATE();
    SET end_dt = DATE_ADD(CURDATE(), INTERVAL 60 DAY);
    
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO room_id, available_count;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        SET current_dt = start_dt;
        WHILE current_dt <= end_dt DO
            INSERT INTO hotel_availability (hotel_room_id, date, available_rooms) 
            VALUES (room_id, current_dt, available_count);
            SET current_dt = DATE_ADD(current_dt, INTERVAL 1 DAY);
        END WHILE;
    END LOOP;
    CLOSE cur;
END$$

DELIMITER ;

CALL InsertHotelAvailability();
DROP PROCEDURE InsertHotelAvailability;

-- -----------------------------------------------------
-- Tabla de Ubicaciones Globales
-- -----------------------------------------------------
CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Poblar la tabla de ubicaciones con los datos existentes
INSERT INTO locations (name)
SELECT DISTINCT location_name FROM (
    SELECT DISTINCT origin AS location_name FROM flights
    UNION
    SELECT DISTINCT destination AS location_name FROM flights
    UNION
    SELECT DISTINCT origin AS location_name FROM buses
    UNION
    SELECT DISTINCT destination AS location_name FROM buses
    UNION
    SELECT DISTINCT location AS location_name FROM hotels
) AS all_locations
WHERE location_name IS NOT NULL AND location_name != ''
ORDER BY location_name ASC;