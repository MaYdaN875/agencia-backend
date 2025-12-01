import { Router } from "express";
import {
  getAllFlights,
  getSingleFlight,
  createNewFlight,
  updateFlightById,
  deleteFlightById
} from "../controllers/flightController.js";

const router = Router();

router.get("/", getAllFlights);
router.get("/:id", getSingleFlight);
router.post("/", createNewFlight);
router.put("/:id", updateFlightById);
router.delete("/:id", deleteFlightById);

export default router;