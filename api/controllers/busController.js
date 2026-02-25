import * as Bus from '../models/Bus.js';

export const getAll = async (req, res) => {
  const [rows] = await Bus.getAllBuses();
  res.json(rows);
};

export const getById = async (req, res) => {
  const [rows] = await Bus.getBusById(req.params.id);
  rows.length ? res.json(rows[0]) : res.status(404).json({ message: 'Bus not found' });
};

export const create = async (req, res) => {
  await Bus.createBus(req.body);
  res.json({ message: 'Bus created' });
};

export const update = async (req, res) => {
  await Bus.updateBus(req.params.id, req.body);
  res.json({ message: 'Bus updated' });
};

export const remove = async (req, res) => {
  await Bus.deleteBus(req.params.id);
  res.json({ message: 'Bus deleted' });
};
