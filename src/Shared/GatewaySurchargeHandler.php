<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\Shared;

use Liquichain\WooCommerce\Gateway\Surcharge;
use \WC_Order_Item_Fee;

class GatewaySurchargeHandler
{
    public $gatewayFeeLabel;
    protected $surcharge;

    /**
     * GatewaySurchargeHandler constructor.
     */
    public function __construct(Surcharge $surcharge)
    {
        $this->surcharge = $surcharge;
        $this->gatewayFeeLabel = $this->surchargeFeeOption();

        add_action(
                'init',
                [$this, 'surchargeActions']
        );

    }

    public function surchargeActions()
    {
        add_filter('woocommerce_cart_calculate_fees', function ($cart) {

            return $this->add_engraving_fees($cart);
        }, 10, 1);
        add_action('wp_enqueue_scripts', function () {
            return $this->enqueueSurchargeScript();
        });
        add_action(
                'wp_ajax_update_surcharge_order_pay',
                function () {
                    return $this->updateSurchargeOrderPay();
                }
        );
        add_action(
                'wp_ajax_nopriv_update_surcharge_order_pay',
                function () {
                    return $this->updateSurchargeOrderPay();
                }
        );
        add_action(
                'wp_ajax_liquichain_checkout_blocks_surchage',
                function () {
                    return $this->updateSurchargeCheckoutBlock();
                }
        );
        add_action(
                'wp_ajax_nopriv_liquichain_checkout_blocks_surchage',
                function () {
                    return $this->updateSurchargeCheckoutBlock();
                }
        );
        add_action( 'woocommerce_order_item_meta_end',[$this, 'setHiddenOrderId'], 10, 4);
    }

    public function setHiddenOrderId($item_id, $item, $order, $bool = false)
    {
        ?>
        <input type="hidden" name="liquichain-woocommerce-orderId" value="<?php echo $order->get_id() ?>">
        <?php
    }

    public function enqueueSurchargeScript()
    {
        if (is_admin() || !liquichainWooCommerceIsCheckoutContext()) {
            return;
        }
        wp_enqueue_script('gatewaySurcharge');
        wp_localize_script(
            'gatewaySurcharge',
            'surchargeData',
            [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'gatewayFeeLabel' => $this->gatewayFeeLabel,
            ]
        );
    }

    public function addSurchargeFeeProductPage($order, $gateway)
    {
        $gatewaySettings = $this->gatewaySettings($gateway);
        if (!isset($gatewaySettings['payment_surcharge']) || $gatewaySettings['payment_surcharge'] === Surcharge::NO_FEE) {
            return $order;
        }
        $order->calculate_totals();
        $orderAmount = $order->get_total();
        if ($this->surcharge->aboveMaxLimit($orderAmount, $gatewaySettings)) {
            return $order;
        }
        $amount = $this->surcharge->calculateFeeAmountOrder($order, $gatewaySettings);

        if ($amount > 0) {
            $surchargeName = $this->surcharge->buildFeeName($this->gatewayFeeLabel);
            $this->orderRemoveFee($order);
            $this->orderAddFee($order, $amount, $surchargeName);
            $order->calculate_totals();
        }
        return $order;
    }

    public function updateSurchargeOrderPay()
    {
        $order = $this->canProcessOrder();
        $gatewayName = $this->canProcessGateway();
        if(!$order || !$gatewayName){
            return;
        }
        $this->orderRemoveFee($order);
         $gatewaySettings = $this->gatewaySettings($gatewayName);
        $orderAmount = $order->get_total();
        if ($this->surcharge->aboveMaxLimit($orderAmount, $gatewaySettings)) {
            return;
        }
        if (!isset($gatewaySettings['payment_surcharge']) || $gatewaySettings['payment_surcharge'] === Surcharge::NO_FEE) {
            $data = [
                'amount' => false,
                'currency' => get_woocommerce_currency_symbol(),
                'newTotal' => $order->get_total(),
            ];
            wp_send_json_success($data);
        }

        $amount = $this->surcharge->calculateFeeAmountOrder($order, $gatewaySettings);
        $surchargeName = $this->surcharge->buildFeeName($this->gatewayFeeLabel);

        if ($amount > 0) {
            $this->orderAddFee($order, $amount, $surchargeName);
            $order->calculate_totals();
            $newTotal = $order->get_total();
            $data = [
                'amount' => $amount,
                'name' => $surchargeName,
                'currency' => get_woocommerce_currency_symbol(),
                'newTotal' => $newTotal,
            ];
            wp_send_json_success($data);
        }
    }

