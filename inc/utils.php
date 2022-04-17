<?php

use Liquichain\Api\Exceptions\ApiException;
use Liquichain\Api\Resources\CurrentProfile;
use Liquichain\WooCommerce\Components\ComponentsStyles;
use Liquichain\WooCommerce\Plugin;
use Liquichain\WooCommerce\SDK\Api;
use Liquichain\WooCommerce\Settings\SettingsComponents;

/**
 * Check if the current page context is for checkout
 *
 * @return bool
 */
function liquichainWooCommerceIsCheckoutContext()
{
    global $wp_query;
    if (!isset($wp_query)) {
        return false;
    }
    return is_checkout() || is_checkout_pay_page();
}

/**
 * ComponentsStyles Factory
 *
 * @return array
 */
function liquichainWooCommerceComponentsStylesForAvailableGateways()
{
    $pluginPath = untrailingslashit(M4W_PLUGIN_DIR) . '/';

    $liquichainComponentsStyles = new ComponentsStyles(
        new SettingsComponents($pluginPath),
        WC()->payment_gateways()
    );

    return $liquichainComponentsStyles->forAvailableGateways();
}
/**
 * Retrieve the cardToken value for Liquichain Components
 *
 * @return string
 */
function liquichainWooCommerceCardToken()
{
    return $cardToken = filter_input(INPUT_POST, 'cardToken', FILTER_SANITIZE_STRING) ?: '';
}

/**
 * Check if certain gateway setting is enabled.
 *
 * @param $gatewaySettingsName string
 * @param $settingToCheck string
 * @param bool $default
 * @return bool
 */
function liquichainWooCommerceIsGatewayEnabled($gatewaySettingsName, $settingToCheck, $default = false)
{

    $gatewaySettings = get_option($gatewaySettingsName);
    return liquichainWooCommerceStringToBoolOption(
        checkIndexExistOrDefault($gatewaySettings, $settingToCheck, $default)
    );
}

/**
 * Check if the Apple Pay gateway is enabled and then if the button is enabled too.
 *
 * @param $page
 *
 * @return bool
 */
function liquichainWooCommerceisApplePayDirectEnabled($page)
{
    $pageToCheck = 'liquichain_apple_pay_button_enabled_' . $page;
    return liquichainWooCommerceIsGatewayEnabled('liquichain_wc_gateway_applepay_settings', $pageToCheck);
}
/**
 * Check if the PayPal gateway is enabled and then if the button is enabled too.
 *
 * @param $page string setting to check between cart or product
 *
 * @return bool
 */
function liquichainWooCommerceIsPayPalButtonEnabled($page)
{
    $payPalGatewayEnabled = liquichainWooCommerceIsGatewayEnabled('liquichain_wc_gateway_paypal_settings', 'enabled');

    if (!$payPalGatewayEnabled) {
        return false;
    }
    $settingToCheck = 'liquichain_paypal_button_enabled_' . $page;
    return liquichainWooCommerceIsGatewayEnabled('liquichain_wc_gateway_paypal_settings', $settingToCheck);
}

/**
 * Check if the product needs shipping
 *
 * @param $product
 *
 * @return bool
 */
function liquichainWooCommerceCheckIfNeedShipping($product)
{
    if (
        !wc_shipping_enabled()
        || 0 === wc_get_shipping_method_count(
            true
        )
    ) {
        return false;
    }
    $needs_shipping = false;
    if ($product->is_type('variable')) {
        return false;
    }

    if ($product->needs_shipping()) {
        $needs_shipping = true;
    }

    return $needs_shipping;
}

function checkIndexExistOrDefault($array, $key, $default)
{
    return isset($array[$key]) ? $array[$key] : $default;
}
/**
 * Check if the issuers dropdown for this gateway is enabled.
 *
 * @param string $gatewaySettingsName liquichain_wc_gateway_xxxx_settings
 * @return bool
 */
function liquichainWooCommerceIsDropdownEnabled($gatewaySettingsName)
{
    $gatewaySettings = get_option($gatewaySettingsName);
    $optionValue = checkIndexExistOrDefault($gatewaySettings, 'issuers_dropdown_shown', 'yes');
    return $optionValue == 'yes';
}

/**
 * Check if the Voucher gateway is enabled.
 *
 * @return bool
 */
function liquichainWooCommerceIsVoucherEnabled()
{
    $voucherSettings = get_option('liquichain_wc_gateway_voucher_settings');
    if (!$voucherSettings) {
        $voucherSettings = get_option('liquichain_wc_gateway_mealvoucher_settings');
    }
    return $voucherSettings ? ($voucherSettings['enabled'] == 'yes') : false;
}

/**
 * Check if is a Liquichain gateway
 * @param $gateway string
 *
 * @return bool
*/
function liquichainWooCommerceIsLiquichainGateway($gateway)
{
    if (strpos($gateway, 'liquichain_wc_gateway_') !== false) {
        return true;
    }
    return false;
}

function liquichainWooCommercIsExpiryDateEnabled()
{
    global $wpdb;
    $option = 'liquichain_wc_gateway_%_settings';
    $gatewaySettings = $wpdb->get_results($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name LIKE %s", $option));
    $expiryDateEnabled = false;
    foreach ($gatewaySettings as $gatewaySetting) {
        $values = unserialize($gatewaySetting->option_value);
        if ($values['enabled'] !== 'yes') {
            continue;
        }
        if (!empty($values["activate_expiry_days_setting"]) && $values["activate_expiry_days_setting"] === 'yes') {
            $expiryDateEnabled = true;
        }
    }
    return $expiryDateEnabled;
}

/**
 * Format the value that is sent to Liquichain's API
 * with the required number of decimals
 * depending on the currency used
 *
 * @param $value
 * @param $currency
 * @return string
 */
function liquichainWooCommerceFormatCurrencyValue($value, $currency)
{
    $currenciesWithNoDecimals = ["JPY", "ISK"];
    if(in_array($currency, $currenciesWithNoDecimals)){
        return number_format($value, 0, '.', '');
    }

    return number_format($value, 2, '.', '');
}

function liquichainDeleteWPTranslationFiles()
{
    WP_Filesystem();
    global $wp_filesystem;

    $remote_destination = $wp_filesystem->find_folder(WP_LANG_DIR);
    if (!$wp_filesystem->exists($remote_destination)) {
        return;
    }
    $languageExtensions = [
        'de_DE',
        'de_DE_formal',
        'es_ES',
        'fr_FR',
        'it_IT',
        'nl_BE',
        'nl_NL',
        'nl_NL_formal'
    ];
    $translationExtensions = ['.mo', '.po'];
    $destination = WP_LANG_DIR
        . '/plugins/liquichain-payments-for-woocommerce-';
    foreach ($languageExtensions as $languageExtension) {
        foreach ($translationExtensions as $translationExtension) {
            $file = $destination . $languageExtension
                . $translationExtension;
            $wp_filesystem->delete($file, false);
        }
    }
}
