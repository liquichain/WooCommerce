<?php

$pluginName = 'liquichain-payments-for-woocommerce';
$title = 'Apple Pay';
$description = 'Apple description';
$pluginId = 'liquichain-payments-for-woocommerce';
$applePayOption = get_option('liquichain_wc_gateway_applepay_settings');

return [
    [
        'id' => $title . '_' . 'title',
        'title' => __('Apple Pay', 'liquichain-payments-for-woocommerce'),
        'type' => 'title',
        'desc' => '<p>' . __('The following options are required to use the Apple Pay gateway', 'liquichain-payments-for-woocommerce') . '</p>',
    ],

    [
        'id' => 'enabled',
        'title' => __('Enable/Disable', 'liquichain-payments-for-woocommerce'),
        /* translators: Placeholder 1: Gateway title */
        'desc' => sprintf(__('Enable %s', 'liquichain-payments-for-woocommerce'), $title),
        'type' => 'checkbox',
        'default' =>  'yes',
        'value' => isset($applePayOption['enabled']) ? $applePayOption['enabled'] : 'yes',

    ],
    [
        'id' => 'title',
        'title' => __('Title', 'liquichain-payments-for-woocommerce'),
        /* translators: Placeholder 1: Gateway title */
        'desc' => sprintf(
            __(
                'This controls the title which the user sees during checkout. Default <code>%s</code>',
                'liquichain-payments-for-woocommerce'
            ),
            $title
        ),
        'desc_tip' => true,
        'type' => 'text',
        'default' =>  $title,
        'value' => isset($applePayOption['title']) ? $applePayOption['title'] : $title,

    ],
    [
        'id' => 'display_logo',
        'title' => __('Display logo', 'liquichain-payments-for-woocommerce'),
        'desc' => sprintf(
            __(
                'Display logo',
                'liquichain-payments-for-woocommerce'
            )
        ),
        'desc_tip' => true,
        'type' => 'checkbox',
        'default' => 'yes',
        'value' => isset($applePayOption['display_logo']) ? $applePayOption['display_logo'] : 'yes',

    ],
    [
        'id' => 'description',
        'title' => __('Description', 'liquichain-payments-for-woocommerce'),
        /* translators: Placeholder 1: Gateway description */
        'desc' => sprintf(
            __(
                'Payment method description that the customer will see on your checkout. Default <code>%s</code>',
                'liquichain-payments-for-woocommerce'
            ),
            $description
        ),
        'desc_tip' => true,
        'type' => 'text',
        'default' => $description,
        'value' => isset($applePayOption['description']) ? $applePayOption['description'] : $description,
    ],
    [
        'id' => $pluginId . '_' . 'sectionend',
        'type' => 'sectionend',
    ],
    [
        'id' => $title . '_' . 'title_button',
        'title' =>  __(
            'Apple Pay button settings',
            'liquichain-payments-for-woocommerce'
        ),
        'type' => 'title',
        'desc' => '<p>' . __('The following options are required to use the Apple Pay Direct Button', 'liquichain-payments-for-woocommerce') . '</p>',
    ],
    [
        'id' => 'liquichain_apple_pay_button_enabled_cart',
        'title' => __('Enable Apple Pay Button on Cart page', 'liquichain-payments-for-woocommerce'),
        /* translators: Placeholder 1: enabled or disabled */
        'desc' => sprintf(
            __(
                'Enable the Apple Pay direct buy button on the Cart page',
                'liquichain-payments-for-woocommerce'
            ),
            $description
        ),
        'type' => 'checkbox',
        'default' => 'no',
        'value' => isset($applePayOption['liquichain_apple_pay_button_enabled_cart']) ? $applePayOption['liquichain_apple_pay_button_enabled_cart'] : 'no',

    ],
    [
        'id' => 'liquichain_apple_pay_button_enabled_product',
        'title' => __('Enable Apple Pay Button on Product page', 'liquichain-payments-for-woocommerce'),
        /* translators: Placeholder 1: enabled or disabled */
        'desc' => sprintf(
            __(
                'Enable the Apple Pay direct buy button on the Product page',
                'liquichain-payments-for-woocommerce'
            ),
            $description
        ),
        'type' => 'checkbox',
        'default' => 'no',
        'value' => isset($applePayOption['liquichain_apple_pay_button_enabled_product']) ? $applePayOption['liquichain_apple_pay_button_enabled_product'] : 'no',

    ],
    [
        'id' => $pluginName . '_' . 'sectionend',
        'type' => 'sectionend',
    ],
];
