<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods;

class Applepay extends AbstractPaymentMethod implements PaymentMethodI
{
    protected function getConfig(): array
    {
        return [
            'id' => 'applepay',
            'defaultTitle' => __('Apple Pay', 'liquichain-payments-for-woocommerce'),
            'settingsDescription' => __('To accept payments via Apple Pay', 'liquichain-payments-for-woocommerce'),
            'defaultDescription' => '',
            'paymentFields' => false,
            'instructions' => true,
            'supports' => [
                'products',
                'refunds',
                'subscriptions',
            ],
            'filtersOnBuild' => false,
            'confirmationDelayed' => false,
            'SEPA' => false,
            'Subscription' => true,
        ];
    }

    public function getFormFields($generalFormFields): array
    {
        $paymentMethodFormFieds = [
            'liquichain_apple_pay_button_enabled_cart' => [
                'title' => __('Enable Apple Pay Button on Cart page', 'liquichain-payments-for-woocommerce'),
                /* translators: Placeholder 1: enabled or disabled */
                'desc' => __(
                    'Enable the Apple Pay direct buy button on the Cart page',
                    'liquichain-payments-for-woocommerce'
                ),
                'type' => 'checkbox',
                'default' => 'no',
            ],
            'liquichain_apple_pay_button_enabled_product' => [
                'title' => __('Enable Apple Pay Button on Product page', 'liquichain-payments-for-woocommerce'),
                /* translators: Placeholder 1: enabled or disabled */
                'desc' => __(
                    'Enable the Apple Pay direct buy button on the Product page',
                    'liquichain-payments-for-woocommerce'
                ),
                'type' => 'checkbox',
                'default' => 'no',
            ],
        ];
        return array_merge($generalFormFields, $paymentMethodFormFieds);
    }
}
