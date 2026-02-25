import {connection} from '../database/config.js';

// Obtener disponibilidad por fecha específica
export const getAvailabilityByDate = (room_id, date) =>
  connection.execute(
    `SELECT * FROM hotel_availability 
     WHERE hotel_room_id = ? AND date = ?`,
    [room_id, date]
  );

// Actualizar disponibilidad (restar habitaciones)
export const updateAvailability = (room_id, date, quantity) =>
  connection.execute(
    `UPDATE hotel_availability 
     SET available_rooms = available_rooms - ?
     WHERE hotel_room_id = ? AND date = ? AND available_rooms >= ?`,
    [quantity, room_id, date, quantity]
  );
