<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods;

class Mybank extends AbstractPaymentMethod implements PaymentMethodI
{

    protected function getConfig(): array
    {
        return [
            'id' => 'mybank',
            'defaultTitle' => __('MyBank', 'liquichain-payments-for-woocommerce'),
            'settingsDescription' => __('To accept payments via MyBank', 'liquichain-payments-for-woocommerce'),
            'defaultDescription' => '',
            'paymentFields' => false,
            'instructions' => true,
            'supports' => [
                'products',
                'refunds',
                ],
            'filtersOnBuild' => false,
            'confirmationDelayed' => false,
            'SEPA' => false,
        ];
    }

    public function getFormFields($generalFormFields): array
    {
        return $generalFormFields;
    }
}
