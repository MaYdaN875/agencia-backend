import { connection } from '../database/config.js';

export const getAll = async () => {
  const [rows] = await connection.query(
    'SELECT name FROM locations ORDER BY name ASC'
  );
  return rows.map((r) => r.name);
};
