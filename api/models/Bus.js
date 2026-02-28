import { connection } from '../database/config.js';

export const getAllBuses = async (filters = {}) => {
  let sql = 'SELECT * FROM buses WHERE 1=1';
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
  const [rows] = await connection.execute(sql, params);
  return rows;
};

export const getDestinationsByOrigin = async (origin) => {
  const [rows] = await connection.execute(
    `SELECT DISTINCT destination FROM buses 
     WHERE origin = ? AND departure_date >= CURDATE() AND available_seats > 0 
     ORDER BY destination`,
    [origin]
  );
  return rows.map((r) => r.destination);
};

export const getAvailableDates = async (origin, destination) => {
  const [outbound] = await connection.execute(
    `SELECT DISTINCT departure_date FROM buses 
     WHERE origin = ? AND destination = ? AND departure_date >= CURDATE() AND available_seats > 0 
     ORDER BY departure_date`,
    [origin, destination]
  );
  const [inbound] = await connection.execute(
    `SELECT DISTINCT departure_date FROM buses 
     WHERE origin = ? AND destination = ? AND departure_date >= CURDATE() AND available_seats > 0 
     ORDER BY departure_date`,
    [destination, origin]
  );
  const fmt = (d) => (d?.toISOString?.()?.slice(0, 10) ?? d);
  return {
    departure_dates: outbound.map((r) => fmt(r.departure_date)),
    return_dates: inbound.map((r) => fmt(r.departure_date))
  };
};

export const getBusById = async (id) => {
  const [rows] = await connection.execute('SELECT * FROM buses WHERE id = ?', [id]);
  return rows[0];
};

export const createBus = (bus) =>
  connection.execute(
    'INSERT INTO buses (origin, destination, price, date) VALUES (?, ?, ?, ?)',
    [bus.origin, bus.destination, bus.price, bus.date]
  );

export const updateBus = (id, bus) =>
  connection.execute(
    'UPDATE buses SET origin = ?, destination = ?, price = ?, date = ? WHERE id = ?',
    [bus.origin, bus.destination, bus.price, bus.date, id]
  );

export const deleteBus = (id) =>
  connection.execute('DELETE FROM buses WHERE id = ?', [id]);
