<?php

namespace Liquichain\WooCommerce\Settings\General;

use Liquichain\WooCommerce\Gateway\LiquichainPaymentGateway;
use Liquichain\WooCommerce\Gateway\Surcharge;

class LiquichainGeneralSettings
{
    public function gatewayFormFields(
        $defaultTitle,
        $defaultDescription,
        $paymentConfirmation
    ) {

        $formFields = [
            'enabled' => [
                'title' => __(
                    'Enable/Disable',
                    'liquichain-payments-for-woocommerce'
                ),
                'type' => 'checkbox',
                /* translators: Placeholder 1: Gateway title */
                'label' => sprintf(
                    __('Enable %s', 'liquichain-payments-for-woocommerce'),
                    $defaultTitle
                ),
                'default' => 'yes',
            ],
            [
                'id' => $defaultTitle . '_' . 'title',
                'title' => sprintf(
                    /* translators: Placeholder 1: Gateway title */
                    __('%s display settings', 'liquichain-payments-for-woocommerce'), $defaultTitle),
                'type' => 'title',
            ],
            'title' => [
                'title' => __('Title', 'liquichain-payments-for-woocommerce'),
                'type' => 'text',
                /* translators: Placeholder 1: Gateway title */
                'description' => sprintf(
                    __(
                        'This controls the title which the user sees during checkout. Default <code>%s</code>',
                        'liquichain-payments-for-woocommerce'
                    ),
                    $defaultTitle
                ),
                'default' => $defaultTitle,
                'desc_tip' => true,
            ],
            'description' => [
                'title' => __('Description', 'liquichain-payments-for-woocommerce'),
                'type' => 'textarea',
                /* translators: Placeholder 1: Gateway description */
                'description' => sprintf(
                    __(
                        'Payment method description that the customer will see on your checkout. Default <code>%s</code>',
                        'liquichain-payments-for-woocommerce'
                    ),
                    $defaultDescription
                ),
                'default' => $defaultDescription,
                'desc_tip' => true,
            ],
            'display_logo' => [
                'title' => __(
                    'Display logo',
                    'liquichain-payments-for-woocommerce'
                ),
                'type' => 'checkbox',
                'label' => __(
                    'Display logo on checkout page. Default <code>enabled</code>',
                    'liquichain-payments-for-woocommerce'
                ),
                'default' => 'yes',
            ],
            [
                'id' => $defaultTitle . '_' . 'title',
                'title' => sprintf(__(
                    'Sales countries',
                    'liquichain-payments-for-woocommerce'
                )),
                'type' => 'title',
            ],
            'allowed_countries' => [
                'title' => __(
                    'Sell to specific countries',
                    'liquichain-payments-for-woocommerce'
                ),
                'desc' => '',
                'css' => 'min-width: 350px;',
                'default' => [],
                'type' => 'multi_select_countries',
            ],
            [
                'id' => $defaultTitle . '_' . 'custom_logo',
                'title' => sprintf(
                /* translators: Placeholder 1: Gateway title */
                    __('%s custom logo', 'liquichain-payments-for-woocommerce'), $defaultTitle),
                'type' => 'title',
            ],
            'enable_custom_logo' => [
                'title' => __(
                    'Enable custom logo',
                    'liquichain-payments-for-woocommerce'
                ),
                'type' => 'checkbox',
                'label' => __(
                    'Enable the feature to add a custom logo for this gateway. This feature will have precedence over other logo options.',
                    'liquichain-payments-for-woocommerce'
                ),
            ],
            'upload_logo' => [
                'title' => __(
                    'Upload custom logo',
                    'liquichain-payments-for-woocommerce'
                ),
                'type' => 'file',
                'custom_attributes' => ['accept' => '.png, .jpeg, .svg, image/png, image/jpeg'],
                'description' => sprintf(
                    __(
                        'Upload a custom icon for this gateway. The feature must be enabled.',
                        'liquichain-payments-for-woocommerce'
                    )
                ),
                'desc_tip' => true,
            ],
            [
                'id' => $defaultTitle . '_' . 'surcharge',
                'title' => sprintf(
                /* translators: Placeholder 1: Gateway title */
                    __('%s surcharge', 'liquichain-payments-for-woocommerce'),
                    $defaultTitle),
                'type' => 'title',
            ],
            'payment_surcharge' => [
                'title' => __(
                    'Payment Surcharge',
                    'liquichain-payments-for-woocommerce'
                ),
                'type' => 'select',
                'options' => [
                    Surcharge::NO_FEE => __(
                        'No fee',
                        'liquichain-payments-for-woocommerce'
                    ),
                    Surcharge::FIXED_FEE => __(
                        'Fixed fee',
                        'liquichain-payments-for-woocommerce'
                    ),
                    Surcharge::PERCENTAGE => __(
                        'Percentage',
                        'liquichain-payments-for-woocommerce'
                    ),
                    Surcharge::FIXED_AND_PERCENTAGE => __(
                        'Fixed fee and percentage',
                        'liquichain-payments-for-woocommerce'
                    ),
                ],
                'default' => 'no_fee',
                'description' => __(
                    'Choose a payment surcharge for this gateway',
                    'liquichain-payments-for-woocommerce'
                ),
                'desc_tip' => true,
            ],
            'fixed_fee' => [
                'title' => sprintf(
                /* translators: Placeholder 1: currency */
                    __('Payment surcharge fixed amount in %s', 'liquichain-payments-for-woocommerce'),
                    html_entity_decode(get_woocommerce_currency_symbol())),
                'type' => 'number',
                'description' => sprintf(
                    __(
                        'Control the fee added on checkout. Default 0.00',
                        'liquichain-payments-for-woocommerce'
                    )
                ),
                'custom_attributes' => ['step' => '0.01', 'min' => '0.00', 'max' => '999'],
                'default' => '0.00',
                'desc_tip' => true,
            ],
            'percentage' => [
                'title' => __('Payment surcharge percentage amount %', 'liquichain-payments-for-woocommerce'),
                'type' => 'number',
                'description' => sprintf(
                    __(
                        'Control the percentage fee added on checkout. Default 0.00',
                        'liquichain-payments-for-woocommerce'
                    )
                ),
                'custom_attributes' => ['step' => '0.01', 'min' => '0.00', 'max' => '999'],
                'default' => '0.00',
                'desc_tip' => true,
            ],
            'surcharge_limit' => [
                /* translators: Placeholder 1: currency */
                'title' => sprintf(__('Payment surcharge limit in %s', 'liquichain-payments-for-woocommerce'), html_entity_decode(get_woocommerce_currency_symbol())),
                'type' => 'number',
                'description' => sprintf(
                    __(
                        'Limit the maximum fee added on checkout. Default 0, means no limit',
                        'liquichain-payments-for-woocommerce'
                    )
                ),
                'custom_attributes' => ['step' => '0.01', 'min' => '0.00', 'max' => '999'],
                'default' => '0.00',
                'desc_tip' => true,
            ],
            'maximum_limit' => [
                /* translators: Placeholder 1: currency */
                'title' => sprintf(__('Surcharge only under this limit, in %s', 'liquichain-payments-for-woocommerce'), html_entity_decode(get_woocommerce_currency_symbol())),
                'type' => 'number',
                'description' => sprintf(
                    __(
                        'Maximum order amount to apply surcharge. If the order is above this number the surcharge will not apply. Default 0, means no maximum',
                        'liquichain-payments-for-woocommerce'
                    )
                ),
                'custom_attributes' => ['step' => '0.01', 'min' => '0.00', 'max' => '999'],
                'default' => '0.00',
                'desc_tip' => true,
            ],
            [
                'id' => $defaultTitle . '_' . 'advanced',
                'title' => sprintf(
                /* translators: Placeholder 1: gateway title */
                    __('%s advanced', 'liquichain-payments-for-woocommerce'),
                    $defaultTitle),
                'type' => 'title',
            ],
            'activate_expiry_days_setting' => [
                'title' => __('Activate expiry date setting', 'liquichain-payments-for-woocommerce'),
                'label' => __('Enable expiry date for payments', 'liquichain-payments-for-woocommerce'),
                'description' => __('Enable this option if you want to be able to set the number of days after the order will expire.', 'liquichain-payments-for-woocommerce'),
                'type' => 'checkbox',
                'default' => 'no',
            ],
            'order_dueDate' => [
                'title' => sprintf(__('Expiry date', 'liquichain-payments-for-woocommerce')),
                'type' => 'number',
                'description' => sprintf(
                    __(
                        'Number of MINUTES after the order will expire and will be canceled at Liquichain and WooCommerce. A value of 0 means no expiry date will be considered.',
                        'liquichain-payments-for-woocommerce'
                    )
                ),
                'custom_attributes' => ['step' => '1', 'min' => '0', 'max' => '526000'],
                'default' => '0',
                'desc_tip' => true,
            ],
        ];

        if ($paymentConfirmation) {
            $formFields['initial_order_status'] = [
                'title' => __(
                    'Initial order status',
                    'liquichain-payments-for-woocommerce'
                ),
                'type' => 'select',
                'options' => [
                    LiquichainPaymentGateway::STATUS_ON_HOLD => wc_get_order_status_name(
                        LiquichainPaymentGateway::STATUS_ON_HOLD
                    ) . ' (' . __(
                        'default',
                        'liquichain-payments-for-woocommerce'
                    ) . ')',
                    LiquichainPaymentGateway::STATUS_PENDING => wc_get_order_status_name(
                        LiquichainPaymentGateway::STATUS_PENDING
                    ),
                ],
                'default' => LiquichainPaymentGateway::STATUS_ON_HOLD,
                /* translators: Placeholder 1: Default order status, placeholder 2: Link to 'Hold Stock' setting */
                'description' => sprintf(
                    __(
                        'Some payment methods take longer than a few hours to complete. The initial order state is then set to \'%1$s\'. This ensures the order is not cancelled when the setting %2$s is used.',
                        'liquichain-payments-for-woocommerce'
                    ),
                    wc_get_order_status_name(
                        LiquichainPaymentGateway::STATUS_ON_HOLD
                    ),
                    '<a href="' . admin_url(
                        'admin.php?page=wc-settings&tab=products&section=inventory'
                    ) . '" target="_blank">' . __(
                        'Hold Stock (minutes)',
                        'woocommerce'
                    ) . '</a>'
                ),
            ];
        }

        return $formFields;
    }
}
