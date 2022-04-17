<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\Payment;

use Liquichain\Api\Exceptions\ApiException;
use Liquichain\Api\Resources\Payment;
use Liquichain\WooCommerce\Gateway\LiquichainPaymentGateway;
use Liquichain\WooCommerce\Gateway\Surcharge;
use Liquichain\WooCommerce\Notice\NoticeInterface;
use Liquichain\WooCommerce\PaymentMethods\PaymentMethodI;
use Liquichain\WooCommerce\SDK\Api;
use Liquichain\WooCommerce\Settings\Settings;
use Liquichain\WooCommerce\Shared\Data;
use Psr\Log\LoggerInterface as Logger;
use Psr\Log\LogLevel;
use WC_Order;

class PaymentService
{
    public const PAYMENT_METHOD_TYPE_ORDER = 'order';
    public const PAYMENT_METHOD_TYPE_PAYMENT = 'payment';
    /**
     * @var LiquichainPaymentGateway
     */
    protected $gateway;
    /**
     * @var NoticeInterface
     */
    protected $notice;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var PaymentFactory
     */
    protected $paymentFactory;
    /**
     * @var Data
     */
    protected $dataHelper;
    protected $apiHelper;
    protected $settingsHelper;
    protected $pluginId;
    /**
     * @var PaymentCheckoutRedirectService
     */
    protected $paymentCheckoutRedirectService;


    /**
	 * PaymentService constructor.
	 */
    public function __construct(
        NoticeInterface $notice,
        Logger $logger,
        PaymentFactory $paymentFactory,
        Data $dataHelper,
        Api $apiHelper,
        Settings $settingsHelper,
        string $pluginId,
        PaymentCheckoutRedirectService $paymentCheckoutRedirectService
    )
    {
        $this->notice = $notice;
        $this->logger = $logger;
        $this->paymentFactory = $paymentFactory;
        $this->dataHelper = $dataHelper;
        $this->apiHelper = $apiHelper;
        $this->settingsHelper = $settingsHelper;
        $this->pluginId = $pluginId;
        $this->paymentCheckoutRedirectService = $paymentCheckoutRedirectService;
    }

    public function setGateway($gateway)
    {
        $this->gateway = $gateway;
    }

    public function processPayment($orderId, $order, $paymentMethod, $redirectUrl)
    {
        $this->logger->log(
            LogLevel::DEBUG,
            "{$paymentMethod->getProperty('id')}: Start process_payment for order {$orderId}",
            [true]
        );
        $initialOrderStatus = $this->processInitialOrderStatus($paymentMethod);


        $customerId = $this->getUserLiquichainCustomerId($order);

        $apiKey = $this->settingsHelper->getApiKey();

        $hasBlocksEnabled = $this->dataHelper->isBlockPluginActive();
        if($hasBlocksEnabled){
            $order = $this->correctSurchargeFee($order, $paymentMethod);
        }

        if ($this->needsSubscriptionSwitch($order, $orderId)) {
            return $this->processSubscriptionSwitch($order, $orderId, $customerId, $apiKey);
        }

        $liquichainPaymentType = $this->paymentTypeBasedOnGateway($paymentMethod);
        $liquichainPaymentType = $this->paymentTypeBasedOnProducts($order, $liquichainPaymentType);
        try {
            $paymentObject = $this->paymentFactory->getPaymentObject($liquichainPaymentType);
        } catch (ApiException $exception) {
            return $this->paymentObjectFailure($exception);
        }
        try {
            $paymentObject = $this->processPaymentForLiquichain(
                $liquichainPaymentType,
                $orderId,
                $paymentObject,
                $order,
                $customerId,
                $apiKey
            );

            $this->saveLiquichainInfo($order, $paymentObject);
            $this->saveSubscriptionMandateData($orderId, $apiKey, $customerId, $paymentObject, $order);
            do_action($this->pluginId . '_payment_created', $paymentObject, $order);
            $this->updatePaymentStatusForDelayedMethods($paymentObject, $order, $initialOrderStatus);
            $this->reportPaymentSuccess($paymentObject, $orderId, $order, $paymentMethod);
            return [
                'result' => 'success',
                'redirect' => $this->getProcessPaymentRedirect(
                    $paymentMethod,
                    $order,
                    $paymentObject,
                    $redirectUrl
                ),
            ];
        } catch (ApiException $error) {
            $this->reportPaymentCreationFailure($orderId, $error);
        }
        return ['result' => 'failure'];
    }


