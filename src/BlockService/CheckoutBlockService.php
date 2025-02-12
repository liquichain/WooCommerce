<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\BlockService;

use InvalidArgumentException;
use Liquichain\WooCommerce\Gateway\Voucher\MaybeDisableGateway;
use Liquichain\WooCommerce\Shared\Data;

/**
 * Class CheckoutBlockService
 * @package Liquichain\WooCommerce\BlockService
 */
class CheckoutBlockService
{
    protected $dataService;
    /**
     * @var MaybeDisableGateway
     */
    protected $voucherDisabler;

    /**
     * CheckoutBlockService constructor.
     */
    public function __construct(Data $dataService, MaybeDisableGateway $voucherDisabler)
    {
        $this->dataService = $dataService;
        $this->voucherDisabler = $voucherDisabler;
    }

    /**
     * Adds all the Ajax actions to perform the whole workflow
     */
    public function bootstrapAjaxRequest()
    {
        $actionName = 'liquichain_checkout_blocks_canmakepayment';
        add_action(
            'wp_ajax_' . $actionName,
            function () {
                return $this->availableGateways();
            }
        );
        add_action(
            'wp_ajax_nopriv_' . $actionName,
            function () {
                return $this->availableGateways();
            }
        );
    }

    /**
     * When the country changes in the checkout block
     * We need to check again the list of available gateways accordingly
     * And return the result with a key based on the evaluated filters for the script to cache
     */
    public function availableGateways()
    {
        $currency = filter_input(INPUT_POST, 'currency', FILTER_SANITIZE_STRING);
        $cartTotal = filter_input(INPUT_POST,'cartTotal', FILTER_SANITIZE_NUMBER_INT);
        $paymentLocale = filter_input(INPUT_POST, 'paymentLocale', FILTER_SANITIZE_STRING);
        $billingCountry = filter_input(INPUT_POST, 'billingCountry', FILTER_SANITIZE_STRING);
        $cartTotal = $cartTotal / 100;
        $availablePaymentMethods = [];
        try {
            $filters = $this->dataService->getFilters(
                $currency,
                $cartTotal,
                $paymentLocale,
                $billingCountry
            );
        } catch (InvalidArgumentException $exception) {
            $filters = false;
        }
        if ($filters) {
            WC()->customer->set_billing_country($billingCountry);
            $availableGateways = WC()->payment_gateways()->get_available_payment_gateways();
            $availableGateways = $this->removeNonLiquichainGateway($availableGateways);
            $availableGateways = $this->maybeRemoveVoucher($availableGateways);
            $filterKey = "{$filters['amount']['currency']}-{$filters['locale']}-{$filters['billingCountry']}";
            foreach ($availableGateways as $key => $gateway){
                $availablePaymentMethods[$filterKey][$key] = $gateway->paymentMethod->getProperty('id');
            }
        }
        wp_send_json_success($availablePaymentMethods);
    }

    /**
     * Remove the voucher gateway from the available ones
     * if the products in the cart don't fit the requirements
     *
     * @param array $availableGateways
     * @return array
     */
    protected function maybeRemoveVoucher(array $availableGateways): array
    {
        foreach ($availableGateways as $key => $gateway) {
            if ($key !=='liquichain_wc_gateway_voucher') {
                continue;
            }
            $shouldRemoveVoucher = $this->voucherDisabler->shouldRemoveVoucher();
            if($shouldRemoveVoucher){
                unset($availableGateways[$key]);
            }
        }
        return $availableGateways;
    }

    /**
     * Remove the non Liquichain gateways from the available ones
     * so we don't deal with them in our block logic
     *
     * @param array $availableGateways
     * @return array
     */
    protected function removeNonLiquichainGateway(array $availableGateways): array
    {
        foreach ($availableGateways as $key => $gateway) {
            if (strpos($key, 'liquichain_wc_gateway_') === false) {
                unset($availableGateways[$key]);
            }
        }
        return $availableGateways;
    }
}
