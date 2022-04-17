<?php

# -*- coding: utf-8 -*-

declare(strict_types=1);

namespace Liquichain\WooCommerce\SDK;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Liquichain\Api\Resources\Refund;
use Liquichain\WooCommerce\Gateway\AbstractGateway;
use Liquichain\WooCommerce\Notice\AdminNotice;
use Liquichain\WooCommerce\Plugin;
use Liquichain\WooCommerce\SDK\HttpResponse;
use Psr\Container\ContainerInterface;

class SDKModule implements ExecutableModule, ServiceModule
{
    use ModuleClassNameIdTrait;

    public function services(): array
    {
        return [
            'SDK.api_helper' => static function (ContainerInterface $container): Api {
                $pluginVersion = $container->get('shared.plugin_version');
                $pluginId = $container->get('shared.plugin_id');
                return new Api($pluginVersion, $pluginId);
            },
            'SDK.HttpResponse' => static function (): HttpResponse {
                return new HttpResponse();
            },
        ];
    }

    public function run(ContainerInterface $container): bool
    {
        return true;
    }
}
