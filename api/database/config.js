import mysql from "mysql2/promise";

let connection = async () => {
  try {
    connection = await mysql.createConnection({
      host: process.env.DB_HOST,
      user: process.env.DB_USER,
      password: process.env.DB_PASSWORD,
      database: process.env.DB_NAME,
      charset: 'utf8mb4',
    });

    console.log("📌 Conectado a MySQL exitosamente!");

  } catch (error) {
    console.log("⏳ MySQL no está listo, reintentando en 5s...");
    setTimeout(connection, 5000);
  }
};

// Exportamos también la conexión si la quieres usar en modelos
export { connection };
