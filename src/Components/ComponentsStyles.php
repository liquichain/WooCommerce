<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\Components;

use Liquichain\WooCommerce\Settings\SettingsComponents;
use WC_Payment_Gateway;
use WC_Payment_Gateways;

class ComponentsStyles
{
    /**
     * @var SettingsComponents
     */
    protected $liquichainComponentsSettings;

    /**
     * @var WC_Payment_Gateways
     */
    protected $paymentGateways;

    /**
     * ComponentsStyles constructor.
     * @param WC_Payment_Gateways $paymentGateways
     */
    public function __construct(
        SettingsComponents $liquichainComponentsSettings,
        WC_Payment_Gateways $paymentGateways
    ) {

        $this->liquichainComponentsSettings = $liquichainComponentsSettings;
        $this->paymentGateways = $paymentGateways;
    }

    /**
     * Retrieve the liquichain components styles for all of the available Gateways
     *
     * Gateways are enabled along with liquichain components
     *
     * @return array
     */
    public function forAvailableGateways()
    {
        $availablePaymentGateways = $this->paymentGateways->get_available_payment_gateways();
        $gatewaysWithLiquichainComponentsEnabled = $this->gatewaysWithLiquichainComponentsEnabled(
            $availablePaymentGateways
        );

        if ($gatewaysWithLiquichainComponentsEnabled === []) {
            return [];
        }

        return $this->liquichainComponentsStylesPerGateway(
            $this->liquichainComponentsSettings->styles(),
            $gatewaysWithLiquichainComponentsEnabled
        );
    }

    /**
     * Retrieve the WooCommerce Gateways Which have the Liquichain Components enabled
     *
     * @return array
     */
    protected function gatewaysWithLiquichainComponentsEnabled(array $gateways)
    {
        $gatewaysWithLiquichainComponentsEnabled = [];

        /** @var WC_Payment_Gateway $gateway */
        foreach ($gateways as $gateway) {
            $isGatewayEnabled = liquichainWooCommerceStringToBoolOption($gateway->enabled);
            if ($isGatewayEnabled && $this->isLiquichainComponentsEnabledForGateway($gateway)) {
                $gatewaysWithLiquichainComponentsEnabled[] = $gateway;
            }
        }

        return $gatewaysWithLiquichainComponentsEnabled;
    }

    /**
     * Check if Liquichain Components are enabled for the given gateway
     *
     * @param WC_Payment_Gateway $gateway
     * @return bool
     */
    protected function isLiquichainComponentsEnabledForGateway(WC_Payment_Gateway $gateway)
    {
        if (!isset($gateway->settings['liquichain_components_enabled'])) {
            return false;
        }

        return liquichainWooCommerceStringToBoolOption($gateway->settings['liquichain_components_enabled']);
    }

    /**
     * Retrieve the liquichain components styles associated to the given gateways
     *
     * @return array
     */
    protected function liquichainComponentsStylesPerGateway(
        array $liquichainComponentStyles,
        array $gateways
    ) {

        $gatewayNames = $this->gatewayNames($gateways);
        $liquichainComponentsStylesGateways = array_combine(
            $gatewayNames,
            array_fill(
                0,
                count($gatewayNames),
                [
                    'styles' => $liquichainComponentStyles,
                ]
            )
        );

        return $liquichainComponentsStylesGateways ?: [];
    }

    /**
     * Extract the name of the gateways from the given gateways instances
     *
     * @return array
     */
    protected function gatewayNames(array $gateways)
    {
        $gatewayNames = [];

        /** @var WC_Payment_Gateway $gateway */
        foreach ($gateways as $gateway) {
            $gatewayNames[] = str_replace('liquichain_wc_gateway_', '', $gateway->id);
        }

        return $gatewayNames;
    }
}
