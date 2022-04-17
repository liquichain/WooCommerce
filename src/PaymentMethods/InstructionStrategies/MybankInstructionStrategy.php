<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods\InstructionStrategies;

class MybankInstructionStrategy implements InstructionStrategyI
{

    public function execute(
        $gateway,
        $payment,
        $order = null,
        $admin_instructions = false
    ) {

        if ($payment->isPaid() && $payment->details) {
            return sprintf(
                __(
                /* translators: Placeholder 1: Liquichain_WC_Gateway_MyBank consumer name, placeholder 2: Consumer Account number */
                    'Payment completed by <strong>%1$s</strong> - %2$s',
                    'liquichain-payments-for-woocommerce'
                ),
                $payment->details->consumerName,
                $payment->details->consumerAccount
            );
        }

        $defaultStrategy = new DefaultInstructionStrategy();
        return $defaultStrategy->execute($gateway, $payment, $admin_instructions);
    }
}
