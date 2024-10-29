<?php

require_once 'includes/application_top.php';

// redirect
if (empty($_SESSION['unzer_order_id'])) {
    UnzerCheckoutHelper::doErrorRedirect('No order id found');
}
// create smarty elements
$smarty = new Smarty();

include DIR_FS_CATALOG.'includes/modules/payment/unzer/src/Shop/Modules/UnzerCheckout.php';

// include header
require (DIR_WS_INCLUDES . 'header.php');

// include boxes
$display_mode = 'checkout';
require (DIR_FS_CATALOG.'templates/'.CURRENT_TEMPLATE.'/source/boxes.php');

$smarty->assign('language', $_SESSION['language']);
$smarty->caching = 0;
if (!defined('RM')){
    $smarty->load_filter('output', 'note');
}

$smarty->display(CURRENT_TEMPLATE.'/index.html');
include ('includes/application_bottom.php');