<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods\InstructionStrategies;

class PaypalInstructionStrategy implements InstructionStrategyI
{

    public function execute(
        $gateway,
        $payment,
        $order = null,
        $admin_instructions = false
    ) {

        if ($payment->isPaid() && $payment->details) {
            return sprintf(
            /* translators: Placeholder 1: PayPal consumer name, placeholder 2: PayPal email, placeholder 3: PayPal transaction ID */
                __("Payment completed by <strong>%1\$s</strong> - %2\$s (PayPal transaction ID: %3\$s)", 'liquichain-payments-for-woocommerce'),
                $payment->details->consumerName,
                $payment->details->consumerAccount,
                $payment->details->paypalReference
            );
        }
        $defaultStrategy = new DefaultInstructionStrategy();
        return $defaultStrategy->execute($gateway, $payment, $admin_instructions);
    }
}
