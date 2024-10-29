<?php

use UnzerSDK\Constants\PaymentState;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Chargeback;

class UnzerTransactionsAdminActions
{
    public static function getAdminBlock()
    {
        $orderId = (int)$_GET['oID'];
        $q = "SELECT * FROM " . TABLE_ORDERS . " WHERE orders_id = " . $orderId;
        $rs = xtc_db_query($q);
        $r = xtc_db_fetch_array($rs);

        if ($r['payment_method'] !== UnzerConstants::MODULE_NAME || empty($r[UnzerConstants::ORDER_TABLE_PAYMENT_ID_COLUMN])) {
            return;
        }

        $heading = '<img src="' . UnzerConstants::MODULE_PATH_WS . 'Assets/Image/unzer_logo.svg" alt="Unzer" />';
        $body = '<div id="admin-unzer-transactions" 
                          data-load-url="' . xtc_href_link('orders.php', 'unzer_action=transactions&paymentId=' . $r[UnzerConstants::ORDER_TABLE_PAYMENT_ID_COLUMN] . '&orderId=' . $orderId) . '"
                          data-action-url="' . xtc_href_link('orders.php', 'unzer_action=doTransaction') . '"
                          data-order-id="' . $orderId . '"
                          data-payment-id="' . $r[UnzerConstants::ORDER_TABLE_PAYMENT_ID_COLUMN] . '"
                     ></div>
                     <div id="unzer-debug-container">
                         <button id="unzer-debug-button" class="btn btn-default" style="margin: 10px 0 0 0;">Debug</button>
                         <pre id="unzer-debug-content" style="display: none; overflow: auto; max-height: 800px; max-width: 990px;"></pre>
                    </div>
                    <script src="' . UnzerConstants::MODULE_PATH_WS . 'Admin/Assets/js/admin_transactions.js"></script>
                  <link rel="stylesheet" href="' . UnzerConstants::MODULE_PATH_WS . 'Admin/Assets/css/admin_transactions.css" />';
        return '<div id="unzer-admin-order-block"><div id="unzer-logo-container">' . $heading . '</div>' . $body.'</div>';
    }

    public function actionGetTransactions()
    {
        $orderHelper = new UnzerOrderHelper();
        $paymentId = $_GET['paymentId'];
        $payment = (new UnzerApiHelper())->fetchPayment($paymentId);
        $orderId = (int)$_GET['orderId'];

        $txt = UnzerConfigHelper::getTxt();

        $data = [
            'paymentMethodLabel' => $orderHelper->getUnzerPaymentMethodNameFromOrderId($orderId),
            'paymentMethodCode' => $orderHelper->getUnzerPaymentMethodCodeFromOrderId($orderId),
            'payment' => $payment,
            'amountRefundable' => $payment->getAmount()->getCharged(),
            'amountChargeable' => $payment->getAmount()->getRemaining(),
            'transactions' => $this->getTransactionArray($payment),
            'txt' => $txt,
        ];

        $paymentState = $payment->getState();
        try {
            $paymentStateName = PaymentState::mapStateCodeToName($paymentState);
            $data['paymentStateName'] = $paymentStateName;
        } catch (Exception $e) {
            $data['paymentStateName'] = '?';
        }


        if ($instructions = $orderHelper->formatPaymentInstructions($payment, $txt)) {
            $data['payment_instructions'] = '<div><b>' . UnzerConfigHelper::getStringConstant('UNZER_PAYMENT_INSTRUCTIONS') . '</b></div>' . nl2br($instructions);
        }


        $html = (new Smarty())->fetch(DIR_FS_CATALOG . 'includes/modules/payment/unzer/src/Admin/Html/unzer_transactions.html', $data);

        return [
            'success' => true,
            'html' => $html,
            'debug' => preg_replace('/s\-priv\-[a-z0-9]+/i', '', print_r($payment, true)),
        ];
    }

    protected function getTransactionArray(\UnzerSDK\Resources\Payment $payment): array
    {
        $currency = $payment->getCurrency();
        $transactions = [];
        if ($payment->getAuthorization()) {
            $transactions[] = $payment->getAuthorization();
            if ($payment->getAuthorization()->getCancellations()) {
                $transactions = array_merge($transactions, $payment->getAuthorization()->getCancellations());
            }
        }
        if ($payment->getCharges()) {
            foreach ($payment->getCharges() as $charge) {
                $transactions[] = $charge;
                if ($charge->getCancellations()) {
                    $transactions = array_merge($transactions, $charge->getCancellations());
                }
            }
        }
        if ($payment->getReversals()) {
            foreach ($payment->getReversals() as $reversal) {
                $transactions[] = $reversal;
            }
        }
        if ($payment->getRefunds()) {
            foreach ($payment->getRefunds() as $refund) {
                $transactions[] = $refund;
            }
        }
        if ($payment->getChargebacks()) {
            foreach ($payment->getChargebacks() as $chargeback) {
                $transactions[] = $chargeback;
            }
        }

        // $transactions = array_merge($transactions, $payment->getCharges(), $payment->getRefunds(), $payment->getReversals());
        $transactionTypes = [
            Cancellation::class => 'cancellation',
            Charge::class => 'charge',
            Authorization::class => 'authorization',
            Chargeback::class => 'chargeback',
        ];

        $transactions = array_map(
            function (AbstractTransactionType $transaction) use ($transactionTypes, $currency) {
                $return = $transaction->expose();
                $class = get_class($transaction);
                $return['type'] = $transactionTypes[$class] ?? $class;
                $return['time'] = $transaction->getDate();

                if (method_exists($transaction, 'getAmount') && method_exists($transaction, 'getCurrency')) {
                    $return['amount'] = number_format($transaction->getAmount(), 2) . ' ' . $currency;
                } elseif (isset($return['amount'])) {
                    $return['amount'] = number_format($return['amount'], 2) . ' ' . $currency;
                }
                $status = $transaction->isSuccess() ? 'success' : 'error';
                $status = $transaction->isPending() ? 'pending' : $status;
                $return['status'] = $status;

                return $return;
            },
            $transactions
        );
        usort(
            $transactions,
            function ($a, $b) {
                return strcmp($a['time'], $b['time']);
            }
        );
        return $transactions;
    }

    public function actionDoAction()
    {
        $unzerApiHelper = new UnzerApiHelper();
        $action = $_POST['action'];
        $error = null;
        switch ($action) {
            case 'capture':
                try {
                    $unzerApiHelper->charge($_POST['paymentId'], (float)$_POST['amount']);
                } catch (Exception $e) {
                    $error = 'ERROR: ' . $e->getMessage();
                }
                break;
            case 'refund':
                try {
                    $unzerApiHelper->refund($_POST['paymentId'], (float)$_POST['amount']);
                } catch (Exception $e) {
                    $error = 'ERROR: ' . $e->getMessage();
                }
                break;

        }
        return [
            'success' => true,
            'error' => $error,
        ];
    }
}
