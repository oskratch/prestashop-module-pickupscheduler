<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'pickupscheduler/classes/TimeSlotManager.php';

class PickupScheduler extends Module {

    const DAYS_OF_WEEK = [
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes',
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado',
        'Sunday' => 'Domingo',
    ];

    public $tabs = [
        [
            'name' => 'Recogidas en tienda',
            'class_name' => 'AdminPickupOrders',
            'parent_class_name' => 'AdminParentOrders'
        ],
    ];

    public function __construct(){
        $this->name = 'pickupscheduler';
        $this->tab = 'shipping_logistics';
        $this->version = '1.3.0';
        $this->author = 'Oscar Periche - 4funkies';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Pickup Scheduler');
        $this->description = $this->l('Permite a los clientes seleccionar un tramo horario y un día disponibles para recoger su pedido en la tienda.');

        $this->confirmUninstall = $this->l('¿Estás seguro de que deseas desinstalar el módulo?');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => '9.99.99');
    }

    public function install(){
        if (!parent::install()
        || !$this->installTab()
        || !$this->registerHook('displayAfterCarrier')
        || !$this->registerHook('actionFrontControllerSetMedia')
        || !$this->registerHook('actionValidateOrderAfter')
        || !$this->registerHook('displayAdminOrder')
        || !$this->registerHook('displayOrderDetail')
        || !$this->registerHook('displayInvoice')) {
            return false;
        }

        Configuration::updateValue('PICKUP_SCHEDULER_CARRIER_ID', 0);
        Configuration::updateValue('PICKUP_SCHEDULER_RESERVATION_MINUTES', 20);
        Configuration::updateValue('PICKUP_SCHEDULER_PREPARATION_DAYS', 2);
        Configuration::updateValue('PICKUP_SCHEDULER_AVAILABLE_DAYS', 7);

        $sql = [];

        // Create the pickupscheduler_time_slots_config table
        $sql[] = "
            CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "pickupscheduler_time_slots_config` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `day_of_week` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
                `start_time` TIME NOT NULL,
                `end_time` TIME NOT NULL,
                `interval_minutes` INT NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;
        ";

        // Create the pickupscheduler_time_slots table
        $sql[] = "
            CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "pickupscheduler_time_slots` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `date` DATE NOT NULL,
                `start_time` TIME NOT NULL,
                `end_time` TIME NOT NULL,
                `is_reserved` BOOLEAN DEFAULT FALSE,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (`date`, `start_time`, `end_time`)
            ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;
        ";

        // Create the pickupscheduler_time_slot_reservations table
        $sql[] = "
            CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "pickupscheduler_time_slot_reservations` (
                `time_slot_id` INT NOT NULL,
                `customer_id` INT(10) UNSIGNED NOT NULL,
                `order_id` INT(10) UNSIGNED DEFAULT NULL,
                `reserved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `expires_at` TIMESTAMP NOT NULL,
                `is_confirmed` BOOLEAN DEFAULT FALSE,
                CONSTRAINT fk_pickupscheduler_time_slot_id FOREIGN KEY (`time_slot_id`) REFERENCES `" . _DB_PREFIX_ . "pickupscheduler_time_slots`(`id`) ON DELETE CASCADE,
                CONSTRAINT fk_pickupscheduler_customer_id FOREIGN KEY (`customer_id`) REFERENCES `" . _DB_PREFIX_ . "customer`(`id_customer`) ON DELETE CASCADE,
                CONSTRAINT fk_pickupscheduler_order_id FOREIGN KEY (`order_id`) REFERENCES `" . _DB_PREFIX_ . "orders`(`id_order`) ON DELETE CASCADE
            ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;
        ";

        // Execute SQL queries
        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    public function uninstall(){
        if (!parent::uninstall() || !$this->uninstallTab()) {
            return false;
        }
        return true;
    }

    public function enable($force_all = false) {
        return parent::enable($force_all)
            && $this->installTab()
        ;
    }

    public function disable($force_all = false) {
        return parent::disable($force_all)
            && $this->uninstallTab()
        ;
    }

    public function installTab() {
        $tabId = (int) Tab::getIdFromClassName('AdminPickupOrders');

        if (!$tabId) {
            $tab = new Tab();
        } else {
            $tab = new Tab($tabId);
        }

        $tab->active = 1;
        $tab->class_name = 'AdminPickupOrders';
        $tab->name = array();
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = $this->trans('Recogidas en tienda', array(), 'Modules.PickupScheduler.Admin', $lang['locale']);
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminParentOrders');
        $tab->module = $this->name;

        return $tab->save();

    }

    public function uninstallTab() {
        $tabId = (int) Tab::getIdFromClassName('AdminPickupOrders');

        if ($tabId) {
            $tab = new Tab($tabId);
            $tab->delete();
        }

        return true;

    }

    public function getContent() {
        $output = '';
        $errors = [];

        if (Tools::isSubmit('submitPickupSchedulerConfig')) {
            $errors = $this->processConfiguration();
            $output .= $errors
                ? $this->displayError($errors)
                : $this->displayConfirmation($this->l('La configuración se ha guardado correctamente.'));
        }

        $this->context->controller->addCSS($this->_path . 'assets/css/back/configure.css');
        $this->context->controller->addJS($this->_path . 'assets/js/back/time-slot-form.js');

        return $output . $this->renderConfiguration(!empty($errors));
    }

    /**
     * Validates and saves the whole configuration form. Nothing is saved if any field is invalid.
     *
     * @return string[] validation errors
     */
    private function processConfiguration() {
        $errors = [];

        $carrierId = (int)Tools::getValue('PICKUP_SCHEDULER_CARRIER_ID');
        $preparationDays = (int)Tools::getValue('PICKUP_SCHEDULER_PREPARATION_DAYS');
        $reservationMinutes = (int)Tools::getValue('PICKUP_SCHEDULER_RESERVATION_MINUTES');
        $availableDays = (int)Tools::getValue('PICKUP_SCHEDULER_AVAILABLE_DAYS');

        if ($preparationDays < 0) {
            $errors[] = $this->l('Los días de preparación no pueden ser negativos.');
        }
        if ($reservationMinutes < 1) {
            $errors[] = $this->l('El tiempo de reserva debe ser de al menos 1 minuto.');
        }
        if ($availableDays < 1 || $availableDays > 10) {
            $errors[] = $this->l('Los días con franjas disponibles deben estar entre 1 y 10.');
        }

        $schedule = [];
        foreach (self::DAYS_OF_WEEK as $day => $label) {
            $key = 'PICKUP_SCHEDULER_' . strtoupper($day);
            if (!Tools::getValue($key . '_ACTIVE')) {
                continue;
            }

            $startTime = (string)Tools::getValue($key . '_START_TIME');
            $endTime = (string)Tools::getValue($key . '_END_TIME');
            $intervalMinutes = (int)Tools::getValue($key . '_INTERVAL_MINUTES');

            if (!$this->isValidTime($startTime) || !$this->isValidTime($endTime)) {
                $errors[] = sprintf($this->l('%s: indica una hora de inicio y de fin válidas.'), $label);
                continue;
            }
            if (strtotime($endTime) <= strtotime($startTime)) {
                $errors[] = sprintf($this->l('%s: la hora de fin debe ser posterior a la de inicio.'), $label);
                continue;
            }
            if ($intervalMinutes < 4) {
                $errors[] = sprintf($this->l('%s: el intervalo mínimo es de 4 minutos.'), $label);
                continue;
            }

            $schedule[$day] = [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'interval_minutes' => $intervalMinutes,
            ];
        }

        if ($errors) {
            return $errors;
        }

        Configuration::updateValue('PICKUP_SCHEDULER_CARRIER_ID', $carrierId);
        Configuration::updateValue('PICKUP_SCHEDULER_PREPARATION_DAYS', $preparationDays);
        Configuration::updateValue('PICKUP_SCHEDULER_RESERVATION_MINUTES', $reservationMinutes);
        Configuration::updateValue('PICKUP_SCHEDULER_AVAILABLE_DAYS', $availableDays);

        $db = Db::getInstance();
        $db->delete('pickupscheduler_time_slots_config');
        foreach ($schedule as $day => $config) {
            $db->insert('pickupscheduler_time_slots_config', ['day_of_week' => $day] + $config);
        }

        // Rebuild the free slots from the new schedule; reserved ones are kept.
        $timeSlotManager = new TimeSlotManager();
        $timeSlotManager->cleanAllTimeSlots();
        TimeSlotManager::generateTimeSlots();

        return [];
    }

    private function isValidTime($time) {
        return (bool)preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $time);
    }

    private function renderConfiguration($fromPost) {
        $db = Db::getInstance();

        $carriers = Carrier::getCarriers(
            (int)$this->context->language->id,
            false,
            false,
            false,
            null,
            Carrier::ALL_CARRIERS
        );

        $savedConfig = [];
        foreach ($db->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots_config') as $row) {
            $savedConfig[$row['day_of_week']] = $row;
        }

        $days = [];
        foreach (self::DAYS_OF_WEEK as $day => $label) {
            $key = 'PICKUP_SCHEDULER_' . strtoupper($day);
            $saved = isset($savedConfig[$day]) ? $savedConfig[$day] : null;

            if ($fromPost) {
                $days[] = [
                    'key' => $key,
                    'label' => $label,
                    'active' => (bool)Tools::getValue($key . '_ACTIVE'),
                    'start_time' => (string)Tools::getValue($key . '_START_TIME'),
                    'end_time' => (string)Tools::getValue($key . '_END_TIME'),
                    'interval_minutes' => (int)Tools::getValue($key . '_INTERVAL_MINUTES'),
                ];
            } else {
                $days[] = [
                    'key' => $key,
                    'label' => $label,
                    'active' => $saved !== null,
                    'start_time' => $saved ? substr($saved['start_time'], 0, 5) : '10:00',
                    'end_time' => $saved ? substr($saved['end_time'], 0, 5) : '14:00',
                    'interval_minutes' => $saved ? (int)$saved['interval_minutes'] : 30,
                ];
            }
        }

        $settings = [];
        foreach (['CARRIER_ID', 'PREPARATION_DAYS', 'RESERVATION_MINUTES', 'AVAILABLE_DAYS'] as $name) {
            $field = 'PICKUP_SCHEDULER_' . $name;
            $settings[$name] = (int)($fromPost ? Tools::getValue($field) : Configuration::get($field));
        }
        // Show the carrier's current id even if it has been edited since it was configured.
        if (!$fromPost) {
            $settings['CARRIER_ID'] = $this->getPickupCarrierId();
        }

        $today = date('Y-m-d');
        $stats = [
            'upcoming_pickups' => (int)$db->getValue('
                SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slot_reservations r
                INNER JOIN ' . _DB_PREFIX_ . 'pickupscheduler_time_slots ts ON ts.id = r.time_slot_id
                WHERE r.is_confirmed = 1 AND ts.date >= "' . $today . '"'),
            'active_days' => count($savedConfig),
            'free_slots' => (int)$db->getValue('
                SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots
                WHERE is_reserved = 0 AND date >= "' . $today . '"'),
        ];

        $this->context->smarty->assign([
            'psch_form_action' => AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]) . '&token=' . Tools::getAdminTokenLite('AdminModules'),
            'psch_version' => $this->version,
            'psch_carriers' => $carriers,
            'psch_carrier_configured' => $this->getPickupCarrierId() > 0,
            'psch_settings' => $settings,
            'psch_days' => $days,
            'psch_stats' => $stats,
            'psch_pickups_link' => $this->context->link->getAdminLink('AdminPickupOrders'),
        ]);

        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    /**
     * PrestaShop gives a carrier a new id every time it is edited and keeps only its
     * id_reference. Returns the current id of the configured pickup carrier.
     */
    public function getPickupCarrierId() {
        $configuredId = (int)Configuration::get('PICKUP_SCHEDULER_CARRIER_ID');
        if (!$configuredId) {
            return 0;
        }

        $configured = new Carrier($configuredId);
        if (!Validate::isLoadedObject($configured)) {
            return 0;
        }

        if ($configured->deleted) {
            $current = Carrier::getCarrierByReference((int)$configured->id_reference);
            return $current ? (int)$current->id : 0;
        }

        return (int)$configured->id;
    }

    /**
     * Whether a carrier id (the current one or one stored on an older order) is the pickup carrier.
     */
    public function isPickupCarrier($carrierId) {
        $carrierId = (int)$carrierId;
        $pickupCarrierId = $this->getPickupCarrierId();
        if (!$carrierId || !$pickupCarrierId) {
            return false;
        }
        if ($carrierId === $pickupCarrierId) {
            return true;
        }

        $carrier = new Carrier($carrierId);
        $pickupCarrier = new Carrier($pickupCarrierId);

        return Validate::isLoadedObject($carrier)
            && (int)$carrier->id_reference === (int)$pickupCarrier->id_reference;
    }

    public function hookDisplayAfterCarrier($params){
        $preparationDays = (int)Configuration::get('PICKUP_SCHEDULER_PREPARATION_DAYS');

        $pickupSchedulerConfig = [
            'pickup_scheduler_carrier_id' => $this->getPickupCarrierId(),
            'weekends' => Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots_config WHERE day_of_week IN ("Saturday", "Sunday")') > 0 ? 1 : 0,
            'minTime' => (string)Db::getInstance()->getValue('SELECT MIN(start_time) FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots_config'),
            'maxTime' => (string)Db::getInstance()->getValue('SELECT MAX(end_time) FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots_config'),
            'minInterval' => (int)Db::getInstance()->getValue('SELECT MIN(interval_minutes) FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots_config'),
            'customer_id' => (int)$this->context->customer->id,
            'pickup_scheduler_preparation_days' => $preparationDays . ' ' . ($preparationDays == 1 ? 'día' : 'días'),
            'pickup_scheduler_reservation_minutes' => (int)Configuration::get('PICKUP_SCHEDULER_RESERVATION_MINUTES'),
            'get_time_slots_url' => $this->context->link->getModuleLink('pickupscheduler', 'gettimeslots'),
            'confirm_time_slot_url' => $this->context->link->getModuleLink('pickupscheduler', 'confirmtimeslot'),
            'pickup_scheduler_token' => Tools::getToken(false),
            'calendar_event_id_selected' => null,
            'calendar_event_date_selected' => null,
            'calendar_event_selected_expires_in' => 0,
        ];

        $timeSlotManager = new TimeSlotManager();
        $unconfirmedReservation = $timeSlotManager->getUnconfirmedReservation($pickupSchedulerConfig['customer_id']);

        if ($unconfirmedReservation !== null) {
            $timeSlot = Db::getInstance()->getRow('SELECT `date` FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots WHERE id = ' . (int)$unconfirmedReservation['time_slot_id']);
            $pickupSchedulerConfig['calendar_event_id_selected'] = (int)$unconfirmedReservation['time_slot_id'];
            $pickupSchedulerConfig['calendar_event_date_selected'] = $timeSlot ? $timeSlot['date'] : null;
            // Seconds left rather than a timestamp, so the countdown doesn't depend on the customer's clock or time zone.
            $pickupSchedulerConfig['calendar_event_selected_expires_in'] = max(strtotime($unconfirmedReservation['expires_at']) - time(), 0);
        }

        $this->context->smarty->assign($pickupSchedulerConfig);

        return $this->display(__FILE__, 'views/templates/front/displaySchedule.tpl');
    }

    public function hookActionFrontControllerSetMedia($params){
        if ('order' === $this->context->controller->php_self) {
            $this->context->controller->registerStylesheet('fullcalendar-css', $this->_path . 'assets/css/front/fullcalendar/fullcalendar.min.css',['media' => 'all']);
            $this->context->controller->registerStylesheet('fullcalendar-custom-css', $this->_path . 'assets/css/front/custom.css',['media' => 'all']);
            $this->context->controller->registerJavascript('moment-js', $this->_path . 'assets/js/front/fullcalendar/moment.min.js',['position' => 'bottom', 'priority' => 150]);
            $this->context->controller->registerJavascript('fullcalendar-js', $this->_path . 'assets/js/front/fullcalendar/fullcalendar.min.js', ['position' => 'bottom', 'priority' => 151]);
            $this->context->controller->registerJavascript('fullcalendar-locale-es-js', $this->_path . 'assets/js/front/fullcalendar/locale/es.js', ['position' => 'bottom', 'priority' => 152]);
            $this->context->controller->registerJavascript('delivery-schedule-js', $this->_path . 'assets/js/front/delivery-schedule.js',['position' => 'bottom', 'priority' => 153]);
        }
    }

    private function formatPickupDateEs($date) {
        $diasSemana = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
        $meses = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];

        $dateTime = new DateTime($date);

        return $diasSemana[(int)$dateTime->format('N')] . ', ' . $dateTime->format('d') . ' de ' . $meses[(int)$dateTime->format('n')] . ' de ' . $dateTime->format('Y');
    }

    /**
     * The email template only ships in Spanish: use the order's language when a
     * template exists for it, and fall back to Spanish otherwise.
     */
    private function getMailLanguageId($orderLangId) {
        $iso = Language::getIsoById((int)$orderLangId);
        if ($iso && is_dir(_PS_MODULE_DIR_ . $this->name . '/mails/' . $iso)) {
            return (int)$orderLangId;
        }

        $spanishId = (int)Language::getIdByIso('es');

        return $spanishId ?: (int)Configuration::get('PS_LANG_DEFAULT');
    }

    public function hookActionValidateOrderAfter($params) {
        $order = $params['order'];

        if (!$this->isPickupCarrier($order->id_carrier)) {
            return;
        }

        $customerId = (int)$order->id_customer;
        $orderId = (int)$order->id;

        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slot_reservations
                WHERE customer_id = ' . $customerId . ' AND is_confirmed = 0';

        $reservation = Db::getInstance()->getRow($sql);

        if (!$reservation) {
            return;
        }

        Db::getInstance()->update('pickupscheduler_time_slot_reservations', [
            'is_confirmed' => 1,
            'order_id' => $orderId
        ], 'time_slot_id = ' . (int)$reservation['time_slot_id'] . ' AND customer_id = ' . $customerId);

        // send confirmation email
        $timeSlot = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots WHERE id = ' . (int)$reservation['time_slot_id']);
        $customer = new Customer($customerId);

        if (!$timeSlot || !Validate::isLoadedObject($customer)) {
            return;
        }

        $templateVars = [
            '{date}' => $this->formatPickupDateEs($timeSlot['date']),
            '{start_time}' => substr($timeSlot['start_time'], 0, 5),
            '{end_time}' => substr($timeSlot['end_time'], 0, 5),
            '{order_id}' => $orderId,
        ];

        Mail::Send(
            $this->getMailLanguageId($order->id_lang),
            'pickupscheduler_confirmation',
            'Detalles para recoger tu pedido',
            $templateVars,
            $customer->email,
            $customer->firstname . ' ' . $customer->lastname,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . 'pickupscheduler/mails/',
            false,
            (int)$order->id_shop
        );
    }

    private function getOrderReservation($orderId) {
        $reservation = Db::getInstance()->getRow('
            SELECT tsr.*, ts.* FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slot_reservations AS tsr
            INNER JOIN ' . _DB_PREFIX_ . 'pickupscheduler_time_slots AS ts ON tsr.time_slot_id = ts.id
            WHERE tsr.order_id = ' . (int)$orderId . ' AND tsr.is_confirmed = 1');

        if (!$reservation) {
            return null;
        }

        $reservation['start_time'] = substr($reservation['start_time'], 0, 5);
        $reservation['end_time'] = substr($reservation['end_time'], 0, 5);

        return $reservation;
    }

    public function hookDisplayAdminOrder($params){
        $order = new Order((int)$params['id_order']);

        if (!$this->isPickupCarrier($order->id_carrier)) {
            return '';
        }

        $this->context->smarty->assign([
            'reservation' => $this->getOrderReservation($order->id),
        ]);

        return $this->display(__FILE__, 'views/templates/admin/order_pickup_info_block.tpl');
    }

    public function hookDisplayOrderDetail($params) {
        $order = $params['order'];

        if (!$this->isPickupCarrier($order->id_carrier)) {
            return '';
        }

        $this->context->smarty->assign([
            'reservation' => $this->getOrderReservation($order->id),
        ]);

        return $this->display(__FILE__, 'views/templates/front/order_pickup_info_block.tpl');
    }

    public function hookDisplayInvoice($params) {
        $order = new Order((int)$params['id_order']);

        // Only show if it's pickup delivery
        if (!$this->isPickupCarrier($order->id_carrier)) {
            return '';
        }

        $reservation = $this->getOrderReservation($order->id);

        if (!$reservation) {
            return '';
        }

        $this->context->smarty->assign([
            'pickup_info' => [
                'date' => $reservation['date'],
                'formatted_date' => (new DateTime($reservation['date']))->format('d/m/Y'),
                'start_time' => $reservation['start_time'],
                'end_time' => $reservation['end_time'],
            ]
        ]);

        return $this->display(__FILE__, 'views/templates/hook/invoice_pickup_info.tpl');
    }

}
