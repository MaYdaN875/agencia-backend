
import { connection } from '../database/config.js';



export const getFlights = async () => {
  const [rows] = await connection.query("SELECT * FROM flights");
  return rows;
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

