import {connection} from '../database/config.js';

export const generateAvailability = async (room_id, available_rooms) => {
  const startDate = new Date();
  const endDate = new Date();
  endDate.setDate(endDate.getDate() + 60); // 60 días

  let promises = [];

  for (let i = 0; i <= 60; i++) {
    const date = new Date(startDate);
    date.setDate(startDate.getDate() + i);

    promises.push(
      connection.execute(
        `INSERT INTO hotel_availability (hotel_room_id, date, available_rooms)
         VALUES (?, ?, ?)`,
        [room_id, date.toISOString().split("T")[0], available_rooms]
      )
    );
  }

  await Promise.all(promises);
};
