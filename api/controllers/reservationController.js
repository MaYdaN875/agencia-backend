import { createReservation } from "../models/Reservations.js";
import * as Availability from "../models/Availability.js";

export const reserve = async (req, res) => {
  const { user_id, room_id, check_in_date, check_out_date, quantity } = req.body;

  try {
    const start = new Date(check_in_date);
    const end = new Date(check_out_date);

    // recorrer todas las noches de la estancia
    while (start < end) {
      const date = start.toISOString().split("T")[0];

      const [rows] = await Availability.getAvailabilityByDate(room_id, date);
      if (!rows.length || rows[0].available_rooms < quantity) {
        return res.status(400).json({
          message: `No availability on ${date}`
        });
      }

      await Availability.updateAvailability(room_id, date, quantity);

      start.setDate(start.getDate() + 1);
    }

    const nights = Math.ceil((end - new Date(check_in_date)) / (1000 * 60 * 60 * 24));
    const total_price = rows[0].price_per_night * quantity * nights;

    await createReservation({
      user_id,
      hotel_room_id: room_id,
      check_in_date,
      check_out_date,
      total_price
    });

    res.json({ message: "Reservation successful!", total_price });

  } catch (error) {
    console.log(error);
    res.status(500).json({ message: "Error processing reservation" });
  }
};
