import * as Bus from '../models/Bus.js';

export const getDestinations = async (req, res) => {
  const origin = req.query.origin;
  if (!origin) return res.json([]);
  const list = await Bus.getDestinationsByOrigin(origin);
  res.json(list);
};

export const getAvailableDates = async (req, res) => {
  const { origin, destination } = req.query;
  if (!origin || !destination) {
    return res.json({ departure_dates: [], return_dates: [] });
  }
  const data = await Bus.getAvailableDates(origin, destination);
  res.json(data);
};

export const getAll = async (req, res) => {
  const filters = {
    origin: req.query.origin,
    destination: req.query.destination,
    departure_date: req.query.departure_date,
    min_seats: req.query.min_seats
  };
  const rows = await Bus.getAllBuses(filters);
  res.json(rows);
};

export const getById = async (req, res) => {
  const bus = await Bus.getBusById(req.params.id);
  bus ? res.json(bus) : res.status(404).json({ message: 'Bus not found' });
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
