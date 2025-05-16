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
        
        $availableDays = (int)Configuration::get('PICKUP_SCHEDULER_AVAILABLE_DAYS');
        for ($i = 0; $i < $availableDays; $i++) {
            $currentDate = clone $today;
            $currentDate->modify('+' . $i . ' days');
            $dayOfWeek = $currentDate->format('l');

            foreach ($timeSlotsConfig as $config) {
                if ($config['day_of_week'] === $dayOfWeek) {
                    $startTime = new DateTime($currentDate->format('Y-m-d') . ' ' . $config['start_time']);
                    $endTime = new DateTime($currentDate->format('Y-m-d') . ' ' . $config['end_time']);
                    while ($startTime < $endTime) {
                        $nextTime = clone $startTime;
                        $nextTime->modify('+' . $config['interval_minutes'] . ' minutes');
                        if ($nextTime <= $endTime && ($currentDate->format('Y-m-d') !== date('Y-m-d') || $nextTime >= new DateTime('now'))) {
                            $existingSlot = Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots WHERE date = "' . $currentDate->format('Y-m-d') . '" AND start_time = "' . $startTime->format('H:i:s') . '" AND end_time = "' . $nextTime->format('H:i:s') . '"');
                            if (!$existingSlot) {
                                Db::getInstance()->insert('pickupscheduler_time_slots', [
                                    'date' => $currentDate->format('Y-m-d'),
                                    'start_time' => $startTime->format('H:i:s'),
                                    'end_time' => $nextTime->format('H:i:s')
                                ]);
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