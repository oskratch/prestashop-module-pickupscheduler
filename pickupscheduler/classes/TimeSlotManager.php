<?php

class TimeSlotManager {

    public function cleanAllTimeSlots() {
        Db::getInstance()->delete('pickupscheduler_time_slots', 'is_reserved = 0');
    }

    public function cleanExpiredTimeSlots() {
        Db::getInstance()->delete('pickupscheduler_time_slots', 'is_reserved = 0 AND date < "' . date('Y-m-d') . '"');
    }

    public static function generateTimeSlots() {
        $timeSlotsConfig = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots_config');
        $today = new DateTime();
        $today->setTime(0, 0, 0);

        $availableDays = max(min((int)Configuration::get('PICKUP_SCHEDULER_AVAILABLE_DAYS'), 10), 1); // Entre 1 y 10 días para prevenir problemas de memoria
        $lastDate = clone $today;
        $lastDate->modify('+' . ($availableDays - 1) . ' days');

        // Fetch all slots already generated in the window in a single query,
        // instead of one existence check per potential slot.
        $existingSlots = Db::getInstance()->executeS('
            SELECT date, start_time, end_time FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots
            WHERE date BETWEEN "' . $today->format('Y-m-d') . '" AND "' . $lastDate->format('Y-m-d') . '"
        ');
        $existingSlotKeys = [];
        foreach ($existingSlots as $slot) {
            $existingSlotKeys[$slot['date'] . ' ' . $slot['start_time'] . ' ' . $slot['end_time']] = true;
        }

        for ($i = 0; $i < $availableDays; $i++) {
            $currentDate = clone $today;
            $currentDate->modify('+' . $i . ' days');
            $dayOfWeek = $currentDate->format('l');

            foreach ($timeSlotsConfig as $config) {
                if ($config['day_of_week'] === $dayOfWeek) {
                    $intervalMinutes = max((int)$config['interval_minutes'], 4); // Mínimo 4 minutos para evitar bucles infinitos
                    $startTime = new DateTime($currentDate->format('Y-m-d') . ' ' . $config['start_time']);
                    $endTime = new DateTime($currentDate->format('Y-m-d') . ' ' . $config['end_time']);
                    while ($startTime < $endTime) {
                        $nextTime = clone $startTime;
                        $nextTime->modify('+' . $intervalMinutes . ' minutes');
                        if ($nextTime <= $endTime && ($currentDate->format('Y-m-d') !== date('Y-m-d') || $nextTime >= new DateTime('now'))) {
                            $slotKey = $currentDate->format('Y-m-d') . ' ' . $startTime->format('H:i:s') . ' ' . $nextTime->format('H:i:s');
                            if (!isset($existingSlotKeys[$slotKey])) {
                                // Db::INSERT_IGNORE relies on the UNIQUE(date, start_time, end_time)
                                // constraint to stay safe if two requests generate the same slot concurrently.
                                Db::getInstance()->insert('pickupscheduler_time_slots', [
                                    'date' => $currentDate->format('Y-m-d'),
                                    'start_time' => $startTime->format('H:i:s'),
                                    'end_time' => $nextTime->format('H:i:s')
                                ], false, true, Db::INSERT_IGNORE);
                                $existingSlotKeys[$slotKey] = true;
                            }
                        }
                        $startTime = $nextTime;
                    }
                }
            }
        }
    }
    
    public function cleanExpiredReservations() {
        $expiredReservations = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slot_reservations WHERE expires_at < NOW() AND is_confirmed = 0');
        foreach ($expiredReservations as $reservation) {
            Db::getInstance()->delete('pickupscheduler_time_slot_reservations', 'time_slot_id = ' . $reservation['time_slot_id']);
            Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'pickupscheduler_time_slots SET is_reserved = 0 WHERE id = ' . $reservation['time_slot_id']);
        }
    }

    public function getUnconfirmedReservation($customer_id) {
        $reservation = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slot_reservations WHERE customer_id = ' . (int)$customer_id . ' AND is_confirmed = 0');
        return $reservation ? $reservation : null;
    }
}