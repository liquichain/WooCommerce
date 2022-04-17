<?php

# -*- coding: utf-8 -*-

declare(strict_types=1);

namespace Liquichain\WooCommerce\Notice;

use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Liquichain\WooCommerce\Notice\AdminNotice;
use Psr\Container\ContainerInterface;
use Liquichain\WooCommerce\Notice\NoticeInterface as Notice;

class NoticeModule implements ServiceModule
{
    use ModuleClassNameIdTrait;

    public function services(): array
    {
        return [
            AdminNotice::class => static function (): AdminNotice {
                return new AdminNotice();
            },
        ];
    }
}
