<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Releases slots that earlier versions could leave flagged as reserved with no
 * reservation behind them (e.g. after an anonymous or failed booking request).
 */
function upgrade_module_1_3_0($module)
{
    return Db::getInstance()->execute('
        UPDATE ' . _DB_PREFIX_ . 'pickupscheduler_time_slots ts
        LEFT JOIN ' . _DB_PREFIX_ . 'pickupscheduler_time_slot_reservations r ON r.time_slot_id = ts.id
        SET ts.is_reserved = 0
        WHERE ts.is_reserved = 1 AND r.time_slot_id IS NULL
    ');
}
