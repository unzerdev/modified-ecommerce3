<?php

require_once __DIR__ . '/vendor/autoload.php';

foreach (glob(__DIR__ . '/src/Classes/*.php') as $filename) {
    require_once $filename;
}

function xtc_cfg_pull_down_order_statuses_with_empty($order_status_id, $key = '')
{
    $name = (($key) ? 'configuration[' . $key . ']' : 'configuration_value');
    $statuses_array = [
        ['id' => '', 'text' => '&nbsp;'],
    ];
    $statuses_query = xtc_db_query("SELECT orders_status_id,
                                           orders_status_name
                                      FROM " . TABLE_ORDERS_STATUS . "
                                     WHERE language_id = '" . (int)$_SESSION['languages_id'] . "'
                                  ORDER BY sort_order");
    while ($statuses = xtc_db_fetch_array($statuses_query)) {
        $statuses_array[] = ['id' => $statuses['orders_status_id'], 'text' => $statuses['orders_status_name'] . (($statuses['orders_status_id'] == DEFAULT_ORDERS_STATUS_ID) ? ' (' . TEXT_DEFAULT . ')' : '')];
    }
    return xtc_draw_pull_down_menu($name, $statuses_array, $order_status_id);
}