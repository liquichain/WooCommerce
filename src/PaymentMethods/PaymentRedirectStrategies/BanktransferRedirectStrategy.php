<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods\PaymentRedirectStrategies;

use Liquichain\WooCommerce\Payment\LiquichainObject;
use Liquichain\WooCommerce\Payment\LiquichainOrder;
use Liquichain\WooCommerce\Payment\LiquichainPayment;
use Liquichain\WooCommerce\PaymentMethods\PaymentMethodI;
use WC_Order;

class BanktransferRedirectStrategy implements PaymentRedirectStrategyI
{
    /**
     * Redirect location after successfully completing process_payment
     *
     * @param PaymentMethodI $paymentMethod
     * @param WC_Order  $order
     * @param LiquichainOrder|LiquichainPayment $payment_object
     *
     * @return string
     */
    public function execute(PaymentMethodI $paymentMethod, $order, $paymentObject, string $redirectUrl): string
    {
        if ($paymentMethod->getProperty('skip_liquichain_payment_screen') === 'yes') {
            return add_query_arg(
                [
                    'utm_nooverride' => 1,
                ],
                $redirectUrl
            );
        }

        return $paymentObject->getCheckoutUrl();
    }
}
