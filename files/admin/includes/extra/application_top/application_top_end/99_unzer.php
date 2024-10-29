<?php
// this is after permission check
defined( '_VALID_XTC' ) or die( 'Direct Access to this location is not allowed.' );

require_once DIR_FS_CATALOG.'includes/modules/payment/unzer/autoload.php';
require_once DIR_FS_CATALOG.'includes/modules/payment/unzer/src/Admin/Modules/UnzerTransactionsAdminActions.php';

if(isset($_GET['unzer_action'])){
    switch ($_GET['unzer_action']){
        case 'transactions':
            $unzerTransactionsAdminActions = new UnzerTransactionsAdminActions();
            header('Content-Type: application/json');
            echo json_encode($unzerTransactionsAdminActions->actionGetTransactions());
            die;
        case 'doTransaction':
            $unzerTransactionsAdminActions = new UnzerTransactionsAdminActions();
            header('Content-Type: application/json');
            echo json_encode($unzerTransactionsAdminActions->actionDoAction());
            die;
    }
}

if(!empty($_POST['unzer_configuration'])){
    require_once UnzerConstants::MODULE_PATH_FS . 'Admin/Modules/UnzerConfigurationController.inc.php';
    (new UnzerConfigurationController())->saveConfiguration();
}

if(!empty($_POST['delete_unzer_webhooks'])){
    require_once UnzerConstants::MODULE_PATH_FS . 'Admin/Modules/UnzerConfigurationController.inc.php';
    $controller = new UnzerConfigurationController();
    foreach($_POST['delete_unzer_webhooks'] as $webhookId){
        $controller->deleteWebhook($webhookId);
    }
}

if(!empty($_POST['add_unzer_webhook'])){
    require_once UnzerConstants::MODULE_PATH_FS . 'Admin/Modules/UnzerConfigurationController.inc.php';
    (new UnzerConfigurationController())->addWebhook();
}