<?php
class pickupschedulergettimeslotsModuleFrontController extends ModuleFrontController {
    public function initContent() {
        parent::initContent();
        $this->ajaxProcessGetTimeSlots();
    }

    public function ajaxProcessGetTimeSlots() {

        $timeSlotManager = new TimeSlotManager();

        // delete all expired records
        $timeSlotManager->cleanExpiredTimeSlots();

        // create the corresponding records (to fill the next 7 days)
        TimeSlotManager::generateTimeSlots();

        // clean expired reservations
        $timeSlotManager->cleanExpiredReservations();
        
        // Show all available slots taking into account preparation days
        $preparationDays = (int)Configuration::get('PICKUP_SCHEDULER_PREPARATION_DAYS');
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        $startDate = clone $today;
        $startDate->modify('+' . $preparationDays . ' days');
        
        $now = new DateTime();
        $todayStr = $now->format('Y-m-d');
        $nowTimeStr = $now->format('H:i:s');

        // Check if the current day (with preparation days) has available slots that haven't started yet
        $currentAvailableSlots = Db::getInstance()->getValue('
            SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots
            WHERE date = "' . $startDate->format('Y-m-d') . '"
            AND is_reserved = 0
            AND (date != "' . $todayStr . '" OR start_time >= "' . $nowTimeStr . '")
        ');

        // If there are no slots available for today, skip to the next day
        if ($currentAvailableSlots == 0) {
            $startDate->modify('+1 day');
        }

        $timeSlots = Db::getInstance()->executeS('
            SELECT * FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots
            WHERE date >= "' . $startDate->format('Y-m-d') . '"
            AND is_reserved = 0
            AND (date != "' . $todayStr . '" OR start_time >= "' . $nowTimeStr . '")
        ');

        // If the current customer has a reservation that hasn't expired and isn't confirmed yet, show it too
        $customerId = $this->context->customer->id;

        $reservedTimeSlot = Db::getInstance()->getRow('
            SELECT * FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots ts
            INNER JOIN ' . _DB_PREFIX_ . 'pickupscheduler_time_slot_reservations r ON ts.id = r.time_slot_id
            WHERE ts.is_reserved = 1 AND r.customer_id = ' . (int)$customerId . '  AND r.is_confirmed = 0
        ');

        if ($reservedTimeSlot) {
            $timeSlots[] = $reservedTimeSlot;
        }

        header('Content-Type: application/json');
        echo json_encode($timeSlots);
        exit;
    }
}