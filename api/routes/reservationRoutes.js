import { Router } from "express";
import { reserve } from "../controllers/reservationController.js";

const router = Router();

router.post("/", reserve);

export default router;
