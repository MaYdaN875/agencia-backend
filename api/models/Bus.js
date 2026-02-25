import {connection} from '../database/config.js';

export const getAllBuses = () =>
  connection.execute('SELECT * FROM buses');

export const getBusById = (id) =>
  connection.execute('SELECT * FROM buses WHERE id = ?', [id]);

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
