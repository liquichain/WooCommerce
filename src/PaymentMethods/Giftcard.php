<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods;

class Giftcard extends AbstractPaymentMethod implements PaymentMethodI
{
    /**
     * Method to print the giftcard payment details on debug and order note
     *
     * @param           $payment
     * @param \WC_Order  $order
     *
     */
    public function debugGiftcardDetails(
        $payment,
        \WC_Order $order
    ) {

        $details = $payment->details;
        if (!$details) {
            return;
        }
        $orderNoteLine = "";
        foreach ($details->giftcards as $giftcard) {
            $orderNoteLine .= sprintf(
                esc_html_x(
                    'Liquichain - Giftcard details: %1$s %2$s %3$s.',
                    'Placeholder 1: giftcard issuer, Placeholder 2: amount value, Placeholder 3: currency',
                    'liquichain-payments-for-woocommerce'
                ),
                $giftcard->issuer,
                $giftcard->amount->value,
                $giftcard->amount->currency
            );
        }
        if ($details->remainderMethod) {
            $orderNoteLine .= sprintf(
                esc_html_x(
                    ' Remainder: %1$s %2$s %3$s.',
                    'Placeholder 1: remainder method, Placeholder 2: amount value, Placeholder 3: currency',
                    'liquichain-payments-for-woocommerce'
                ),
                $details->remainderMethod,
                $details->remainderAmount->value,
                $details->remainderAmount->currency
            );
        }

        $order->add_order_note($orderNoteLine);
    }

    protected function getConfig(): array
    {
        return [
            'id' => 'giftcard',
            'defaultTitle' => __('Gift cards', 'liquichain-payments-for-woocommerce'),
            'settingsDescription' => '',
            'defaultDescription' => __('Select your gift card', 'liquichain-payments-for-woocommerce'),
            'paymentFields' => true,
            'instructions' => false,
            'supports' => [
                'products',
            ],
            'filtersOnBuild' => false,
            'confirmationDelayed' => false,
            'SEPA' => false,
        ];
    }

    public function getFormFields($generalFormFields): array
    {
        $paymentMethodFormFieds =  [
            'issuers_dropdown_shown' => [
                'title' => __(
                    'Show gift cards dropdown',
                    'liquichain-payments-for-woocommerce'
                ),
                'type' => 'checkbox',
                'description' => sprintf(
                    __(
                        'If you disable this, a dropdown with various gift cards will not be shown in the WooCommerce checkout, so users will select a gift card on the Liquichain payment page after checkout.',
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
                        "This text will be displayed as the first option in the gift card dropdown, but only if the above 'Show gift cards dropdown' is enabled.",
                        'liquichain-payments-for-woocommerce'
                    ),
                    $this->getProperty('defaultTitle')
                ),
                'default' => '',
            ],
        ];
        return array_merge($generalFormFields, $paymentMethodFormFieds);
    }
}
