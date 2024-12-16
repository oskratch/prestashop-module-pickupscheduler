<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'pickupscheduler/controllers/front/gettimeslots.php';
require_once _PS_MODULE_DIR_ . 'pickupscheduler/controllers/front/confirmtimeslot.php';
require_once _PS_MODULE_DIR_ . 'pickupscheduler/classes/TimeSlotManager.php';

class PickupScheduler extends Module {

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
        $this->version = '1.0.0';
        $this->author = 'Oscar Periche - 4funkies';
        $this->need_instance = 0;
        
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Pickup Scheduler');
        $this->description = $this->l('Permite a los clientes seleccionar un tramo horario y un día disponibles para recoger su pedido en la tienda.');

        $this->confirmUninstall = $this->l('¿Estás seguro de que deseas desinstalar el módulo?');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    public function install(){
        if (!parent::install() 
        || !$this->installTab()
        || !$this->registerHook('displayAfterCarrier') 
        || !$this->registerHook('actionFrontControllerSetMedia')
        || !$this->registerHook('actionValidateOrderAfter')
        || !$this->registerHook('displayAdminOrder')
        || !$this->registerHook('displayOrderDetail')) {
            return false;
        }

        Configuration::updateValue('PICKUP_SCHEDULER_CARRIER_ID', 0);
        Configuration::updateValue('PICKUP_SCHEDULER_RESERVATION_MINUTES', 20);
        Configuration::updateValue('PICKUP_SCHEDULER_PREPARATION_DAYS', 2);
        Configuration::updateValue('PICKUP_SCHEDULER_AVAILABLE_DAYS', 7);

        $sql = [];

        // Crear la taula pickupscheduler_time_slots_config
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

        // Crear la taula pickupscheduler_time_slots
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

        // Crear la taula pickupscheduler_time_slot_reservations
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

        // Executar les consultes SQL
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
        $tabId = (int) Tab::getIdFromClassName('AdminPickupOrdersController');

        if ($tabId) {
            $tab = new Tab($tabId);
            $tab->delete();
        }

        return true;

    }

    public function getContent() {
        $output = '';

        if (Tools::isSubmit('PICKUP_SCHEDULER_CARRIER_ID') && Tools::getValue('PICKUP_SCHEDULER_CARRIER_ID') !== null) {
            $carrierId = (int)Tools::getValue('PICKUP_SCHEDULER_CARRIER_ID');
            Configuration::updateValue('PICKUP_SCHEDULER_CARRIER_ID', $carrierId);
            $output = $this->displayConfirmation($this->l('El transportista se ha actualizado correctamente.'));
        }

        if(Tools::isSubmit('PICKUP_SCHEDULER_PREPARATION_DAYS') && Tools::getValue('PICKUP_SCHEDULER_PREPARATION_DAYS') !== null){
            $preparationDays = (int)Tools::getValue('PICKUP_SCHEDULER_PREPARATION_DAYS');
            Configuration::updateValue('PICKUP_SCHEDULER_PREPARATION_DAYS', $preparationDays);
            $output = $this->displayConfirmation($this->l('La configuración del tiempo de preparación se ha actualizado correctamente.'));
        }

        if(Tools::isSubmit('PICKUP_SCHEDULER_RESERVATION_MINUTES') && Tools::getValue('PICKUP_SCHEDULER_RESERVATION_MINUTES') !== null){
            $reservationMinutes = (int)Tools::getValue('PICKUP_SCHEDULER_RESERVATION_MINUTES');
            Configuration::updateValue('PICKUP_SCHEDULER_RESERVATION_MINUTES', $reservationMinutes);
            $output = $this->displayConfirmation($this->l('La configuración de los tramos horarios se ha actualizado correctamente.'));
        }

        if(Tools::isSubmit('schedule_config') && Tools::getValue('schedule_config')){
            $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $timeSlotsConfig = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots_config');

            foreach ($daysOfWeek as $day) {
                $active = (int)Tools::getValue('PICKUP_SCHEDULER_' . strtoupper($day) . '_ACTIVE');
                $startTime = Tools::getValue('PICKUP_SCHEDULER_' . strtoupper($day) . '_START_TIME');
                $endTime = Tools::getValue('PICKUP_SCHEDULER_' . strtoupper($day) . '_END_TIME');
                $intervalMinutes = (int)Tools::getValue('PICKUP_SCHEDULER_' . strtoupper($day) . '_INTERVAL_MINUTES');

                $config = array_filter($timeSlotsConfig, function($slot) use ($day) {
                    return $slot['day_of_week'] === $day;
                });
                $config = reset($config);

                if ($active) {
                    if ($config) {
                        Db::getInstance()->update('pickupscheduler_time_slots_config', [
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'interval_minutes' => $intervalMinutes
                        ], 'id = ' . $config['id']);
                    } else {
                        Db::getInstance()->insert('pickupscheduler_time_slots_config', [
                            'day_of_week' => $day,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'interval_minutes' => $intervalMinutes
                        ]);
                    }
                } else {
                    if ($config) {
                        Db::getInstance()->delete('pickupscheduler_time_slots_config', 'id = ' . $config['id']);
                    }
                }
            }

            $timeSlotManager = new TimeSlotManager();

            // eliminar tots els registres que no estiguin reservats
            $timeSlotManager->cleanAllTimeSlots();

            // crear els registres corresponents            
            $timeSlotManager->generateTimeSlots();

            $output = $this->displayConfirmation($this->l('La configuración de los tramos horarios se ha actualizado correctamente.'));
        }

        if(Tools::isSubmit('PICKUP_SCHEDULER_AVAILABLE_DAYS') && Tools::getValue('PICKUP_SCHEDULER_AVAILABLE_DAYS') !== null){
            $availableDays = (int)Tools::getValue('PICKUP_SCHEDULER_AVAILABLE_DAYS');
            Configuration::updateValue('PICKUP_SCHEDULER_AVAILABLE_DAYS', $availableDays);
            $output = $this->displayConfirmation($this->l('La configuración de los días disponibles se ha actualizado correctamente.'));
        }
    
        $output .= $this->displayCarrierPickUpForm();
        $output .= $this->displayPreparationDaysForm();
        $output .= $this->displayReservationExpireForm();
        $output .= $this->displayTimeSlotsForm();
        $output .= $this->displayAvailableDaysForm();
        return $output;
    }
    
