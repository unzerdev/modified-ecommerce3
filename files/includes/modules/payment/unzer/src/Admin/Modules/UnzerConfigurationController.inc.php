<?php

use UnzerSDK\Constants\TransactionTypes;

class UnzerConfigurationController
{
    public function getPaymentMethodForm()
    {
        if (!UnzerConfigHelper::getPrivateKey()) {
            return '';
        }
        try {
            $smarty = new Smarty();
            $smarty->assign($this->getPaymentMethodTemplateData());
            $html = $smarty->fetch(UnzerConstants::MODULE_PATH_FS . 'Admin/Html/unzer_configuration.html');
            $html .= '<link rel="stylesheet" type="text/css" href="' . UnzerConstants::MODULE_PATH_WS . 'Admin/Assets/css/admin.css">';
            return $html;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getWebhookHtml()
    {
        try {
            $smarty = new Smarty();
            $smarty->assign($this->getWebhookTemplateData());
            $html = $smarty->fetch(UnzerConstants::MODULE_PATH_FS . 'Admin/Html/unzer_webhooks.html');
            return $html;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function addWebhook()
    {
        (new UnzerApiHelper())->addCurrentWebhook();
    }

    public function deleteWebhook($webhookId)
    {
        (new UnzerApiHelper())->deleteWebhook($webhookId);
    }

    protected function getWebhookTemplateData()
    {
        $unzerApiHelper = new UnzerApiHelper();
        return [
            'txt' => UnzerConfigHelper::getTxt(),
            'content' => [
                'urls' => [
                    'logo' => UnzerConstants::MODULE_PATH_WS . 'Assets/Image/unzer_logo.svg',
                ],
                'isRegistered' => $unzerApiHelper->isWebhookRegistered(),
                'webhooks' => $unzerApiHelper->fetchWebhooks(),
            ],
        ];
    }

    protected function getPaymentMethodTemplateData(): array
    {
        $paymentMethods = (new UnzerCheckoutHelper())->getAvailablePaymentMethods(true, null, 0);
        foreach ($paymentMethods as &$paymentMethod) {
            $paymentMethod['canAuthorize'] = UnzerApiHelper::canPaymentMethodAuthorize($paymentMethod['originalCode']);
        }


        return [
            'txt' => UnzerConfigHelper::getTxt(),
            'content' => [
                'urls' => [
                    'logo' => UnzerConstants::MODULE_PATH_WS . 'Assets/Image/unzer_logo.svg',
                ],

                'paymentMethods' => $paymentMethods,
                'currentConfig' => UnzerConfigHelper::getPaymentMethodsConfiguration(),
                'options' => [
                    'transactionTypes' => [
                        [
                            'value' => TransactionTypes::CHARGE,
                            'label' => UnzerConfigHelper::getStringConstant('UNZER_OPTION_LABEL_CAPTURE'),
                        ],
                        [
                            'value' => TransactionTypes::AUTHORIZATION,
                            'label' => UnzerConfigHelper::getStringConstant('UNZER_OPTION_LABEL_AUTHORIZE'),
                        ],
                    ],

                ],
            ],
        ];

    }


    public function saveConfiguration()
    {
        if (!empty($_POST['unzer_configuration'])) {
            UnzerConfigHelper::upsertConfigValue('MODULE_PAYMENT_UNZER_PAYMENT_METHODS_CONFIGURATION', json_encode($_POST['unzer_configuration']));
        }
    }
}
