import {connection} from '../database/config.js';

export const createReservation = (reservation) =>
  connection.execute(
    `INSERT INTO reservations 
     (user_id, hotel_room_id, check_in_date, check_out_date, total_price)
     VALUES (?, ?, ?, ?, ?)`,
    [
      reservation.user_id,
      reservation.hotel_room_id,
      reservation.check_in_date,
      reservation.check_out_date,
      reservation.total_price
    ]
  );