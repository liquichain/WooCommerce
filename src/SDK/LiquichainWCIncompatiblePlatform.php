<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\SDK;

use Liquichain\WooCommerce\Shared\LiquichainException;

class LiquichainWCIncompatiblePlatform extends LiquichainException
{
    /**
     * @var int
     */
    public const API_CLIENT_NOT_INSTALLED = 1000;
    /**
     * @var int
     */
    public const API_CLIENT_NOT_COMPATIBLE = 2000;
}
