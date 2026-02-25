import * as Room from "../models/hotelRoom.js";
import { generateAvailability } from "../utils/availabilityGenerator.js";

export const create = async (req, res) => {
  try {
    const [result] = await Room.createRoom(req.body);
    const room_id = result.insertId;

    await generateAvailability(room_id, req.body.available_rooms);

    res.json({ message: "Room created with initial availability" });

  } catch (error) {
    console.log(error);
    res.status(500).json({ message: "Error creating room" });
  }
};

export const getByHotel = async (req, res) => {
  const [rows] = await Room.getRoomsByHotel(req.params.hotelId);
  res.json(rows);
};

export const getById = async (req, res) => {
  const [rows] = await Room.getRoomById(req.params.id);
  rows.length
    ? res.json(rows[0])
    : res.status(404).json({ message: "Room not found" });
};
