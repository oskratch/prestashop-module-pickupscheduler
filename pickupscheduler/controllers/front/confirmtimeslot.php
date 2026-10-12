<?php
class pickupschedulerconfirmtimeslotModuleFrontController extends ModuleFrontController {

    public function postProcess() {
        $event_id = (int)Tools::getValue('event_id');
        $customer_id = (int)$this->context->customer->id;

        // Only a customer (or guest) that has reached the delivery step can hold a slot,
        // and the request must carry the shop's static token.
        if (!$customer_id || Tools::getValue('token') !== Tools::getToken(false)) {
            $this->jsonResponse(['success' => false, 'error' => 'forbidden']);
        }

        $time_slot = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'pickupscheduler_time_slots WHERE id = '.$event_id);
        if (!$time_slot || !$this->isBookable($time_slot)) {
            $this->jsonResponse(['success' => false, 'error' => 'unavailable']);
        }

        $reservation_minutes = max((int)Configuration::get('PICKUP_SCHEDULER_RESERVATION_MINUTES'), 1);
        $expires_at = date('Y-m-d H:i:s', strtotime('+' . $reservation_minutes . ' minutes'));

        $existing_reservation = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'pickupscheduler_time_slot_reservations WHERE customer_id = '.$customer_id.' AND is_confirmed = 0');

        // Clicking the slot the customer already holds just renews the hold.
        if ($existing_reservation && (int)$existing_reservation['time_slot_id'] === $event_id) {
            Db::getInstance()->update('pickupscheduler_time_slot_reservations', [
                'expires_at' => $expires_at,
            ], 'time_slot_id = '.$event_id.' AND customer_id = '.$customer_id.' AND is_confirmed = 0');

            $this->jsonResponse(['success' => true, 'expires_at' => $expires_at, 'expires_in' => $reservation_minutes * 60]);
        }

        // Atomic claim: only one request can flip is_reserved from 0 to 1.
        Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'pickupscheduler_time_slots SET is_reserved = 1 WHERE id = '.$event_id.' AND is_reserved = 0');
        if (Db::getInstance()->Affected_Rows() < 1) {
            $this->jsonResponse(['success' => false, 'error' => 'taken']);
        }

        $inserted = Db::getInstance()->insert('pickupscheduler_time_slot_reservations', [
            'time_slot_id' => $event_id,
            'customer_id' => $customer_id,
            'reserved_at' => date('Y-m-d H:i:s'),
            'expires_at' => $expires_at,
        ]);

        if (!$inserted) {
            // Never leave a slot locked without a reservation pointing at it.
            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'pickupscheduler_time_slots SET is_reserved = 0 WHERE id = '.$event_id);
            $this->jsonResponse(['success' => false, 'error' => 'unavailable']);
        }

        // Release the previous unconfirmed reservation only once the new one is in place.
        if ($existing_reservation) {
            $previous_id = (int)$existing_reservation['time_slot_id'];
            Db::getInstance()->delete('pickupscheduler_time_slot_reservations', 'time_slot_id = '.$previous_id.' AND customer_id = '.$customer_id.' AND is_confirmed = 0');
            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'pickupscheduler_time_slots SET is_reserved = 0 WHERE id = '.$previous_id);
        }

        $this->jsonResponse(['success' => true, 'expires_at' => $expires_at, 'expires_in' => $reservation_minutes * 60]);
    }

    /**
     * A slot can be booked if it hasn't started yet and falls after the preparation period.
     */
    private function isBookable(array $time_slot) {
        $preparation_days = (int)Configuration::get('PICKUP_SCHEDULER_PREPARATION_DAYS');
        $first_date = new DateTime('today');
        $first_date->modify('+' . $preparation_days . ' days');

        if ($time_slot['date'] < $first_date->format('Y-m-d')) {
            return false;
        }

        return new DateTime($time_slot['date'] . ' ' . $time_slot['start_time']) > new DateTime('now');
    }

    private function jsonResponse(array $data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
