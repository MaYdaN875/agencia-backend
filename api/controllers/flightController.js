// src/controllers/flightController.js
import * as FlightModel from '../models/Flight.js';

export const getDestinations = async (req, res) => {
  try {
    const origin = req.query.origin;
    if (!origin) return res.json([]);
    const list = await FlightModel.getDestinationsByOrigin(origin);
    res.json(list);
  } catch (error) {
    console.error(error);
    res.status(500).json({ message: 'Error getting destinations' });
  }
};

export const getAvailableDates = async (req, res) => {
  try {
    const { origin, destination } = req.query;
    if (!origin || !destination) {
      return res.json({ departure_dates: [], return_dates: [] });
    }
    const data = await FlightModel.getAvailableDates(origin, destination);
    res.json(data);
  } catch (error) {
    console.error(error);
    res.status(500).json({ message: 'Error getting available dates' });
  }
};


export const getAllFlights = async (req, res) => {
  try {
    const filters = {
      origin: req.query.origin,
      destination: req.query.destination,
      departure_date: req.query.departure_date,
      min_seats: req.query.min_seats
    };
    const flights = await FlightModel.getFlights(filters);
    res.json(flights);
  } catch (error) {
    console.error(error);
    res.status(500).json({ message: "Error getting flights" });
  }
};

export const getSingleFlight = async (req, res) => {
  try {
    const flight = await FlightModel.getFlightById(req.params.id);
    if (!flight) return res.status(404).json({ message: "Flight not found" });

    res.json(flight);
  } catch (error) {
    res.status(500).json({ message: "Error getting flight" });
  }
};

export const createNewFlight = async (req, res) => {
  try {
    const id = await FlightModel.createFlight(req.body);
    res.status(201).json({ id, message: "Flight created!" });
  } catch (error) {
    res.status(500).json({ message: "Error creating flight" });
  }
};

export const updateFlightById = async (req, res) => {
  try {
    const updated = await FlightModel.updateFlight(req.params.id, req.body);
    if (!updated) return res.status(404).json({ message: "Flight not found" });

    res.json({ message: "Flight updated!" });
  } catch (error) {
    res.status(500).json({ message: "Error updating flight" });
  }
};

export const deleteFlightById = async (req, res) => {
  try {
    const deleted = await FlightModel.deleteFlight(req.params.id);
    if (!deleted) return res.status(404).json({ message: "Flight not found" });

    res.json({ message: "Flight deleted!" });
  } catch (error) {
    res.status(500).json({ message: "Error deleting flight" });
  }
};
