<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\Payment;

use Liquichain\WooCommerce\Gateway\LiquichainPaymentGateway;
use Liquichain\WooCommerce\PaymentMethods\InstructionStrategies\DefaultInstructionStrategy;

class OrderInstructionsService
{
    protected $strategy;
    public function setStrategy($gateway)
    {
        if (!$gateway->paymentMethod->getProperty('instructions')) {
            $this->strategy = new DefaultInstructionStrategy();
        } else {
            $className = 'Liquichain\\WooCommerce\\PaymentMethods\\InstructionStrategies\\' . ucfirst($gateway->paymentMethod->getProperty('id')) . 'InstructionStrategy';
            $this->strategy = class_exists($className) ? new $className() : new DefaultInstructionStrategy();
        }
    }

    public function executeStrategy(
        LiquichainPaymentGateway $gateway,
        $payment,
        $order = null,
        $admin_instructions = false
    ) {

        return $this->strategy->execute($gateway, $payment, $order, $admin_instructions);
    }
}
