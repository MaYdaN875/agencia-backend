import { Router } from "express";
import {
  getAllFlights,
  getSingleFlight,
  createNewFlight,
  updateFlightById,
  deleteFlightById,
  getDestinations,
  getAvailableDates
} from "../controllers/flightController.js";

const router = Router();

router.get("/", getAllFlights);
router.get("/destinations", getDestinations);
router.get("/available-dates", getAvailableDates);
router.get("/:id", getSingleFlight);
router.post("/", createNewFlight);
router.put("/:id", updateFlightById);
router.delete("/:id", deleteFlightById);

export default router;