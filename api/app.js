import express from "express";
import cookieParser from "cookie-parser";
import cors from "cors";
import dotenv from "dotenv";
import { connection } from "./database/config.js";
import flightRoutes from './routes/flightRoutes.js';
import busRoutes from './routes/busRoutes.js';
import hotelRoutes from './routes/hotelRoutes.js';
import reservationRoutes from "./routes/reservationRoutes.js";
import hotelRoomRoutes from "./routes/hotelRoomRoutes.js";
import locationRoutes from "./routes/locationRoutes.js";
import authRoutes from "./routes/authRoutes.js";

dotenv.config();

const app = express();
const PORT = process.env.PORT || 3000;

connection();

app.use(express.json());
app.use(cookieParser());
app.use(cors({ origin: process.env.CORS_ORIGIN || true, credentials: true }));

// Rutas
app.use("/api/auth", authRoutes);
app.use('/api/flights', flightRoutes);
app.use('/api/buses', busRoutes);
app.use('/api/hotels', hotelRoutes);
app.use("/api/rooms", hotelRoomRoutes);
app.use("/api/locations", locationRoutes);
app.use("/api/reservations", reservationRoutes);


app.listen(PORT, () => console.log(`Servidor escuchando en puerto ${PORT}`));

export default app;