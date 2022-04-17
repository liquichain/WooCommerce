<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods;

class Kbc extends AbstractPaymentMethod implements PaymentMethodI
{
    protected function getConfig(): array
    {
        return [
            'id' => 'kbc',
            'defaultTitle' => __('KBC/CBC Payment Button', 'liquichain-payments-for-woocommerce'),
            'settingsDescription' => '',
            'defaultDescription' => __('Select your bank', 'liquichain-payments-for-woocommerce'),
            'paymentFields' => true,
            'instructions' => false,
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
        $paymentMethodFormFieds =   [
            'issuers_dropdown_shown' => [
                'title' => __(
                    'Show KBC/CBC banks dropdown',
                    'liquichain-payments-for-woocommerce'
                ),
                'type' => 'checkbox',
                'description' => sprintf(
                    __(
                        'If you disable this, a dropdown with various KBC/CBC banks will not be shown in the WooCommerce checkout, so users will select a KBC/CBC bank on the Liquichain payment page after checkout.',
                        'liquichain-payments-for-woocommerce'
                    ),
                    $this->getProperty('defaultTitle')
                ),
                'default' => 'yes',
            ],
            'issuers_empty_option' => [
                'title' => __(
                    'Issuers empty option',
                    'liquichain-payments-for-woocommerce'
                ),
                'type' => 'text',
                'description' => sprintf(
                    __(
                        'This text will be displayed as the first option in the KBC/CBC issuers drop down, if nothing is entered, "Select your bank" will be shown. Only if the above \'\'Show KBC/CBC banks dropdown\' is enabled.',
                        'liquichain-payments-for-woocommerce'
                    ),
                    $this->getProperty('defaultTitle')
                ),
                'default' => 'Select your bank',
            ],
        ];
        return array_merge($generalFormFields, $paymentMethodFormFieds);
    }
}