    /**
     * @param WC_Order $order
     * @param PaymentMethodI $paymentMethod
     */
    protected function correctSurchargeFee($order, $paymentMethod)
    {
        $fees = $order->get_fees();
        $surcharge = $paymentMethod->surcharge();
        $gatewaySettings = $paymentMethod->getMergedProperties();
        $totalAmount = $order->get_total();
        $aboveMaxLimit = $surcharge->aboveMaxLimit($totalAmount, $gatewaySettings);
        $amount = $aboveMaxLimit? 0 : $surcharge->calculateFeeAmountOrder($order, $gatewaySettings);
        $gatewayHasSurcharge = $amount !== 0;
        $gatewayFeeLabel = get_option(
            'liquichain-payments-for-woocommerce_gatewayFeeLabel',
            __(Surcharge::DEFAULT_FEE_LABEL, 'liquichain-payments-for-woocommerce')
        );
        $surchargeName = $surcharge->buildFeeName($gatewayFeeLabel);

        $correctedFee = false;
        foreach ($fees as $fee) {
            $feeName = $fee->get_name();
            $feeId = $fee->get_id();
            $hasLiquichainFee = strpos($feeName, $gatewayFeeLabel) !== false;
            if ($hasLiquichainFee) {
                if($amount == (float) $fee->get_amount('edit')){
                    $correctedFee = true;
                    continue;
                }
                if(!$gatewayHasSurcharge){
                    $this->removeOrderFee($order, $feeId);
                    $correctedFee = true;
                    continue;
                }
                $this->removeOrderFee($order, $feeId);
                $this->orderAddFee($order, $amount, $surchargeName);
                $correctedFee = true;
            }
        }
        if (!$correctedFee) {
            if($gatewayHasSurcharge){
                $this->orderAddFee($order, $amount, $surchargeName);
            }
        }
        return $order;
    }

    /**
     * @param WC_Order $order
     * @param int $feeId
     * @throws \Exception
     */
    protected function removeOrderFee(\WC_Order $order, int $feeId): \WC_Order
    {
        $order->remove_item($feeId);
        wc_delete_order_item($feeId);
        $order->calculate_totals();
        return $order;
    }


    protected function orderAddFee($order, $amount, $surchargeName)
    {
        $item_fee = new \WC_Order_Item_Fee();
        $item_fee->set_name($surchargeName);
        $item_fee->set_amount($amount);
        $item_fee->set_total($amount);
        $item_fee->set_tax_status('none');

        $order->add_item($item_fee);
        $order->calculate_totals();
    }

    /**
     * Redirect location after successfully completing process_payment
     *
     * @param WC_Order                  $order
     * @param LiquichainOrder|LiquichainPayment $paymentObject
     *
     * @return string
     */
    public function getProcessPaymentRedirect(
        PaymentMethodI $paymentMethod,
        $order,
        $paymentObject,
        string $redirectUrl
    ): string {
        $this->paymentCheckoutRedirectService->setStrategy($paymentMethod);
        return $this->paymentCheckoutRedirectService->executeStrategy(
            $paymentMethod,
            $order,
            $paymentObject,
            $redirectUrl
        );
    }
    /**
     * @param $order
     * @param $test_mode
     * @return null|string
     */
    protected function getUserLiquichainCustomerId($order)
    {
        $order_customer_id = $order->get_customer_id();
        $apiKey = $this->settingsHelper->getApiKey();

        return  $this->dataHelper->getUserLiquichainCustomerId($order_customer_id, $apiKey);
    }

