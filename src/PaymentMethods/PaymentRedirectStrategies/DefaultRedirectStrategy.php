<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods\PaymentRedirectStrategies;

use Liquichain\WooCommerce\Payment\LiquichainOrder;
use Liquichain\WooCommerce\Payment\LiquichainPayment;
use Liquichain\WooCommerce\PaymentMethods\PaymentMethodI;
use WC_Order;

class DefaultRedirectStrategy implements PaymentRedirectStrategyI
{

    /**
     * Redirect location after successfully completing process_payment
     *
     * @param WC_Order  $order
     * @param LiquichainOrder|LiquichainPayment $payment_object
     */
    public function execute(PaymentMethodI $paymentMethod, $order, $paymentObject,string $redirectUrl): string
    {
        return $paymentObject->getCheckoutUrl();
    }
}
