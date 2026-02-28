import { connection } from '../database/config.js';

function generateReservationCode() {
  return 'RES' + Math.random().toString(36).substring(2, 10).toUpperCase();
}

export async function createBooking(reservation) {
  const code = generateReservationCode();
  const [result] = await connection.execute(
    `INSERT INTO reservations (reservation_code, customer_name, customer_email, customer_phone, total_price, status)
     VALUES (?, ?, ?, ?, ?, 'pending')`,
    [
      code,
      reservation.customer_name,
      reservation.customer_email,
      reservation.customer_phone || null,
      reservation.total_price
    ]
  );
  const reservationId = result.insertId;
  for (const d of reservation.details) {
    const passengersJson = typeof d.passengers_data === 'string'
      ? d.passengers_data
      : JSON.stringify(d.passengers_data || []);
    const passengerCount = Array.isArray(d.passengers_data)
      ? d.passengers_data.length
      : (d.passengers_data ? JSON.parse(passengersJson).length : 0);

    await connection.execute(
      `INSERT INTO reservation_details (reservation_id, service_type, service_id, passengers_data, subtotal)
       VALUES (?, ?, ?, ?, ?)`,
      [reservationId, d.service_type, d.service_id, passengersJson, d.subtotal]
    );

    if (d.service_type === 'flight') {
      await connection.execute(
        'UPDATE flights SET available_seats = available_seats - ? WHERE id = ?',
        [passengerCount, d.service_id]
      );
    }
    if (d.service_type === 'bus') {
      await connection.execute(
        'UPDATE buses SET available_seats = available_seats - ? WHERE id = ?',
        [passengerCount, d.service_id]
      );
    }
  }
  return { reservation_id: reservationId, reservation_code: code };
}

export async function getReservationById(id) {
  const [rows] = await connection.execute(
    'SELECT * FROM reservations WHERE id = ?',
    [id]
  );
  return rows[0];
}

export async function getReservationDetails(reservationId) {
  const [rows] = await connection.execute(
    'SELECT * FROM reservation_details WHERE reservation_id = ?',
    [reservationId]
  );
  return rows;
}

export async function addHotelToReservation(reservationId, payload) {
  const { hotel_id, room_id, room_type, check_in, check_out, subtotal } = payload;
  await connection.execute(
    `INSERT INTO reservation_details (reservation_id, service_type, service_id, room_type, check_in, check_out, subtotal)
     VALUES (?, 'hotel', ?, ?, ?, ?, ?)`,
    [reservationId, hotel_id, room_type, check_in, check_out, subtotal]
  );
  const [res] = await connection.execute(
    'SELECT total_price FROM reservations WHERE id = ?',
    [reservationId]
  );
  const newTotal = (res[0]?.total_price || 0) + Number(subtotal);
  await connection.execute(
    'UPDATE reservations SET total_price = ? WHERE id = ?',
    [newTotal, reservationId]
  );
  const start = new Date(check_in);
  const end = new Date(check_out);
  while (start < end) {
    const date = start.toISOString().slice(0, 10);
    await connection.execute(
      'UPDATE hotel_availability SET available_rooms = available_rooms - 1 WHERE hotel_room_id = ? AND date = ? AND available_rooms > 0',
      [room_id, date]
    );
    start.setDate(start.getDate() + 1);
  }
  return { ok: true };
}