    protected function paymentTypeBasedOnGateway($paymentMethod)
    {
        $optionName = $this->pluginId . '_' .'api_switch';
        $apiSwitchOption = get_option($optionName);
        $paymentType = $apiSwitchOption?: self::PAYMENT_METHOD_TYPE_ORDER;
        $isBankTransferGateway = $paymentMethod->getProperty('id') === 'banktransfer';
        if($isBankTransferGateway && $paymentMethod->isExpiredDateSettingActivated()){
            $paymentType = self::PAYMENT_METHOD_TYPE_PAYMENT;
        }

        return $paymentType;
    }
    /**
     * CHECK WOOCOMMERCE PRODUCTS
     * Make sure all cart items are real WooCommerce products,
     * not removed products or virtual ones (by WooCommerce Events Manager etc).
     * If products are virtual, use Payments API instead of Orders API
     *
     * @param \WC_Order $order
     *
     * @param  string  $liquichainPaymentType
     *
     * @return string
     */
    protected function paymentTypeBasedOnProducts($order, $liquichainPaymentType)
    {
        foreach ($order->get_items() as $cart_item) {
            if ($cart_item['quantity']) {
                do_action(
                    $this->pluginId
                    . '_orderlines_process_items_before_getting_product_id',
                    $cart_item
                );

                if ($cart_item['variation_id']) {
                    $product = wc_get_product($cart_item['variation_id']);
                } else {
                    $product = wc_get_product($cart_item['product_id']);
                }

                if ($product === false) {
                    $liquichainPaymentType = self::PAYMENT_METHOD_TYPE_PAYMENT;
                    do_action(
                        $this->pluginId
                        . '_orderlines_process_items_after_processing_item',
                        $cart_item
                    );
                    break;
                }
                do_action(
                    $this->pluginId
                    . '_orderlines_process_items_after_processing_item',
                    $cart_item
                );
            }
        }
        return $liquichainPaymentType;
    }
    /**
     * @param LiquichainOrder $paymentObject
     * @param \WC_Order                $order
     * @param                         $customer_id
     * @param                         $test_mode
     *
     * @return array
     * @throws ApiException
     */
    protected function processAsLiquichainOrder(
        LiquichainOrder $paymentObject,
        $order,
        $customer_id,
        $apiKey
    ) {
        $liquichainPaymentType = self::PAYMENT_METHOD_TYPE_ORDER;
        $paymentRequestData = $paymentObject->getPaymentRequestData(
            $order,
            $customer_id
        );

        $data = array_filter($paymentRequestData);

        $data = apply_filters(
            'woocommerce_' . $this->gateway->id . '_args',
            $data,
            $order
        );

        do_action(
            $this->pluginId . '_create_payment',
            $data,
            $order
        );

        // Create Liquichain payment with customer id.
        try {
            $this->logger->log( LogLevel::DEBUG,
                'Creating payment object: type Order, first try creating a Liquichain Order.'
            );

            // Only enable this for hardcore debugging!
            $apiCallLog = [
                'amount' => isset($data['amount']) ? $data['amount'] : '',
                'redirectUrl' => isset($data['redirectUrl'])
                    ? $data['redirectUrl'] : '',
                'webhookUrl' => isset($data['webhookUrl'])
                    ? $data['webhookUrl'] : '',
                'method' => isset($data['method']) ? $data['method'] : '',
                'payment' => isset($data['payment']) ? $data['payment']
                    : '',
                'locale' => isset($data['locale']) ? $data['locale'] : '',
                'metadata' => isset($data['metadata']) ? $data['metadata']
                    : '',
                'orderNumber' => isset($data['orderNumber'])
                    ? $data['orderNumber'] : ''
            ];

            $this->logger->log( LogLevel::DEBUG, json_encode($apiCallLog));
            $paymentOrder = $paymentObject;
            $paymentObject = $this->apiHelper->getApiClient($apiKey)->orders->create($data);
            $this->logger->log( LogLevel::DEBUG, json_encode($paymentObject));
            $settingsHelper = $this->settingsHelper;
            if($settingsHelper->getOrderStatusCancelledPayments() === 'cancelled'){
                $orderId = $order->get_id();
                $orderWithPayments = $this->apiHelper->getApiClient($apiKey)->orders->get( $paymentObject->id, [ "embed" => "payments" ] );
                $paymentOrder->updatePaymentDataWithOrderData($orderWithPayments, $orderId);
            }
        } catch (ApiException $e) {
            // Don't try to create a Liquichain Payment for Klarna payment methods
            $order_payment_method = $order->get_payment_method();

            if ($order_payment_method === 'liquichain_wc_gateway_klarnapaylater'
                || $order_payment_method === 'liquichain_wc_gateway_sliceit'
                || $order_payment_method === 'liquichain_wc_gateway_klarnapaynow'
            ) {
                $this->logger->log( LogLevel::DEBUG,
                    'Creating payment object: type Order, failed for Klarna payment, stopping process.'
                );
                throw $e;
            }

            $this->logger->log( LogLevel::DEBUG,
                'Creating payment object: type Order, first try failed: '
                . $e->getMessage()
            );

            // Unset missing customer ID
            unset($data['payment']['customerId']);

            try {
                if ($e->getField() !== 'payment.customerId') {
                    $this->logger->log( LogLevel::DEBUG,
                        'Creating payment object: type Order, did not fail because of incorrect customerId, so trying Payment now.'
                    );
                    throw $e;
                }

                // Retry without customer id.
                $this->logger->log( LogLevel::DEBUG,
                    'Creating payment object: type Order, second try, creating a Liquichain Order without a customerId.'
                );
                $paymentObject = $this->apiHelper->getApiClient(
                    $apiKey
                )->orders->create($data);
            } catch (ApiException $e) {
                // Set Liquichain payment type to payment, when creating a Liquichain Order has failed
                $liquichainPaymentType = self::PAYMENT_METHOD_TYPE_PAYMENT;
            }
        }
        return array(
            $paymentObject,
            $liquichainPaymentType
        );
    }

