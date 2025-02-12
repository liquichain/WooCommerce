<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\Payment;

use Liquichain\WooCommerce\PaymentMethods\PaymentRedirectStrategies\DefaultRedirectStrategy;
use Liquichain\WooCommerce\PaymentMethods\PaymentRedirectStrategies\PaymentRedirectStrategyI;
use Liquichain\WooCommerce\Shared\Data;
use WC_Order;

class PaymentCheckoutRedirectService
{
    /**
     * @var PaymentRedirectStrategyI
     */
    protected $strategy;
    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * PaymentService constructor.
     */
    public function __construct($dataHelper)
    {
        $this->dataHelper = $dataHelper;
    }

    public function setStrategy($paymentMethod)
    {
        if (!$paymentMethod->getProperty('customRedirect')) {
            $this->strategy = new DefaultRedirectStrategy();
            return;
        }
        $className = 'Liquichain\\WooCommerce\\PaymentMethods\\PaymentRedirectStrategies\\' . ucfirst(
                $paymentMethod->getProperty('id')
            ) . 'RedirectStrategy';
        $this->strategy = class_exists($className) ? new $className() : new DefaultRedirectStrategy();
    }

    public function executeStrategy($paymentMethod, $order, $paymentObject, $redirectUrl)
    {
        return $this->strategy->execute($paymentMethod, $order, $paymentObject, $redirectUrl);
    }
}
