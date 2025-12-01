import express from "express";
import dotenv from "dotenv";
import { connection } from "./database/config.js";
import flightRoutes from './routes/flightRoutes.js';
dotenv.config();

const app = express();
const PORT = process.env.PORT || 3000;

connection();

app.use(express.json());

// Rutas
app.use('/api/flights', flightRoutes);

app.listen(PORT, () => console.log(`Servidor escuchando en puerto ${PORT}`));

export default app;