    /**
     * @param \WC_Order                $order
     * @param                         $customer_id
     * @param                         $test_mode
     *
     * @return Payment $paymentObject
     * @throws ApiException
     */
    protected function processAsLiquichainPayment(
        \WC_Order $order,
        $customer_id,
        $apiKey
    ) {
        $paymentObject = $this->paymentFactory->getPaymentObject(
            self::PAYMENT_METHOD_TYPE_PAYMENT
        );
        $paymentRequestData = $paymentObject->getPaymentRequestData(
            $order,
            $customer_id
        );

        $data = array_filter($paymentRequestData);

        $data = apply_filters(
            'woocommerce_' . $this->gateway->id . '_args',
            $data,
            $order
        );

        try {
            // Only enable this for hardcore debugging!
            $apiCallLog = [
                'amount' => isset($data['amount']) ? $data['amount'] : '',
                'description' => isset($data['description'])
                    ? $data['description'] : '',
                'redirectUrl' => isset($data['redirectUrl'])
                    ? $data['redirectUrl'] : '',
                'webhookUrl' => isset($data['webhookUrl'])
                    ? $data['webhookUrl'] : '',
                'method' => isset($data['method']) ? $data['method'] : '',
                'issuer' => isset($data['issuer']) ? $data['issuer'] : '',
                'locale' => isset($data['locale']) ? $data['locale'] : '',
                'dueDate' => isset($data['dueDate']) ? $data['dueDate'] : '',
                'metadata' => isset($data['metadata']) ? $data['metadata']
                    : ''
            ];

            $this->logger->log( LogLevel::DEBUG, $apiCallLog);

            // Try as simple payment
            $paymentObject = $this->apiHelper->getApiClient(
                $apiKey
            )->payments->create($data);
        } catch (ApiException $e) {
            $message = $e->getMessage();
            $this->logger->log( LogLevel::DEBUG, $message);
            throw $e;
        }
        return $paymentObject;
    }

