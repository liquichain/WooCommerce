<?php

use Liquichain\WooCommerce\Payment\PaymentService;
use Liquichain\WooCommerce\Settings\Settings;

$pluginName = 'liquichain-payments-for-woocommerce';
$nonce_liquichain_cleanDb = wp_create_nonce('nonce_liquichain_cleanDb');
$cleanDB_liquichain_url = add_query_arg(
    ['cleanDB-liquichain' => 1, 'nonce_liquichain_cleanDb' => $nonce_liquichain_cleanDb]
);
$api_payment_description_labels = [
    '{orderNumber}' => _x( 'Order number', 'Label {orderNumber} description for payment description options', 'liquichain-payments-for-woocommerce' ),
    '{storeName}' => _x( 'Site Title', 'Label {storeName} description for payment description options', 'liquichain-payments-for-woocommerce' ),
    '{customer.firstname}' => _x( 'Customer\'s first name', 'Label {customer.firstname} description for payment description options', 'liquichain-payments-for-woocommerce' ),
    '{customer.lastname}' => _x( 'Customer\'s last name', 'Label {customer.lastname} description for payment description options', 'liquichain-payments-for-woocommerce' ),
    '{customer.company}' => _x( 'Customer\'s company name', 'Label {customer.company} description for payment description options', 'liquichain-payments-for-woocommerce' )
];

