<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods;

interface PaymentMethodI
{
    public function getProperty(string $propertyName);
    public function hasProperty(string $propertyName);
}
