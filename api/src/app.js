import express from 'express';
import flightRoutes from './routes/flightRoutes.js';

const app = express();
app.use(express.json());

// Rutas
app.use('/api/flights', flightRoutes);

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`Servidor escuchando en puerto ${PORT}`));

export default app;