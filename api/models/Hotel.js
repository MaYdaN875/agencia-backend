
import {connection} from '../database/config.js';

export const getAllHotels = async (filters = {}) => {
  let sql = 'SELECT * FROM hotels WHERE 1=1';
  const params = [];
  if (filters.location) {
    sql += ' AND location LIKE ?';
    params.push(`%${filters.location}%`);
  }
  const [rows] = await connection.execute(sql, params);
  return rows;
};

export const getDistinctLocations = async () => {
  const [rows] = await connection.execute(
    'SELECT DISTINCT location FROM hotels ORDER BY location'
  );
  return rows.map((r) => r.location);
};

export const getHotelsWithRooms = async (location, minCapacity) => {
  const [rows] = await connection.execute(
    `SELECT h.*, hr.room_type, hr.capacity, hr.price_per_night, hr.id AS room_id 
     FROM hotels h 
     JOIN hotel_rooms hr ON h.id = hr.hotel_id 
     WHERE h.location LIKE ? AND hr.capacity >= ?`,
    [`%${location}%`, Number(minCapacity) || 0]
  );
  return rows;
};

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
