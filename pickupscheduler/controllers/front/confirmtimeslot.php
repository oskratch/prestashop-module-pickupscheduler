<?php
class pickupschedulerconfirmtimeslotModuleFrontController extends ModuleFrontController {
   
    public function postProcess() {
        $event_id = Tools::getValue('event_id');
        $customer_id = Tools::getValue('customer_id');

        $time_slot = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'pickupscheduler_time_slots WHERE id = '.(int)$event_id);
        
        if ($time_slot['is_reserved'] == 0) {

            // Check if the customer has an existing unconfirmed reservation
            $existing_reservation = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'pickupscheduler_time_slot_reservations WHERE customer_id = '.(int)$customer_id.' AND is_confirmed = 0');
            
            if ($existing_reservation) {
                // Delete the existing reservation
                Db::getInstance()->delete('pickupscheduler_time_slot_reservations', 'time_slot_id = '.(int)$existing_reservation['time_slot_id']);
                
                // Update the time slot to set is_reserved to false
                Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'pickupscheduler_time_slots SET is_reserved = 0 WHERE id = '.(int)$existing_reservation['time_slot_id']);
            }

            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'pickupscheduler_time_slots SET is_reserved = 1 WHERE id = '.(int)$event_id);
            
            $expires_at = date('Y-m-d H:i:s', strtotime('+' . Configuration::get('PICKUP_SCHEDULER_RESERVATION_MINUTES') . ' minutes'));
            Db::getInstance()->insert('pickupscheduler_time_slot_reservations', array(
                'time_slot_id' => $time_slot['id'],
                'customer_id' => $customer_id,
                'reserved_at' => date('Y-m-d H:i:s'),
                'expires_at' => $expires_at
            ));

            $this->ajaxDie(json_encode(array('success' => true, 'expires_at' => $expires_at)));

        } else {
            $this->ajaxDie(json_encode(array('success' => false)));
        }
    }
}