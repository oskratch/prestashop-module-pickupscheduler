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
        
        $show_all = Tools::getValue('show_all', false);
        $date_condition = $show_all ? '' : 'AND ts.date >= CURDATE()';

        $sql = 'SELECT r.*, ts.date, ts.start_time, ts.end_time, c.firstname, c.lastname, c.email, o.current_state
            FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slot_reservations r
            JOIN ' . _DB_PREFIX_ . 'pickupscheduler_time_slots ts ON r.time_slot_id = ts.id
            JOIN ' . _DB_PREFIX_ . 'customer c ON r.customer_id = c.id_customer
            JOIN ' . _DB_PREFIX_ . 'orders o ON r.order_id = o.id_order
            WHERE r.is_confirmed = 1 ' . $date_condition . '
            ORDER BY ts.date DESC, ts.start_time DESC';

        $reservations = Db::getInstance()->executeS($sql);

        foreach ($reservations as &$reservation) {
            $sql_state = 'SELECT osl.name
            FROM ' . _DB_PREFIX_ . 'order_state_lang osl
            WHERE osl.id_order_state = ' . (int)$reservation['current_state'] . ' AND osl.id_lang = ' . (int)$this->context->language->id;
            $state_name = Db::getInstance()->getValue($sql_state);
            $reservation['state_name'] = $state_name;
            $link = new Link();
            $reservation['link_order'] = $link->getAdminLink('AdminOrders', true, ['id_order' => $reservation['order_id'], 'vieworder' => '']);
        }

        $this->context->smarty->assign(array(
            'reservations' => $reservations,
            'module_dir' => $this->module->getPathUri(),
            '_token' => Tools::getAdminTokenLite('AdminCustomers')
        ));
        
        $this->setTemplate('reservations_list.tpl');
    }
}