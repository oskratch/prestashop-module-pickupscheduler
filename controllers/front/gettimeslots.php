<?php
class pickupschedulergettimeslotsModuleFrontController extends ModuleFrontController {
    public function initContent() {
        parent::initContent();
        $this->ajaxProcessGetTimeSlots();
    }

    public function ajaxProcessGetTimeSlots() {

        $timeSlotManager = new TimeSlotManager();

        // eliminar tots els registres caducats
        $timeSlotManager->cleanExpiredTimeSlots();

        // crear els registres corresponents (per omplir els seguents 7 dies)
        $timeSlotManager->generateTimeSlots();        

        // netejo les reserves caducades
        $timeSlotManager->cleanExpiredReservations();
        
        // Mostro tots els slots disponibles tenint en compte els dies de preparació
        $preparationDays = (int)Configuration::get('PICKUP_SCHEDULER_PREPARATION_DAYS');
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        $startDate = clone $today;
        $startDate->modify('+' . $preparationDays . ' days');
        $timeSlots = Db::getInstance()->executeS('
            SELECT * FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots 
            WHERE date >= "' . $startDate->format('Y-m-d') . '" 
            AND is_reserved = 0
        ');

        // Si el client actual té un reservat que encara no ha expirat, el mostrem també
        $customerId = $this->context->customer->id;

        $reservedTimeSlot = Db::getInstance()->getRow('
            SELECT * FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots ts
            INNER JOIN ' . _DB_PREFIX_ . 'pickupscheduler_time_slot_reservations r ON ts.id = r.time_slot_id
            WHERE ts.is_reserved = 1 AND r.customer_id = ' . (int)$customerId . '
        ');

        if ($reservedTimeSlot) {
            $timeSlots[] = $reservedTimeSlot;
        }

        header('Content-Type: application/json');
        echo json_encode($timeSlots);
        exit;
    }
}