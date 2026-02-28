import { createReservation } from "../models/Reservations.js";
import * as Availability from "../models/Availability.js";
import * as Booking from "../models/reservationBooking.js";

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

/** Reservación estilo agencia: reservation + reservation_details + actualizar asientos */
export const createBooking = async (req, res) => {
  try {
    const { customer_name, customer_email, customer_phone, total_price, details } = req.body;
    if (!customer_name || !customer_email || !details?.length) {
      return res.status(400).json({ message: "Faltan customer_name, customer_email o details" });
    }
    const total = total_price ?? details.reduce((s, d) => s + Number(d.subtotal || 0), 0);
    const result = await Booking.createBooking({
      customer_name,
      customer_email,
      customer_phone: customer_phone || null,
      total_price: total,
      details
    });
    res.status(201).json(result);
  } catch (error) {
    console.error(error);
    res.status(500).json({ message: "Error al crear la reservación" });
  }
};

export const getBookingById = async (req, res) => {
  const row = await Booking.getReservationById(req.params.id);
  if (!row) return res.status(404).json({ message: "Reserva no encontrada" });
  res.json(row);
};

export const getBookingDetails = async (req, res) => {
  const rows = await Booking.getReservationDetails(req.params.id);
  res.json(rows);
};

export const addHotelToBooking = async (req, res) => {
  try {
    const reservationId = req.params.id;
    const existing = await Booking.getReservationById(reservationId);
    if (!existing) return res.status(404).json({ message: "Reserva no encontrada" });
    const { hotel_id, room_id, room_type, check_in, check_out, subtotal } = req.body;
    if (!hotel_id || !room_id || !check_in || !check_out || subtotal == null) {
      return res.status(400).json({ message: "Faltan hotel_id, room_id, room_type, check_in, check_out o subtotal" });
    }
    await Booking.addHotelToReservation(reservationId, {
      hotel_id,
      room_id,
      room_type: room_type || "",
      check_in,
      check_out,
      subtotal
    });
    res.json({ message: "Hotel añadido a la reserva" });
  } catch (error) {
    console.error(error);
    res.status(500).json({ message: "Error al añadir hotel" });
  }
};
