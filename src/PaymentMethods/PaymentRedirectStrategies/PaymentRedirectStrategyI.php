<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods\PaymentRedirectStrategies;

use Liquichain\WooCommerce\Payment\LiquichainObject;
use Liquichain\WooCommerce\PaymentMethods\PaymentMethodI;
use WC_Order;

interface PaymentRedirectStrategyI
{

    public function execute(PaymentMethodI $paymentMethod, $order, $paymentObject, string $redirectUrl);
}