    public function displayCarrierPickUpForm() {
        $carrierId = Configuration::get('PICKUP_SCHEDULER_CARRIER_ID');
        
        $carriers = Carrier::getCarriers(
            (int)Configuration::get('PS_LANG_DEFAULT'),
            true,
            false,
            false,
            null,
            Carrier::ALL_CARRIERS
        );

        $carrierOptions = [];
        $carrierOptions[] = [
            'id_option' => 0,
            'name' => "Seleccionar Transportista..."
        ];
        foreach ($carriers as $carrier) {
            $carrierOptions[] = [
                'id_option' => $carrier['id_carrier'],
                'name' => $carrier['name']
            ];
        }

        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Seleccionar el transportista relacionado con la recogida en tienda'),
                    'icon' => 'icon-cogs',
                ],
                'description' => $this->l('Cuando el cliente escoja el transportista seleccionado se le mostrará la tabla de horarios disponibles para recoger el pedido.'),
                'input' => [
                    [
                        'type' => 'select',
                        //'label' => $this->l(''),
                        'name' => 'PICKUP_SCHEDULER_CARRIER_ID',
                        'options' => [
                            'query' => $carrierOptions,
                            'id' => 'id_option',
                            'name' => 'name'
                        ],
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Guardar'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;

        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        $helper->fields_value['PICKUP_SCHEDULER_CARRIER_ID'] = Tools::getValue('PICKUP_SCHEDULER_CARRIER_ID', $carrierId);

        return $helper->generateForm([$form]);
    }
    
    public function displayPreparationDaysForm() {
        $preparationDays = Configuration::get('PICKUP_SCHEDULER_PREPARATION_DAYS');

        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Días necesarios para preparar el pedido'),
                    'icon' => 'icon-cogs',
                ],
                'description' => $this->l('Define el número de días que la tienda necesita para preparar un pedido. Los clientes solo podrán seleccionar fechas de recogida a partir de este periodo, garantizando que haya tiempo suficiente para la preparación del pedido antes de la recogida.'),
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Número de días'),
                        'name' => 'PICKUP_SCHEDULER_PREPARATION_DAYS',
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Guardar'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit-preparation-days';

        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        $helper->fields_value['PICKUP_SCHEDULER_PREPARATION_DAYS'] = Tools::getValue('PICKUP_SCHEDULER_PREPARATION_DAYS', $preparationDays);

        return $helper->generateForm([$form]);
    }
    
