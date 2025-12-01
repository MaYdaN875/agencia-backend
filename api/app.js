import express from "express";
import cors from "cors";
import dotenv from "dotenv";
import mysql from "mysql2/promise";

dotenv.config();

const app = express();
app.use(express.json());
app.use(cors());

const db = await mysql.createConnection({
    host: process.env.DB_HOST || "db",
    user: process.env.DB_USER || "appuser",
    password: process.env.DB_PASSWORD || "apppassword",
    database: process.env.DB_NAME || "mi_app",
});

app.get("/api/test", async (req, res) => {
    const [rows] = await db.query("SELECT 'API funcionando con Docker y MySQL' AS mensaje");
    res.json(rows[0]);
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`API escuchando en puerto ${PORT}`));
