<?php

if ($order->info['payment_method'] === UnzerConstants::MODULE_NAME) {
    $paymentInformation = (new UnzerOrderHelper())->getPaymentInformationForOrder((int)$order->info['orders_id']);
    if($paymentInformation){
        $smarty->assign('PAYMENT_INFO', $paymentInformation);
    }
}