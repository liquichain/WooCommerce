<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods;

class Creditcard extends AbstractPaymentMethod implements PaymentMethodI
{
    protected function getConfig(): array
    {
        return [
            'id' => 'creditcard',
            'defaultTitle' => __('Credit card', 'liquichain-payments-for-woocommerce'),
            'settingsDescription' => '',
            'defaultDescription' => __('', 'liquichain-payments-for-woocommerce'),
            'paymentFields' => $this->hasPaymentFields(),
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
        $componentFields = $this->includeLiquichainComponentsFields($generalFormFields);
        return $this->includeCreditCardIconSelector($componentFields);
    }

    protected function hasPaymentFields()
    {
        return $this->getProperty('liquichain_components_enabled') === 'yes';
    }

    protected function includeLiquichainComponentsFields($generalFormFields)
    {
        $fields = [
            'liquichain_components_enabled' => [
                'type' => 'checkbox',
                'title' => __('Enable Liquichain Components', 'liquichain-payments-for-woocommerce'),
                /* translators: Placeholder 1: Liquichain Components.*/
                'description' => sprintf(
                    __(
                        'Use the Liquichain Components for this Gateway. Read more about <a href="https://www.liquichain.io/en/news/post/better-checkout-flows-with-liquichain-components">%s</a> and how it improves your conversion.',
                        'liquichain-payments-for-woocommerce'
                    ),
                    __('Liquichain Components', 'liquichain-payments-for-woocommerce')
                ),
                'default' => 'no',
            ],
        ];

        return array_merge($generalFormFields, $fields);
    }

    /**
     * Include the credit card icon selector customization in the credit card
     * settings page
     */
    protected function includeCreditCardIconSelector($componentFields)
    {
        $fields = $this->creditcardIconsSelectorFields();
        $fields && ($componentFields = array_merge($componentFields, $fields));
        return $componentFields;
    }

    private function creditcardIconsSelectorFields(): array
    {
        return [
            [
                'title' => __('Customize Icons', 'liquichain-payments-for-woocommerce'),
                'type' => 'title',
                'desc' => '',
                'id' => 'customize_icons',
            ],
            'liquichain_creditcard_icons_enabler' => [
                'type' => 'checkbox',
                'title' => __('Enable Icons Selector', 'liquichain-payments-for-woocommerce'),
                'description' => __(
                    'Show customized creditcard icons on checkout page',
                    'liquichain-payments-for-woocommerce'
                ),
                'checkboxgroup' => 'start',
                'default' => 'no',
            ],
            'liquichain_creditcard_icons_amex' => [
                'label' => __('Show American Express Icon', 'liquichain-payments-for-woocommerce'),
                'type' => 'checkbox',
                'default' => 'no',
            ],
            'liquichain_creditcard_icons_cartasi' => [
                'label' => __('Show Carta Si Icon', 'liquichain-payments-for-woocommerce'),
                'type' => 'checkbox',
                'default' => 'no',
            ],
            'liquichain_creditcard_icons_cartebancaire' => [
                'label' => __('Show Carte Bancaire Icon', 'liquichain-payments-for-woocommerce'),
                'type' => 'checkbox',
                'default' => 'no',
            ],
            'liquichain_creditcard_icons_maestro' => [
                'label' => __('Show Maestro Icon', 'liquichain-payments-for-woocommerce'),
                'type' => 'checkbox',
                'default' => 'no',
            ],
            'liquichain_creditcard_icons_mastercard' => [
                'label' => __('Show Mastercard Icon', 'liquichain-payments-for-woocommerce'),
                'type' => 'checkbox',
                'default' => 'no',
            ],
            'liquichain_creditcard_icons_visa' => [
                'label' => __('Show Visa Icon', 'liquichain-payments-for-woocommerce'),
                'type' => 'checkbox',
                'default' => 'no',
            ],
            'liquichain_creditcard_icons_vpay' => [
                'label' => __('Show VPay Icon', 'liquichain-payments-for-woocommerce'),
                'type' => 'checkbox',
                'checkboxgroup' => 'end',
                'default' => 'no',
            ],
        ];
    }
}