    /**
     * @param                         $liquichainPaymentType
     * @param                         $orderId
     * @param LiquichainOrder|LiquichainPayment $paymentObject
     * @param \WC_Order                $order
     * @param                         $customer_id
     * @param                         $test_mode
     *
     * @return mixed|Payment|LiquichainOrder
     * @throws ApiException
     */
    protected function processPaymentForLiquichain(
        $liquichainPaymentType,
        $orderId,
        $paymentObject,
        $order,
        $customer_id,
        $apiKey
    ) {
        //
        // PROCESS REGULAR PAYMENT AS LIQUICHAIN ORDER
        //
        if ($liquichainPaymentType === self::PAYMENT_METHOD_TYPE_ORDER) {
            $this->logger->log( LogLevel::DEBUG,
                "{$this->gateway->id}: Create Liquichain payment object for order {$orderId}",
                [true]
            );

            list(
                $paymentObject,
                $liquichainPaymentType
                )
                = $this->processAsLiquichainOrder(
                $paymentObject,
                $order,
                $customer_id,
                $apiKey
            );
        }

        //
        // PROCESS REGULAR PAYMENT AS LIQUICHAIN PAYMENT
        //

        if ($liquichainPaymentType === self::PAYMENT_METHOD_TYPE_PAYMENT) {
            $this->logger->log( LogLevel::DEBUG,
                'Creating payment object: type Payment, creating a Payment.'
            );

            $paymentObject = $this->processAsLiquichainPayment(
                $order,
                $customer_id,
                $apiKey
            );
        }
        return $paymentObject;
    }

    /**
     * @param $order
     * @param $payment
     */
    protected function saveLiquichainInfo( $order, $payment ) {
        // Get correct Liquichain Payment Object
        $payment_object = $this->paymentFactory->getPaymentObject( $payment );

        // Set active Liquichain payment
        $payment_object->setActiveLiquichainPayment( $order->get_id() );

        // Get Liquichain Customer ID
        $liquichain_customer_id = $payment_object->getLiquichainCustomerIdFromPaymentObject( $payment_object->data->id );

        // Set Liquichain customer
        $this->dataHelper->setUserLiquichainCustomerId( $order->get_customer_id(), $liquichain_customer_id );
    }

    /**
     * @param \WC_Order $order
     * @param string $new_status
     * @param string $note
     * @param bool $restore_stock
     */
    public function updateOrderStatus (\WC_Order $order, $new_status, $note = '', $restore_stock = true )
    {
        $order->update_status($new_status, $note);

        switch ($new_status)
        {
            case LiquichainPaymentGateway::STATUS_ON_HOLD:

                if ( $restore_stock === true ) {
                    if ( ! $order->get_meta( '_order_stock_reduced', true ) ) {
                        // Reduce order stock
                        wc_reduce_stock_levels( $order->get_id() );

                        $this->logger->log( LogLevel::DEBUG,  __METHOD__ . ":  Stock for order {$order->get_id()} reduced." );
                    }
                }

                break;

            case LiquichainPaymentGateway::STATUS_PENDING:
            case LiquichainPaymentGateway::STATUS_FAILED:
            case LiquichainPaymentGateway::STATUS_CANCELLED:
                if ( $order->get_meta( '_order_stock_reduced', true ) )
                {
                    // Restore order stock
                    $this->dataHelper->restoreOrderStock($order);

                    $this->logger->log( LogLevel::DEBUG, __METHOD__ . " Stock for order {$order->get_id()} restored.");
                }

                break;
        }
    }