    public function updateSurchargeCheckoutBlock()
    {
        $gateway = $this->canProcessGateway();
        $cart = WC()->cart;
        $noSurchargeData = [
                'amount' => false,
                'name' => '',
                'currency' => get_woocommerce_currency_symbol(),
                'newTotal' => $cart->get_total(),
        ];
        $gatewaySettings = $this->gatewaySettings($gateway);
        $cart->calculate_totals();
        if (!$gatewaySettings){
            wp_send_json_success($noSurchargeData);
            return;
        }
        //This also removes previous fees

        if (
                !isset($gatewaySettings['payment_surcharge'])
                || $gatewaySettings['payment_surcharge'] === Surcharge::NO_FEE
        ) {
            wp_send_json_success($noSurchargeData);
            return;
        }

        $isRecurringCart = ! empty($cart->recurring_cart_key);
        if ($isRecurringCart) {
            wp_send_json_success($noSurchargeData);
            return;
        }
        $cartAmount = $cart->get_subtotal() + $cart->get_subtotal_tax();
        if ($this->surcharge->aboveMaxLimit($cartAmount, $gatewaySettings)) {
            wp_send_json_success($noSurchargeData);
            return;
        }
        $feeAmount = (float) $this->surcharge->calculateFeeAmount($cart, $gatewaySettings);
        $surchargeName = $this->surcharge->buildFeeName($this->gatewayFeeLabel);

        $newTotal = (float) $cart->get_total('edit') + $feeAmount;
        $data = [
                'amount' => $feeAmount,
                'name' => $surchargeName,
                'currency' => get_woocommerce_currency_symbol(),
                'newTotal' => $newTotal,
        ];
        wp_send_json_success($data);
    }

    public function add_engraving_fees($cart)
    {
        if (!liquichainWooCommerceIsCheckoutContext()
                && !has_block('woocommerce/checkout')

        ) {
            return;
        }

        $gateway = $this->chosenGateway();

        if (!$gateway) {
            return;
        }

        $gatewaySettings = $this->gatewaySettings($gateway);
        if (!$gatewaySettings){
            return;
        }
        if (
                !isset($gatewaySettings['payment_surcharge'])
                || $gatewaySettings['payment_surcharge'] === Surcharge::NO_FEE
        ) {
            return;
        }

        $isRecurringCart = ! empty($cart->recurring_cart_key);
        if ($isRecurringCart) {
            return;
        }
        $cartAmount = $cart->get_subtotal() + $cart->get_subtotal_tax();
        if ($this->surcharge->aboveMaxLimit($cartAmount, $gatewaySettings)) {
            return;
        }

        $amount = $this->surcharge->calculateFeeAmount($cart, $gatewaySettings);

        $surchargeName = $this->surcharge->buildFeeName($this->gatewayFeeLabel);

        $cart->add_fee($surchargeName, $amount);
    }

    protected function chosenGateway()
    {
        $gateway = WC()->session->chosen_payment_method;
        if ($gateway === '') {
            $gateway = (empty($_REQUEST['payment_method'])
                    ? '' : sanitize_text_field(
                        wp_unslash($_REQUEST['payment_method'])
                    ));
        }

        if (!$this->isLiquichainGateway($gateway)) {
            return false;
        }
        return $gateway;
    }

    protected function isLiquichainGateway($gateway)
    {
        return !empty($gateway) && strpos($gateway, 'liquichain_wc_gateway_') !== false;
    }

    private function gatewaySettings($gateway)
    {
        $optionName = sprintf('%s_settings', $gateway);
        $allSettings = get_option($optionName, false);
        if (!$allSettings) {
            return false;
        }

        return $allSettings;
    }

    /**
    * @var wc_order $order
     */
    protected function orderRemoveFee($order)
    {
        $fees = $order->get_fees();
        foreach ($fees as $fee) {
            $feeName = $fee->get_name();
            $feeId = $fee->get_id();
            if (strpos($feeName, $this->gatewayFeeLabel) !== false) {
                $order->remove_item($feeId);
                wc_delete_order_item($feeId);
                $order->calculate_totals();
            }
        }
    }

    protected function orderAddFee($order, $amount, $surchargeName)
    {
        $item_fee = new WC_Order_Item_Fee();
        $item_fee->set_name($surchargeName);
        $item_fee->set_amount($amount);
        $item_fee->set_total($amount);
        $item_fee->set_tax_status('none');

        $order->add_item($item_fee);
        $order->calculate_totals();
    }

    protected function canProcessOrder()
    {
        $orderId = isset($_POST['orderId']) ? filter_var($_POST['orderId'], FILTER_SANITIZE_NUMBER_INT) : false;
        if (!$orderId) {
            return false;
        }
        $order = wc_get_order($orderId);
        if (!$order) {
            return false;
        }
        return $order;
    }

    protected function canProcessGateway()
    {
        $gateway = isset($_POST['method']) ? filter_var($_POST['method'], FILTER_SANITIZE_STRING) : false;
        if (!$gateway) {
            return false;
        }
        if (!$this->isLiquichainGateway($gateway)) {
            return false;
        }
        return $gateway;
    }

    protected function surchargeFeeOption()
    {
        return get_option(
                'liquichain-payments-for-woocommerce_gatewayFeeLabel',
                __(Surcharge::DEFAULT_FEE_LABEL, 'liquichain-payments-for-woocommerce')
        );
    }
}

