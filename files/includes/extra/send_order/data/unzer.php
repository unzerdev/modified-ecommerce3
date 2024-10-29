<?php

if ($order->info['payment_method'] === UnzerConstants::MODULE_NAME) {

    $unzerOrderHelper = new UnzerOrderHelper();
    $unzerPaymentId = $unzerOrderHelper->getPaymentIdFromOrderId((int)$order->info['order_id']);
    if (!empty($unzerPaymentId)) {
        $payment = (new UnzerApiHelper())->fetchPayment($unzerPaymentId);
        if ($payment !== null) {
            $txt = UnzerConfigHelper::getTxt();
            if ($instructions = $unzerOrderHelper->formatPaymentInstructions($payment, $txt)) {
                $smarty->assign('PAYMENT_INFO_HTML', nl2br($instructions));
                $smarty->clear_assign('PAYMENT_INFO_TXT', $instructions);
            }
        }
    }
}
