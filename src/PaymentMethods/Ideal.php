<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods;

class Ideal extends AbstractPaymentMethod implements PaymentMethodI
{
    public function getConfig(): array
    {
        return [
            'id' => 'ideal',
            'defaultTitle' => __('iDEAL', 'liquichain-payments-for-woocommerce'),
            'settingsDescription' => '',
            'defaultDescription' => __('Select your bank', 'liquichain-payments-for-woocommerce'),
            'paymentFields' => true,
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
        $paymentMethodFormFieds =  [
            'issuers_dropdown_shown' => [
                'title' => __('Show iDEAL banks dropdown', 'liquichain-payments-for-woocommerce'),
                'type' => 'checkbox',
                'description' => sprintf(
                    __(
                        'If you disable this, a dropdown with various iDEAL banks
                         will not be shown in the WooCommerce checkout,
                         so users will select a iDEAL bank on the Liquichain payment page after checkout.',
                        'liquichain-payments-for-woocommerce'
                    ),
                    $this->getProperty('defaultTitle')
                ),
                'default' => 'yes',
            ],
            'issuers_empty_option' => [
                'title' => __('Issuers empty option', 'liquichain-payments-for-woocommerce'),
                'type' => 'text',
                'description' => sprintf(
                    __(
                        'This text will be displayed as the first option in the iDEAL issuers drop down,
                         if nothing is entered, "Select your bank" will be shown. Only if the above 
                         \'Show iDEAL banks dropdown\' is enabled.',
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
