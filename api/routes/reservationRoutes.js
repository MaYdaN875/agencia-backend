import { Router } from "express";
import {
  reserve,
  createBooking,
  getBookingById,
  getBookingDetails,
  addHotelToBooking
} from "../controllers/reservationController.js";

const router = Router();

router.post("/", reserve);
router.post("/booking", createBooking);
router.post("/:id/hotel", addHotelToBooking);
router.get("/:id/details", getBookingDetails);
router.get("/:id", getBookingById);

export default router;
