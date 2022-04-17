<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods;

class Klarnasliceit extends AbstractPaymentMethod implements PaymentMethodI
{

    protected function getConfig(): array
    {
        return [
            'id' => 'klarnasliceit',
            'defaultTitle' => __('Klarna Slice it', 'liquichain-payments-for-woocommerce'),
            'settingsDescription' => __(
                'To accept payments via Klarna, all default WooCommerce checkout fields should be enabled and required.',
                'liquichain-payments-for-woocommerce'
            ),
            'defaultDescription' => '',
            'paymentFields' => false,
            'instructions' => false,
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
