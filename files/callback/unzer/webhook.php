<?php
chdir('../../');
require_once('includes/application_top.php');

require_once UnzerConstants::MODULE_PATH_FS.'Shop/Classes/Controllers/UnzerWebhookController.inc.php';

$webhookController = new UnzerWebhookController();
header('Content-Type: application/json');
echo json_encode($webhookController->actionDefault());