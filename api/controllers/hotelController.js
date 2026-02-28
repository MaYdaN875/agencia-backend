import * as Hotel from '../models/Hotel.js';

export const getAll = async (req, res) => {
  const filters = { location: req.query.location };
  const rows = await Hotel.getAllHotels(filters);
  res.json(rows);
};

export const getDestinations = async (req, res) => {
  const list = await Hotel.getDistinctLocations();
  res.json(list);
};

export const searchWithRooms = async (req, res) => {
  const location = req.query.location || '';
  const min_capacity = req.query.min_capacity || 1;
  const rows = await Hotel.getHotelsWithRooms(location, min_capacity);
  res.json(rows);
};

export const getById = async (req, res) => {
  const [rows] = await Hotel.getHotelById(req.params.id);
  rows.length
    ? res.json(rows[0])
    : res.status(404).json({ message: "Hotel not found" });
};

export const create = async (req, res) => {
  await Hotel.createHotel(req.body);
  res.json({ message: "Hotel created successfully" });
};

export const update = async (req, res) => {
  await Hotel.updateHotel(req.params.id, req.body);
  res.json({ message: "Hotel updated" });
};

export const remove = async (req, res) => {
  await Hotel.deleteHotel(req.params.id);
  res.json({ message: "Hotel deleted" });
};