    /**
     * @param $orderId
     */
    protected function noValidMandateForSubsSwitchFailure($orderId): void
    {
        $this->logger->log(
            LogLevel::DEBUG,
            $this->gateway->id . ': Subscription switch failed, no valid mandate for order #' . $orderId
        );
        $this->notice->addNotice(
            'error',
            __(
                'Subscription switch failed, no valid mandate found. Place a completely new order to change your subscription.',
                'liquichain-payments-for-woocommerce'
            )
        );
        throw new ApiException(
            __('Failed switching subscriptions, no valid mandate.', 'liquichain-payments-for-woocommerce')
        );
    }

    protected function subsSwitchCompleted($order):array
    {
        $order->payment_complete();

        $order->add_order_note( sprintf(
                                    __( 'Order completed internally because of an existing valid mandate at Liquichain.', 'liquichain-payments-for-woocommerce' ) ) );

        $this->logger->log( LogLevel::DEBUG,  $this->gateway->id . ': Subscription switch completed, valid mandate for order #' . $orderId );

        return array (
            'result'   => 'success',
            'redirect' => $this->gateway->get_return_url( $order ),
        );
    }

    /**
     * @param $order
     * @param string|null $customerId
     * @param $apiKey
     * @return bool
     * @throws ApiException
     */
    protected function processValidMandate($order, ?string $customerId, $apiKey): bool
    {
        $paymentObject = $this->paymentFactory->getPaymentObject(
            self::PAYMENT_METHOD_TYPE_PAYMENT
        );
        $paymentRequestData = $paymentObject->getPaymentRequestData($order, $customerId);
        $data = array_filter($paymentRequestData);
        $data = apply_filters('woocommerce_' . $this->gateway->id . '_args', $data, $order);

        $mandates = $this->apiHelper->getApiClient($apiKey)->customers->get($customerId)->mandates();
        $validMandate = false;
        foreach ($mandates as $mandate) {
            if ($mandate->status === 'valid') {
                $validMandate = true;
                $data['method'] = $mandate->method;
                break;
            }
        }
        return $validMandate;
    }

    protected function processSubscriptionSwitch(WC_Order $order, int $orderId, ?string $customerId, ?string $apiKey)
    {
        //
        // PROCESS SUBSCRIPTION SWITCH - If this is a subscription switch and customer has a valid mandate, process the order internally
        //
        try {
            $this->logger->log(LogLevel::DEBUG,  $this->gateway->id . ': Subscription switch started, fetching mandate(s) for order #' . $orderId);
            $validMandate = $this->processValidMandate($order, $customerId, $apiKey);
            if ( $validMandate ) {
                return $this->subsSwitchCompleted($order);
            } else {
                $this->noValidMandateForSubsSwitchFailure($orderId);
            }
        }
        catch ( ApiException $e ) {
            if ( $e->getField() ) {
                throw $e;
            }
        }

        return array ( 'result' => 'failure' );
    }

    /**
     * @param $orderId
     * @param $e
     */
    protected function reportPaymentCreationFailure($orderId, $e): void
    {
        $this->logger->log(LogLevel::DEBUG,
                           $this->id . ': Failed to create Liquichain payment object for order ' . $orderId . ': ' . $e->getMessage(
                           )
        );

        /* translators: Placeholder 1: Payment method title */
        $message = sprintf(__('Could not create %s payment.', 'liquichain-payments-for-woocommerce'), $this->title);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $message .= 'hii ' . $e->getMessage();
        }

