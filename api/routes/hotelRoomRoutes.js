import { Router } from "express";
import * as RoomController from "../controllers/hotelRoomController.js";

const router = Router();

// GET /api/hotels/:hotelId/rooms
router.get("/hotel/:hotelId", RoomController.getByHotel);

// GET /api/rooms/:id
router.get("/:id", RoomController.getById);

// POST /api/rooms/
router.post("/", RoomController.create);

export default router;
