<?php

# -*- coding: utf-8 -*-

declare(strict_types=1);

namespace Liquichain\WooCommerce\Log;

use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface as Logger;

class LogModule implements ServiceModule
{
    use ModuleClassNameIdTrait;

    private $loggerSource;

    /**
     * LogModule constructor.
     */
    public function __construct($loggerSource)
    {
        $this->loggerSource = $loggerSource;
    }

    public function services(): array
    {
        $source = $this->loggerSource;
        return [
            Logger::class => static function () use ($source): WcPsrLoggerAdapter {
                return new WcPsrLoggerAdapter(\wc_get_logger(), $source);
            },
        ];
    }
}
