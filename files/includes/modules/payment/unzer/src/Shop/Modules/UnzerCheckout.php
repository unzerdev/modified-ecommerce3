<?php

use UnzerSDK\Exceptions\UnzerApiException;

$html = (function() {
    $moduleSmarty = new Smarty();
    $orderId = $_SESSION['unzer_order_id'];
    $paymentMethod = $_SESSION['unzer_payment_method'] ?? null;

    if (!class_exists('order')) {
        require_once DIR_FS_CATALOG . 'includes/classes/order.php';
    }

    $order = new order($orderId);
    $unzerOrderHelper = new UnzerOrderHelper();
    try {
        $payPage = $unzerOrderHelper->getUnzerPayPage($order, $paymentMethod);
        $_SESSION['unzer_payment_id'] = $payPage->getPaymentId();
        $data = [
            'unzerPaymentPageId' => $payPage->getId(),
            'threatMetrixUrl' => 'https://h.online-metrix.net/fp/tags.js?org_id=363t8kgq&session_id=' . $payPage->getAdditionalAttribute('riskData.threatMetrixId'),
            'locale' => $_SESSION['language_code'] ?? 'en',
            'checkoutPaymentUrl' => xtc_href_link('checkout_payment.php', '', 'SSL'),
            'checkoutProcessUrl' => xtc_href_link('checkout_process.php', '', 'SSL'),
        ];
        $moduleSmarty->assign($data);
        return $moduleSmarty->fetch(DIR_FS_CATALOG . 'includes/modules/payment/unzer/src/Shop/Themes/All/unzer_checkout.html');
    } catch (UnzerApiException $e) {
        $unzerOrderHelper->logger->error('Error during payment action: ' . $e->getMerchantMessage(), [
            'trace' => $e->getTraceAsString(),
            'error' => $e->getMerchantMessage(),
            'message' => $e->getMessage(),
            'basket' => ($payPage ?? null)?->getBasket()?->expose(),
            'customer' => ($payPage ?? null)?->getCustomer()?->expose(),
            'paypage' => ($payPage ?? null)?->expose(),
        ]);
        UnzerCheckoutHelper::doErrorRedirect($e->getClientMessage());
    } catch (Exception $e) {
        $unzerOrderHelper->logger->error('Error during payment action: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'error' => $e->getMessage(),
            'message' => $e->getMessage(),
            'basket' => ($payPage ?? null)?->getBasket()?->expose(),
            'customer' => ($payPage ?? null)?->getCustomer()?->expose(),
            'paypage' => ($payPage ?? null)?->expose(),
        ]);
        UnzerCheckoutHelper::doErrorRedirect($e->getMessage());
    }
})();
/** @var Smarty $smarty */
$smarty->assign('main_content', $html);