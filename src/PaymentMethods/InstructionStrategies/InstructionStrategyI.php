<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods\InstructionStrategies;

interface InstructionStrategyI
{
    public function execute(
        $gateway,
        $payment,
        $order,
        $admin_instructions = false
    );
}