return [
    [
        'id' => $pluginName . '_' . 'title',
        'title' => __('Liquichain advanced settings', 'liquichain-payments-for-woocommerce'),
        'type' => 'title',
        'desc' => '<p>' . __('The following options are required to use the plugin and are used by all Liquichain payment methods', 'liquichain-payments-for-woocommerce') . '</p>',
    ],
    [
        'id' => $pluginName . '_' . 'order_status_cancelled_payments',
        'title' => __('Order status after cancelled payment', 'liquichain-payments-for-woocommerce'),
        'type' => 'select',
        'options' => [
            'pending' => __('Pending', 'woocommerce'),
            'cancelled' => __('Cancelled', 'woocommerce'),
        ],
        'desc' => __('Status for orders when a payment (not a Liquichain order via the Orders API) is cancelled. Default: pending. Orders with status Pending can be paid with another payment method, customers can try again. Cancelled orders are final. Set this to Cancelled if you only have one payment method or don\'t want customers to re-try paying with a different payment method. This doesn\'t apply to payments for orders via the new Orders API and Klarna payments.', 'liquichain-payments-for-woocommerce'),
        'default' => 'pending',
    ],
    [
        'id' => $pluginName . '_' . Settings::SETTING_NAME_PAYMENT_LOCALE,
        'title' => __('Payment screen language', 'liquichain-payments-for-woocommerce'),
        'type' => 'select',
        'options' => [
            Settings::SETTING_LOCALE_WP_LANGUAGE => __(
                'Automatically send WordPress language',
                'liquichain-payments-for-woocommerce'
            ) . ' (' . __('default', 'liquichain-payments-for-woocommerce') . ')',
            Settings::SETTING_LOCALE_DETECT_BY_BROWSER => __(
                'Detect using browser language',
                'liquichain-payments-for-woocommerce'
            ),
            'en_US' => __('English', 'liquichain-payments-for-woocommerce'),
            'nl_NL' => __('Dutch', 'liquichain-payments-for-woocommerce'),
            'nl_BE' => __('Flemish (Belgium)', 'liquichain-payments-for-woocommerce'),
            'fr_FR' => __('French', 'liquichain-payments-for-woocommerce'),
            'fr_BE' => __('French (Belgium)', 'liquichain-payments-for-woocommerce'),
            'de_DE' => __('German', 'liquichain-payments-for-woocommerce'),
            'de_AT' => __('Austrian German', 'liquichain-payments-for-woocommerce'),
            'de_CH' => __('Swiss German', 'liquichain-payments-for-woocommerce'),
            'es_ES' => __('Spanish', 'liquichain-payments-for-woocommerce'),
            'ca_ES' => __('Catalan', 'liquichain-payments-for-woocommerce'),
            'pt_PT' => __('Portuguese', 'liquichain-payments-for-woocommerce'),
            'it_IT' => __('Italian', 'liquichain-payments-for-woocommerce'),
            'nb_NO' => __('Norwegian', 'liquichain-payments-for-woocommerce'),
            'sv_SE' => __('Swedish', 'liquichain-payments-for-woocommerce'),
            'fi_FI' => __('Finnish', 'liquichain-payments-for-woocommerce'),
            'da_DK' => __('Danish', 'liquichain-payments-for-woocommerce'),
            'is_IS' => __('Icelandic', 'liquichain-payments-for-woocommerce'),
            'hu_HU' => __('Hungarian', 'liquichain-payments-for-woocommerce'),
            'pl_PL' => __('Polish', 'liquichain-payments-for-woocommerce'),
            'lv_LV' => __('Latvian', 'liquichain-payments-for-woocommerce'),
            'lt_LT' => __('Lithuanian', 'liquichain-payments-for-woocommerce'),
        ],
        'desc' => sprintf(
            __('Sending a language (or locale) is required. The option \'Automatically send WordPress language\' will try to get the customer\'s language in WordPress (and respects multilanguage plugins) and convert it to a format Liquichain understands. If this fails, or if the language is not supported, it will fall back to American English. You can also select one of the locales currently supported by Liquichain, that will then be used for all customers.', 'liquichain-payments-for-woocommerce'),
            '<a href="https://www.liquichain.io/nl/docs/reference/payments/create" target="_blank">',
            '</a>'
        ),
        'default' => Settings::SETTING_LOCALE_WP_LANGUAGE,
    ],
    [
        'id' => $pluginName . '_' . 'customer_details',
        'title' => __('Store customer details at Liquichain', 'liquichain-payments-for-woocommerce'),
        /* translators: Placeholder 1: enabled or disabled */
        'desc' => sprintf(
            __(
                'Should Liquichain store customers name and email address for Single Click Payments? Default <code>%1$s</code>. Required if WooCommerce Subscriptions is being used! Read more about <a href="https://help.liquichain.io/hc/en-us/articles/115000671249-What-are-single-click-payments-and-how-does-it-work-">%2$s</a> and how it improves your conversion.',
                'liquichain-payments-for-woocommerce'
            ),
            strtolower(__('Enabled', 'liquichain-payments-for-woocommerce')),
            __('Single Click Payments', 'liquichain-payments-for-woocommerce')
        ),
        'type' => 'checkbox',
        'default' => 'yes',

    ],
    [
        'id' => $pluginName . '_' . 'api_switch',
        'title' => __(
            'Select API Method',
            'liquichain-payments-for-woocommerce'
        ),
        'type' => 'select',
        'options' => [
            PaymentService::PAYMENT_METHOD_TYPE_ORDER => ucfirst(
                    PaymentService::PAYMENT_METHOD_TYPE_ORDER
            ) . ' (' . __('default', 'liquichain-payments-for-woocommerce')
                . ')',
            PaymentService::PAYMENT_METHOD_TYPE_PAYMENT => ucfirst(
                PaymentService::PAYMENT_METHOD_TYPE_PAYMENT
            ),
        ],
        'default' => PaymentService::PAYMENT_METHOD_TYPE_ORDER,
        /* translators: Placeholder 1: opening link tag, placeholder 2: closing link tag */
        'desc' => sprintf(
            __(
                'Click %1$shere%2$s to read more about the differences between the Payments and Orders API',
                'liquichain-payments-for-woocommerce'
            ),
            '<a href="https://docs.liquichain.io/orders/why-use-orders" target="_blank">',
            '</a>'
        ),
    ],
    [
        'id' => $pluginName . '_' . 'api_payment_description',
        'title' => __(
            'API Payment Description',
            'liquichain-payments-for-woocommerce'
        ),
        'type' => 'text',
        'default' => '{orderNumber}',
        'desc' => sprintf(
            '</p>
            <div class="available-payment-description-labels hide-if-no-js">
                <p>%1$s:</p>
                <ul role="list">
                    %2$s
                </ul>
            </div>
            <br style="clear: both;" />
            <p class="description">%3$s',

            _x( 'Available variables', 'Payment description options', 'liquichain-payments-for-woocommerce' ),
            implode( '', array_map(
                function ($label, $label_description) {
                    return sprintf(
                        '<li style="float: left; margin-right: 5px;">
                            <button type="button"
                                class="liquichain-settings-advanced-payment-desc-label button button-secondary button-small"
                                data-tag="%1$s"
                                aria-label="%2$s"
                                title="%3$s"
                            >
                                %1$s
                            </button>
                        </li>',

                        $label,
                        substr( $label, 1, -1 ),
                        $label_description
                    );
                },
                array_keys( $api_payment_description_labels ),
                $api_payment_description_labels
            ) ),
            /* translators: Placeholder 1: Opening paragraph tag, placeholder 2: Closing paragraph tag */
            sprintf(
                __(
                    'Select among the available variables the description to be used for this transaction.%1$s(Note: this only works when the method is set to Payments API)%2$s',
                    'liquichain-payments-for-woocommerce'
                ),
                '<p>',
                '</p>'
            )
        ),
    ],
    [
        'id' => $pluginName . '_' . 'gatewayFeeLabel',
        'title' => __(
            'Surcharge gateway fee label',
            'liquichain-payments-for-woocommerce'
        ),
        'type' => 'text',
        'custom_attributes' => ['maxlength' => '30'],
        'default' => __('Gateway Fee', 'liquichain-payments-for-woocommerce'),
        'desc' => sprintf(
            __(
                'This is the label will appear in frontend when the surcharge applies',
                'liquichain-payments-for-woocommerce'
            )
        ),
    ],
    [
        'id' => $pluginName . '_' . 'removeOptionsAndTransients',
        'title' => __(
            'Remove Liquichain data from Database on uninstall',
            'liquichain-payments-for-woocommerce'
        ),
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __("Remove options and scheduled actions from database when uninstalling the plugin.", "liquichain-payments-for-woocommerce") . ' (<a href="' . esc_attr($cleanDB_liquichain_url) . '">' . strtolower(
                __('Clear now', 'liquichain-payments-for-woocommerce')
            ) . '</a>)',
    ],
    [
        'id' => $pluginName . '_' . 'sectionend',
        'type' => 'sectionend',
    ],
];