    public function displayReservationExpireForm() {
        $reservationMinutes = Configuration::get('PICKUP_SCHEDULER_RESERVATION_MINUTES');

        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Tiempo de reserva en minutos'),
                    'icon' => 'icon-cogs',
                ],
                'description' => $this->l('Cantidad de minutos que se mantiene la reserva del tramo horario seleccionado antes de finalizar el pedido. Si el cliente supera este límite, el tramo horario será liberado y estará disponible para otros clientes.'),
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Límite en minutos'),
                        'name' => 'PICKUP_SCHEDULER_RESERVATION_MINUTES',
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Guardar'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;

        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        $helper->fields_value['PICKUP_SCHEDULER_RESERVATION_MINUTES'] = Tools::getValue('PICKUP_SCHEDULER_RESERVATION_MINUTES', $reservationMinutes);

        return $helper->generateForm([$form]);
    }
    
    public function displayTimeSlotsForm() {
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $timeSlotsConfig = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots_config');

        $fields_value = [];
        foreach ($daysOfWeek as $day) {
            $config = array_filter($timeSlotsConfig, function($slot) use ($day) {
                return $slot['day_of_week'] === $day;
            });
            $config = reset($config);

            $fields_value['PICKUP_SCHEDULER_' . strtoupper($day) . '_ACTIVE'] = $config ? 1 : 0;
            $fields_value['PICKUP_SCHEDULER_' . strtoupper($day) . '_START_TIME'] = $config ? $config['start_time'] : '00:00';
            $fields_value['PICKUP_SCHEDULER_' . strtoupper($day) . '_END_TIME'] = $config ? $config['end_time'] : '00:00';
            $fields_value['PICKUP_SCHEDULER_' . strtoupper($day) . '_INTERVAL_MINUTES'] = $config ? $config['interval_minutes'] : 0;
        }

        $this->context->smarty->assign([
            'fields_value' => $fields_value,
            'daysOfWeek' => $daysOfWeek,
            'form_action' => AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]) . '&token=' . Tools::getAdminTokenLite('AdminModules'),
            'token' => Tools::getAdminTokenLite('AdminModules')
        ]);

        return $this->display(__FILE__, 'views/templates/admin/time-slot-form.tpl');
    }
    
    public function displayAvailableDaysForm() {
        $availableDays = Configuration::get('PICKUP_SCHEDULER_AVAILABLE_DAYS');

        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Número de días disponibles con franjas horarias'),
                    'icon' => 'icon-cogs',
                ],
                'description' => $this->l('Establece el número de días para los que siempre habrá franjas horarias disponibles para la recogida. Este valor asegura que las franjas se generen automáticamente para mantener la disponibilidad indicada.'),
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Número de días'),
                        'name' => 'PICKUP_SCHEDULER_AVAILABLE_DAYS',
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Guardar'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit-available-days';;

        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        $helper->fields_value['PICKUP_SCHEDULER_AVAILABLE_DAYS'] = Tools::getValue('PICKUP_SCHEDULER_AVAILABLE_DAYS', $availableDays);

        return $helper->generateForm([$form]);
    }
    
    public function hookDisplayAfterCarrier($params){
        $pickupSchedulerConfig = [
            'pickup_scheduler_carrier_id' => Configuration::get('PICKUP_SCHEDULER_CARRIER_ID'),
            'weekends' => Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots_config WHERE day_of_week IN ("Saturday", "Sunday")') > 0 ? 1 : 0,
            'minTime' => Db::getInstance()->getValue('SELECT MIN(start_time) FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots_config'),
            'maxTime' => Db::getInstance()->getValue('SELECT MAX(end_time) FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots_config'),
            'minInterval' => Db::getInstance()->getValue('SELECT MIN(interval_minutes) FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots_config'),
            'customer_id' => $this->context->customer->id,
            'pickup_scheduler_preparation_days' => Configuration::get('PICKUP_SCHEDULER_PREPARATION_DAYS') . ' ' . (Configuration::get('PICKUP_SCHEDULER_PREPARATION_DAYS') == 1 ? 'día' : 'días'),
            'pickup_scheduler_reservation_minutes' => Configuration::get('PICKUP_SCHEDULER_RESERVATION_MINUTES')
        ];

        $timeSlotManager = new TimeSlotManager();
        $unconfirmedReservation = $timeSlotManager->getUnconfirmedReservation((int)$pickupSchedulerConfig['customer_id']);
        $pickupSchedulerConfig['calendar_event_id_selected'] = $unconfirmedReservation !== null ? (int)$unconfirmedReservation['time_slot_id'] : null;
        $pickupSchedulerConfig['calendar_event_selected_expires'] = $unconfirmedReservation !== null ? (new DateTime($unconfirmedReservation['expires_at']))->format('Y-m-d H:i:s') : null;

        if ($unconfirmedReservation !== null) {
            $timeSlot = Db::getInstance()->getRow('SELECT `date` FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots WHERE id = ' . (int)$unconfirmedReservation['time_slot_id']);
            $pickupSchedulerConfig['calendar_event_date_selected'] = $timeSlot['date'];
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

    public function hookActionValidateOrderAfter($params) {
        $order = $params['order'];
        $carrierId = $order->id_carrier;

        if ($carrierId == Configuration::get('PICKUP_SCHEDULER_CARRIER_ID')) {
            $customerId = $order->id_customer;
            $orderId = $order->id;

            $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slot_reservations 
                    WHERE customer_id = ' . (int)$customerId . ' AND is_confirmed = 0';

            $reservation = Db::getInstance()->getRow($sql);

            if ($reservation) {
                Db::getInstance()->update('pickupscheduler_time_slot_reservations', [
                    'is_confirmed' => 1,
                    'order_id' => (int)$orderId
                ], 'time_slot_id = ' . (int)$reservation['time_slot_id'] . ' AND customer_id = ' . (int)$customerId);

                // enviar email de confirmación
                
                $timeSlot = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slots WHERE id = ' . (int)$reservation['time_slot_id']);

                $templateVars = [
                    '{date}' => ucfirst(strftime('%A, %d de %B de %Y', strtotime($timeSlot['date']))),
                    '{start_time}' => $timeSlot['start_time'],
                    '{end_time}' => $timeSlot['end_time'],
                    '{order_id}' => (int)$orderId,
                ];

                Mail::Send(
                    (int)(Configuration::get('PS_LANG_DEFAULT')),
                    'pickupscheduler_confirmation',
                    'Detalles para recoger tu pedido',
                    $templateVars,
                    $this->context->customer->email,
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    _PS_MODULE_DIR_ . 'pickupscheduler/mails'
                );
            }
        }
    }

    public function hookDisplayAdminOrder($params){
        $orderId = (int)$params['id_order'];
        $order = new Order($orderId);
        
        if ($order->id_carrier == Configuration::get('PICKUP_SCHEDULER_CARRIER_ID')) {
            $sql = 'SELECT tsr.*, ts.* FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slot_reservations AS tsr
                INNER JOIN ' . _DB_PREFIX_ . 'pickupscheduler_time_slots AS ts
                ON tsr.time_slot_id = ts.id
                WHERE tsr.order_id = ' . (int)$orderId;

            $reservation = Db::getInstance()->getRow($sql);

            $this->context->smarty->assign([
                'reservation' => $reservation,
            ]);

            return $this->display(__FILE__, 'views/templates/admin/order_pickup_info_block.tpl');
        }
    }

    public function hookDisplayOrderDetail($params) {
        $orderId = (int)$params['order']->id;
        $order = new Order($orderId);

        if ($order->id_carrier == Configuration::get('PICKUP_SCHEDULER_CARRIER_ID')) {
            $sql = 'SELECT tsr.*, ts.* FROM ' . _DB_PREFIX_ . 'pickupscheduler_time_slot_reservations AS tsr
                INNER JOIN ' . _DB_PREFIX_ . 'pickupscheduler_time_slots AS ts
                ON tsr.time_slot_id = ts.id
                WHERE tsr.order_id = ' . (int)$orderId;

            $reservation = Db::getInstance()->getRow($sql);

            $this->context->smarty->assign([
                'reservation' => $reservation,
            ]);

            return $this->display(__FILE__, 'views/templates/front/order_pickup_info_block.tpl');
        }
    }

}