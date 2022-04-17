<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\Shared;

class SharedDataDictionary
{
    public const DIRECTDEBIT = 'liquichain_wc_gateway_directdebit';
    public const GATEWAY_CLASSNAMES = [
        'Liquichain_WC_Gateway_BankTransfer',
        'Liquichain_WC_Gateway_Belfius',
        'Liquichain_WC_Gateway_Creditcard',
        'Liquichain_WC_Gateway_DirectDebit',
        'Liquichain_WC_Gateway_EPS',
        'Liquichain_WC_Gateway_Giropay',
        'Liquichain_WC_Gateway_Ideal',
        'Liquichain_WC_Gateway_Kbc',
        'Liquichain_WC_Gateway_KlarnaPayLater',
        'Liquichain_WC_Gateway_KlarnaSliceIt',
        'Liquichain_WC_Gateway_KlarnaPayNow',
        'Liquichain_WC_Gateway_Bancontact',
        'Liquichain_WC_Gateway_PayPal',
        'Liquichain_WC_Gateway_Paysafecard',
        'Liquichain_WC_Gateway_Przelewy24',
        'Liquichain_WC_Gateway_Sofort',
        'Liquichain_WC_Gateway_Giftcard',
        'Liquichain_WC_Gateway_ApplePay',
        'Liquichain_WC_Gateway_MyBank',
        'Liquichain_WC_Gateway_Voucher',
    ];

    public const MOLLIE_OPTIONS_NAMES = [
        'liquichain_components_::placeholder',
        'liquichain_components_backgroundColor',
        'liquichain_components_color',
        'liquichain_components_fontSize',
        'liquichain_components_fontWeight',
        'liquichain_components_invalid_backgroundColor',
        'liquichain_components_invalid_color',
        'liquichain_components_letterSpacing',
        'liquichain_components_lineHeight',
        'liquichain_components_padding',
        'liquichain_components_textAlign',
        'liquichain_components_textTransform',
        'liquichain_wc_fix_subscriptions',
        'liquichain_wc_fix_subscriptions2',
        'liquichain-db-version',
        'liquichain-payments-for-woocommerce_api_payment_description',
        'liquichain-payments-for-woocommerce_api_switch',
        'liquichain-payments-for-woocommerce_customer_details',
        'liquichain-payments-for-woocommerce_debug',
        'liquichain-payments-for-woocommerce_gatewayFeeLabel',
        'liquichain-payments-for-woocommerce_live_api_key',
        'liquichain-payments-for-woocommerce_order_status_cancelled_payments',
        'liquichain-payments-for-woocommerce_payment_locale',
        'liquichain-payments-for-woocommerce_profile_merchant_id',
        'liquichain-payments-for-woocommerce_test_api_key',
        'liquichain-payments-for-woocommerce_test_mode_enabled',
        'liquichain_apple_pay_button_enabled_product',
        'liquichain_apple_pay_button_enabled_cart',
        'liquichain_wc_applepay_validated',
        'liquichain-payments-for-woocommerce_removeOptionsAndTransients'
    ];
}
