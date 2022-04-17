<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods\PaymentFieldsStrategies;

interface PaymentFieldsStrategyI
{
    public function execute($gateway, $dataHelper);
}