        $this->notice->addNotice('error', $message);
    }

    /**
     * @param $orderId
     * @param $apiKey
     * @param string|null $customerId
     * @param $paymentObject
     * @param $order
     * @throws ApiException
     */
    protected function saveSubscriptionMandateData(
        $orderId,
        $apiKey,
        ?string $customerId,
        $paymentObject,
        $order
    ): void {
        $dataHelper = $this->dataHelper;
        if ($dataHelper->isSubscription($orderId)) {
            $mandates = $this->apiHelper->getApiClient($apiKey)->customers->get($customerId)->mandates();
            $mandate = $mandates[0];
            $customerId = $mandate->customerId;
            $mandateId = $mandate->id;
            $this->logger->log(
                LogLevel::DEBUG,
                "Liquichain Subscription in the order: customer id {$customerId} and mandate id {$mandateId} "
            );
            do_action($this->pluginId . '_after_mandate_created', $paymentObject, $order, $customerId, $mandateId);
        }
    }

    /**
     * @param $paymentObject
     * @param $order
     * @param $initialOrderStatus
     */
    protected function updatePaymentStatusForDelayedMethods($paymentObject, $order, $initialOrderStatus): void
    {
// Update initial order status for payment methods where the payment status will be delivered after a couple of days.
        // See: https://www.liquichain.io/nl/docs/status#expiry-times-per-payment-method
        // Status is only updated if the new status is not the same as the default order status (pending)
        if (($paymentObject->method === 'banktransfer') || ($paymentObject->method === 'directdebit')) {
            // Don't change the status of the order if it's Partially Paid
            // This adds support for WooCommerce Deposits (by Webtomizer)
            // See https://github.com/liquichain/WooCommerce/issues/138

            $order_status = $order->get_status();

            if ($order_status != 'wc-partially-paid ') {
                $this->updateOrderStatus(
                    $order,
                    $initialOrderStatus,
                    __('Awaiting payment confirmation.', 'liquichain-payments-for-woocommerce') . "\n"
                );
            }
        }
    }

    /**
     * @param $paymentObject
     * @param $orderId
     * @param $order
     */
    protected function reportPaymentSuccess($paymentObject, $orderId, $order, $paymentMethod): void
    {
        $paymentMethodTitle = $paymentMethod->getProperty('id');
        $this->logger->log(
            LogLevel::DEBUG,
            $paymentMethodTitle . ': Liquichain payment object ' . $paymentObject->id . ' (' . $paymentObject->mode . ') created for order ' . $orderId
        );
        $order->add_order_note(
            sprintf(
            /* translators: Placeholder 1: Payment method title, placeholder 2: payment ID */
                __('%s payment started (%s).', 'liquichain-payments-for-woocommerce'),
                $paymentMethodTitle,
                $paymentObject->id . ($paymentObject->mode === 'test' ? (' - ' . __(
                        'test mode',
                        'liquichain-payments-for-woocommerce'
                    )) : '')
            )
        );

        $this->logger->log(
            LogLevel::DEBUG,
            "For order " . $orderId . " redirect user to Liquichain Checkout URL: " . $paymentObject->getCheckoutUrl()
        );
    }

    /**
     * @param $order
     * @param $orderId
     * @return bool
     */
    protected function needsSubscriptionSwitch($order, $orderId): bool
    {
        return ('0.00' === $order->get_total())
            && ($this->dataHelper->isWcSubscription($orderId) === true)
            && 0 !== $order->get_user_id()
            && (wcs_order_contains_switch($order));
    }

    /**
     * @param $exception
     * @return string[]
     */
    protected function paymentObjectFailure($exception): array
    {
        $this->logger->log(LogLevel::DEBUG, $exception->getMessage());
        return array('result' => 'failure');
    }

    /**
     * @return mixed|void|null
     */
    protected function processInitialOrderStatus($paymentMethod)
    {
        $initialOrderStatus = $paymentMethod->getInitialOrderStatus();
        // Overwrite plugin-wide
        $initialOrderStatus = apply_filters(
            $this->pluginId . '_initial_order_status', $initialOrderStatus
        );
        // Overwrite gateway-wide
        $initialOrderStatus = apply_filters(
            $this->pluginId . '_initial_order_status_' . $paymentMethod->getProperty('id'),
            $initialOrderStatus
        );
        return $initialOrderStatus;
    }
}
