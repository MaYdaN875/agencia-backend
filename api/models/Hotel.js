
import {connection} from '../database/config.js';

export const getAllHotels = () =>
  connection.execute('SELECT * FROM hotels');

export const getHotelById = (id) =>
  connection.execute('SELECT * FROM hotels WHERE id = ?', [id]);

export const createHotel = (hotel) =>
  connection.execute(
    `INSERT INTO hotels (name, location, address, phone, email, stars, description)
     VALUES (?, ?, ?, ?, ?, ?, ?)`,
    [
      hotel.name,
      hotel.location,
      hotel.address || null,
      hotel.phone || null,
      hotel.email || null,
      hotel.stars || 3,
      hotel.description || null
    ]
  );

export const updateHotel = (id, hotel) =>
  connection.execute(
    `UPDATE hotels 
     SET name = ?, location = ?, address = ?, phone = ?, email = ?, stars = ?, description = ?
     WHERE id = ?`,
    [
      hotel.name,
      hotel.location,
      hotel.address,
      hotel.phone,
      hotel.email,
      hotel.stars,
      hotel.description,
      id
    ]
  );

export const deleteHotel = (id) =>
  connection.execute('DELETE FROM hotels WHERE id = ?', [id]);
