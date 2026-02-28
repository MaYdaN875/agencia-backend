
import { connection } from '../database/config.js';



export const getFlights = async (filters = {}) => {
  let sql = 'SELECT * FROM flights WHERE 1=1';
  const params = [];
  if (filters.origin) {
    sql += ' AND origin LIKE ?';
    params.push(`%${filters.origin}%`);
  }
  if (filters.destination) {
    sql += ' AND destination LIKE ?';
    params.push(`%${filters.destination}%`);
  }
  if (filters.departure_date) {
    sql += ' AND departure_date = ?';
    params.push(filters.departure_date);
  }
  if (filters.min_seats != null) {
    sql += ' AND available_seats >= ?';
    params.push(Number(filters.min_seats));
  }
  sql += ' ORDER BY departure_date, departure_time';
  const [rows] = await connection.query(sql, params);
  return rows;
};

export const getDestinationsByOrigin = async (origin) => {
  const [rows] = await connection.query(
    `SELECT DISTINCT destination FROM flights 
     WHERE origin = ? AND departure_date >= CURDATE() AND available_seats > 0 
     ORDER BY destination`,
    [origin]
  );
  return rows.map((r) => r.destination);
};

export const getAvailableDates = async (origin, destination) => {
  const [outbound] = await connection.query(
    `SELECT DISTINCT departure_date FROM flights 
     WHERE origin = ? AND destination = ? AND departure_date >= CURDATE() AND available_seats > 0 
     ORDER BY departure_date`,
    [origin, destination]
  );
  const [inbound] = await connection.query(
    `SELECT DISTINCT departure_date FROM flights 
     WHERE origin = ? AND destination = ? AND departure_date >= CURDATE() AND available_seats > 0 
     ORDER BY departure_date`,
    [destination, origin]
  );
  return {
    departure_dates: outbound.map((r) => r.departure_date?.toISOString?.()?.slice(0, 10) ?? r.departure_date),
    return_dates: inbound.map((r) => r.departure_date?.toISOString?.()?.slice(0, 10) ?? r.departure_date)
  };
};

export const getFlightById = async (id) => {
  const [rows] = await connection.query(
    "SELECT * FROM flights WHERE id = ?",
    [id]
  );
  return rows[0];
};

export const createFlight = async (data) => {
  const [result] = await connection.query(
    `INSERT INTO flights (airline, origin, destination, departure_date, departure_time,
      duration_minutes, price, capacity, available_seats)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      data.airline,
      data.origin,
      data.destination,
      data.departure_date,
      data.departure_time,
      data.duration_minutes,
      data.price,
      data.capacity,
      data.available_seats
    ]
  );
  return result.insertId;
};

export const updateFlight = async (id, data) => {
  const [result] = await connection.query(
    `UPDATE flights SET airline = ?, origin = ?, destination = ?, departure_date = ?,
     departure_time = ?, duration_minutes = ?, price = ?, capacity = ?, available_seats = ?
     WHERE id = ?`,
    [
      data.airline,
      data.origin,
      data.destination,
      data.departure_date,
      data.departure_time,
      data.duration_minutes,
      data.price,
      data.capacity,
      data.available_seats,
      id
    ]
  );
  return result.affectedRows;
};

export const deleteFlight = async (id) => {
  const [result] = await connection.query(
    "DELETE FROM flights WHERE id = ?",
    [id]
  );
  return result.affectedRows;
};

