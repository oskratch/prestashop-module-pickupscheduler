<?php

class AdminPickupOrdersController extends ModuleAdminController {
    public function __construct() {
        $this->table = 'pickupscheduler_time_slot_reservations';
        $this->lang = false;
        $this->identifier = 'time_slot_id';
        $this->list_no_link = true;
        $this->bootstrap = true;

        parent::__construct();
    }

    public function initContent() {
        parent::initContent();

        $this->getReservations();
    }

    private function getReservations() {
        $show_all = (bool)Tools::getValue('show_all', false);

        // Upcoming pickups are listed soonest first; the full history newest first.
        $date_condition = $show_all ? '' : 'AND ts.date >= "' . date('Y-m-d') . '"';
        $order_by = $show_all ? 'ts.date DESC, ts.start_time DESC' : 'ts.date ASC, ts.start_time ASC';

        $sql = 'SELECT r.time_slot_id, r.order_id, ts.date, ts.start_time, ts.end_time,
                c.firstname, c.lastname, c.email, o.reference, osl.name AS state_name, os.color AS state_color
            FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slot_reservations r
            JOIN ' . _DB_PREFIX_ . 'pickupscheduler_time_slots ts ON r.time_slot_id = ts.id
            JOIN ' . _DB_PREFIX_ . 'customer c ON r.customer_id = c.id_customer
            JOIN ' . _DB_PREFIX_ . 'orders o ON r.order_id = o.id_order
            LEFT JOIN ' . _DB_PREFIX_ . 'order_state os ON os.id_order_state = o.current_state
            LEFT JOIN ' . _DB_PREFIX_ . 'order_state_lang osl ON osl.id_order_state = o.current_state AND osl.id_lang = ' . (int)$this->context->language->id . '
            WHERE r.is_confirmed = 1 ' . $date_condition . '
            ORDER BY ' . $order_by;

        $reservations = Db::getInstance()->executeS($sql) ?: [];

        foreach ($reservations as &$reservation) {
            $reservation['start_time'] = substr($reservation['start_time'], 0, 5);
            $reservation['end_time'] = substr($reservation['end_time'], 0, 5);
            $reservation['link_order'] = $this->context->link->getAdminLink('AdminOrders', true, [], ['id_order' => (int)$reservation['order_id'], 'vieworder' => 1]);
        }
        unset($reservation);

        $base_link = $this->context->link->getAdminLink('AdminPickupOrders');

        $this->context->smarty->assign(array(
            'reservations' => $reservations,
            'reservations_total' => count($reservations),
            'show_all' => $show_all,
            'link_upcoming' => $base_link,
            'link_all' => $base_link . '&show_all=1',
        ));

        $this->setTemplate('reservations_list.tpl');
    }
}
