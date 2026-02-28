import * as Location from '../models/Location.js';

export const getAll = async (req, res) => {
  const list = await Location.getAll();
  res.json(list);
};
