import {connection} from '../database/config.js';

// 🔹 Crear habitación
export const createRoom = (room) =>
  connection.execute(
    `INSERT INTO hotel_rooms 
     (hotel_id, room_type, capacity, price_per_night, available_rooms, amenities)
     VALUES (?, ?, ?, ?, ?, ?)`,
    [
      room.hotel_id,
      room.room_type,
      room.capacity,
      room.price_per_night,
      room.available_rooms,
      room.amenities
    ]
  );

// 🔹 Obtener todas las habitaciones por hotel
export const getRoomsByHotel = (hotel_id) =>
  connection.execute(
    `SELECT * FROM hotel_rooms WHERE hotel_id = ?`,
    [hotel_id]
  );

// 🔹 Obtener una habitación por ID
export const getRoomById = (id) =>
  connection.execute(`SELECT * FROM hotel_rooms WHERE id = ?`, [id]);
