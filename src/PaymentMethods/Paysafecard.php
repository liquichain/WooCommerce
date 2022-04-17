<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods;

class Paysafecard extends AbstractPaymentMethod implements PaymentMethodI
{

    protected function getConfig(): array
    {
        return [
            'id' => 'paysafecard',
            'defaultTitle' => __('paysafecard', 'liquichain-payments-for-woocommerce'),
            'settingsDescription' => '',
            'defaultDescription' => '',
            'paymentFields' => false,
            'instructions' => false,
            'supports' => [],
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
