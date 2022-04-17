<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods;

class Sofort extends AbstractPaymentMethod implements PaymentMethodI
{

    protected function getConfig(): array
    {
        return [
            'id' => 'sofort',
            'defaultTitle' => __('SOFORT Banking', 'liquichain-payments-for-woocommerce'),
            'settingsDescription' => '',
            'defaultDescription' => '',
            'paymentFields' => false,
            'instructions' => true,
            'supports' => [
                'products',
                'refunds',
            ],
            'filtersOnBuild' => false,
            'confirmationDelayed' => false,
            'SEPA' => true,
        ];
    }

    public function getFormFields($generalFormFields): array
    {
        return $generalFormFields;
    }
}
