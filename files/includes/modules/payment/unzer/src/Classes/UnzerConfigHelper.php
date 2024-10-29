<?php

use UnzerSDK\Constants\TransactionTypes;

class UnzerConfigHelper
{
    protected static $isTextInitialized = false;
    public static function initTexts(){}
    public static function getConstant($constantName)
    {
        return defined($constantName) ? constant($constantName) : null;
    }

    public static function getStringConstant($constantName): string
    {
        return defined($constantName) ? (string)constant($constantName) : '';
    }

    public static function getConfigValueFromDb($key): ?string
    {
        $q = "SELECT `configuration_value` FROM ".TABLE_CONFIGURATION." WHERE `configuration_key` = '".xtc_db_input($key)."'";
        $result = xtc_db_query($q);

        if (xtc_db_num_rows($result) === 0) {
            return null;
        }

        $row = xtc_db_fetch_array($result);
        return (string)$row['configuration_value'];
    }

    public static function createConfigValue($key, $value, $type = [], $sortOrder = 0, $lastModified = 'now()')
    {
        if (self::getConfigValueFromDb($key) !== null) {
            return;
        }
        xtc_db_perform(
            TABLE_CONFIGURATION,
            [
                '`configuration_key`' => "$key",
                '`configuration_value`' => $value,
                '`use_function`' => $type[0]??'',
                '`set_function`' => $type[1]??'',
                '`sort_order`' => $sortOrder,
                '`last_modified`' => $lastModified,
            ]
        );
    }


    public static function upsertConfigValue(string $key, mixed $json_encode): void
    {
        $existing = self::getConfigValueFromDb($key);
        if ($existing === null) {
            self::createConfigValue($key, $json_encode);
        } else {
            xtc_db_perform(
                TABLE_CONFIGURATION,
                [
                    '`configuration_value`' => $json_encode,
                ],
                "update",
                "`configuration_key` = '" . xtc_db_input($key) . "'"
            );
        }
    }

    public static function getPublicKey(): string
    {
        return self::getStringConstant('MODULE_PAYMENT_UNZER_PUBLIC_KEY');
    }

    public static function getPrivateKey(): string
    {
        return self::getStringConstant('MODULE_PAYMENT_UNZER_PRIVATE_KEY');
    }

    public static function getPaymentMethodsConfiguration(): array
    {
        $configValue = self::getConstant('MODULE_PAYMENT_UNZER_PAYMENT_METHODS_CONFIGURATION');
        $return = json_decode($configValue, true);
        return empty($return) ? [] : $return;
    }

    public static function getPaymentMethodTransactionType($type)
    {
        $config = self::getPaymentMethodsConfiguration();
        $type = strtolower($type);
        return $config[$type]['transactionType'] ?: TransactionTypes::CHARGE;
    }

    public static function getPaymentMethodName(string $code): string
    {
        return self::getStringConstant('MODULE_PAYMENT_UNZER_PAYMENT_METHOD_LABEL_' . strtoupper($code));
    }

    public static function getWebhookUrl():string{
        if(function_exists('xtc_catalog_href_link')){
            return xtc_catalog_href_link('callback/unzer/webhook.php', '','SSL');
        }else {
            return xtc_href_link('callback/unzer/webhook.php', '', 'SSL');
        }
    }

    public static function getTxt(){
        include UnzerConstants::MODULE_PATH_FS . 'TextPhrases/' . $_SESSION['language'] . '/unzer.lang.inc.php';
        return $t_language_text_section_content_array;
    }

}