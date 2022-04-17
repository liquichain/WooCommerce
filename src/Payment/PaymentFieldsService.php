<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\Payment;

use Liquichain\WooCommerce\Notice\NoticeInterface;
use Liquichain\WooCommerce\PaymentMethods\PaymentFieldsStrategies\DefaultFieldsStrategy;
use Liquichain\WooCommerce\PaymentMethods\PaymentFieldsStrategies\PaymentFieldsStrategyI;
use Liquichain\WooCommerce\Shared\Data;
use Psr\Log\LoggerInterface as Logger;

class PaymentFieldsService
{
    /**
     * @var PaymentFieldsStrategyI
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
        if (!$paymentMethod->getProperty('paymentFields')) {
            $this->strategy = new DefaultFieldsStrategy();
        } else {
            $className = 'Liquichain\\WooCommerce\\PaymentMethods\\PaymentFieldsStrategies\\' . ucfirst($paymentMethod->getProperty('id')) . 'FieldsStrategy';
            $this->strategy = class_exists($className) ? new $className() : new DefaultFieldsStrategy();
        }
    }

    public function executeStrategy($gateway)
    {
        return $this->strategy->execute($gateway, $this->dataHelper);
    }

    public function getStrategyMarkup($gateway)
    {
        return $this->strategy->getFieldMarkup($gateway, $this->dataHelper);
    }
}
