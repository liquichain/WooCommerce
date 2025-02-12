<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\Payment;

use Liquichain\Api\Exceptions\ApiException;
use Liquichain\Api\Resources\Order;
use Liquichain\Api\Resources\Payment;
use Liquichain\WooCommerce\Gateway\LiquichainPaymentGateway;
use Liquichain\WooCommerce\SDK\Api;
use Liquichain\WooCommerce\Settings\Settings;
use Psr\Log\LogLevel;
use WC_Order;
use WC_Payment_Gateway;
use Psr\Log\LoggerInterface as Logger;

class LiquichainObject
{

    public $data;
    /**
     * @var string[]
     */
    const FINAL_STATUSES = ['completed', 'refunded', 'canceled'];

    public static $paymentId;
    public static $customerId;
    public static $order;
    public static $payment;
    public static $shop_country;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var PaymentFactory
     */
    protected $paymentFactory;
    protected $dataService;
    protected $apiHelper;
    protected $settingsHelper;
    protected $dataHelper;

    public function __construct($data, Logger $logger, PaymentFactory $paymentFactory, Api $apiHelper, Settings $settingsHelper, string $pluginId)
    {
        $this->data = $data;
        $this->logger = $logger;
        $this->paymentFactory = $paymentFactory;
        $this->apiHelper = $apiHelper;
        $this->settingsHelper = $settingsHelper;
        $this->pluginId = $pluginId;
        $base_location = wc_get_base_location();
        static::$shop_country = $base_location['country'];
    }

    /**
     * Get Liquichain payment from cache or load from Liquichain
     * Skip cache by setting $use_cache to false
     *
     * @param string $paymentId
     * @param bool   $testMode (default: false)
     * @param bool   $useCache (default: true)
     *
     * @return Payment|Order|null
     */
    public function getPaymentObject($paymentId, $testMode = false, $useCache = true)
    {
        return static::$payment;
    }

    /**
     * Get Liquichain payment from cache or load from Liquichain
     * Skip cache by setting $use_cache to false
     *
     * @param string $payment_id
     * @param bool   $test_mode (default: false)
     * @param bool   $use_cache (default: true)
     *
     * @return Payment|null
     */
    public function getPaymentObjectPayment($payment_id, $test_mode = false, $use_cache = true)
    {
        try {
            $test_mode = $this->settingsHelper->isTestModeEnabled();
            $apiKey = $this->settingsHelper->getApiKey();
            return $this->apiHelper->getApiClient($apiKey)->payments->get($payment_id);
        } catch (ApiException $apiException) {
            $this->logger->log(LogLevel::DEBUG, __FUNCTION__ . sprintf(': Could not load payment %s (', $payment_id) . ( $test_mode ? 'test' : 'live' ) . "): " . $apiException->getMessage() . ' (' . get_class($apiException) . ')');
        }

        return null;
    }

    /**
     * Get Liquichain payment from cache or load from Liquichain
     * Skip cache by setting $use_cache to false
     *
     * @param string $payment_id
     * @param bool   $test_mode (default: false)
     * @param bool   $use_cache (default: true)
     *
     * @return Payment|Order|null
     */
    public function getPaymentObjectOrder($payment_id, $test_mode = false, $use_cache = true)
    {
        // TODO David: Duplicate, send to child class.
        try {
            // Is test mode enabled?
            $test_mode = $this->settingsHelper->isTestModeEnabled();
            $apiKey = $this->settingsHelper->getApiKey();
            return $this->apiHelper->getApiClient($apiKey)->orders->get($payment_id, [ "embed" => "payments" ]);
        } catch (ApiException $e) {
            $this->logger->log(LogLevel::DEBUG, __FUNCTION__ . sprintf(': Could not load order %s (', $payment_id) . ( $test_mode ? 'test' : 'live' ) . "): " . $e->getMessage() . ' (' . get_class($e) . ')');
        }

        return null;
    }

    /**
     * @param $order
     * @param $customerId
     *
     */
    protected function getPaymentRequestData($order, $customerId)
    {
    }

    /**
     * Save active Liquichain payment id for order
     *
     * @param int $orderId
     *
     * @return $this
     */
    public function setActiveLiquichainPayment($orderId)
    {
        if ($this->dataHelper->isSubscription($orderId)) {
            return $this->setActiveLiquichainPaymentForSubscriptions($orderId);
        }

        return $this->setActiveLiquichainPaymentForOrders($orderId);
    }

    /**
     * Save active Liquichain payment id for order
     *
     * @param int $order_id
     *
     * @return $this
     */
    public function setActiveLiquichainPaymentForOrders($order_id)
    {
        static::$order = wc_get_order($order_id);

        static::$order->update_meta_data('_liquichain_order_id', $this->data->id);
        static::$order->update_meta_data('_liquichain_payment_id', static::$paymentId);
        static::$order->update_meta_data('_liquichain_payment_mode', $this->data->mode);

        static::$order->delete_meta_data('_liquichain_cancelled_payment_id');

        if (static::$customerId) {
            static::$order->update_meta_data('_liquichain_customer_id', static::$customerId);
        }

        static::$order->save();

        return $this;
    }

    /**
     * Save active Liquichain payment id for order
     *
     * @param int $order_id
     *
     * @return $this
     */
    public function setActiveLiquichainPaymentForSubscriptions($order_id)
    {
        $order = wc_get_order($order_id);

        $order->update_meta_data('_liquichain_payment_id', static::$paymentId);
        $order->update_meta_data('_liquichain_payment_mode', $this->data->mode);

        $order->delete_meta_data('_liquichain_cancelled_payment_id');

        if (static::$customerId) {
            $order->update_meta_data('_liquichain_customer_id', static::$customerId);
        }

        // Also store it on the subscriptions being purchased or paid for in the order
        if (
            class_exists('WC_Subscriptions')
            && class_exists('WC_Subscriptions_Admin')
            && $this->dataHelper->isWcSubscription($order_id)
        ) {
            if (wcs_order_contains_subscription($order_id)) {
                $subscriptions = wcs_get_subscriptions_for_order($order_id);
            } elseif (wcs_order_contains_renewal($order_id)) {
                $subscriptions = wcs_get_subscriptions_for_renewal_order($order_id);
            } else {
                $subscriptions = array();
            }

            foreach ($subscriptions as $subscription) {
                $this->unsetActiveLiquichainPayment($subscription->get_id());
                $subscription->delete_meta_data('_liquichain_customer_id');
                $subscription->update_meta_data(
                    '_liquichain_payment_id',
                    static::$paymentId
                );
                $subscription->update_meta_data(
                    '_liquichain_payment_mode',
                    $this->data->mode
                );
                $subscription->delete_meta_data('_liquichain_cancelled_payment_id');
                if (static::$customerId) {
                    $subscription->update_meta_data(
                        '_liquichain_customer_id',
                        static::$customerId
                    );
                }
                $subscription->save();
            }
        }

        $order->save();
        return $this;
    }

    /**
     * Delete active Liquichain payment id for order
     *
     * @param int    $order_id
     * @param string $payment_id
     *
     * @return $this
     */
    public function unsetActiveLiquichainPayment($order_id, $payment_id = null)
    {
        if ($this->dataHelper->isSubscription($order_id)) {
            return $this->unsetActiveLiquichainPaymentForSubscriptions($order_id);
        }

        return $this->unsetActiveLiquichainPaymentForOrders($order_id);
    }

    /**
     * Delete active Liquichain payment id for order
     *
     * @param int $order_id
     *
     * @return $this
     */
    public function unsetActiveLiquichainPaymentForOrders($order_id)
    {
        // Only remove Liquichain payment details if they belong to this payment, not when a new payment was already placed
        $order = wc_get_order($order_id);
        $liquichain_payment_id = $order->get_meta('_liquichain_payment_id', true);

        if (is_object($this->data) && isset($this->data->id) && $liquichain_payment_id === $this->data->id) {
            $order->delete_meta_data('_liquichain_payment_id');
            $order->delete_meta_data('_liquichain_payment_mode');
            $order->save();
        }

        return $this;
    }

    /**
     * Delete active Liquichain payment id for order
     *
     * @param int $order_id
     *
     * @return $this
     */
    public function unsetActiveLiquichainPaymentForSubscriptions($order_id)
    {
        $order = wc_get_order($order_id);
        $order->delete_meta_data('_liquichain_payment_id');
        $order->delete_meta_data('_liquichain_payment_mode');
        $order->save();

        return $this;
    }

    /**
     * Get active Liquichain payment id for order
     *
     * @param int $order_id
     *
     * @return string
     */
    public function getActiveLiquichainPaymentId($order_id)
    {
        $order = wc_get_order($order_id);
        return $order->get_meta('_liquichain_payment_id', true);
    }

    /**
     * Get active Liquichain payment id for order
     *
     * @param int $order_id
     *
     * @return string
     */
    public function getActiveLiquichainOrderId($order_id)
    {
        $order = wc_get_order($order_id);
        return $order->get_meta('_liquichain_order_id', true);
    }

    /**
     * Get active Liquichain payment mode for order
     *
     * @param int $order_id
     *
     * @return string test or live
     */
    public function getActiveLiquichainPaymentMode($order_id)
    {
        $order = wc_get_order($order_id);
        return $order->get_meta('_liquichain_payment_mode', true);
    }

    /**
     * @param int  $order_id
     * @param bool $use_cache
     *
     * @return Payment|null
     */
    public function getActiveLiquichainPayment($order_id, $use_cache = true)
    {
        // Check if there is a payment ID stored with order and get it
        if ($this->hasActiveLiquichainPayment($order_id)) {
            return $this->getPaymentObjectPayment(
                $this->getActiveLiquichainPaymentId($order_id),
                $this->getActiveLiquichainPaymentMode($order_id) === 'test',
                $use_cache
            );
        }

        // If there is no payment ID, try to get order ID and if it's stored, try getting payment ID from API
        if ($this->hasActiveLiquichainOrder($order_id)) {
            $liquichain_order = $this->getPaymentObjectOrder($this->getActiveLiquichainOrderId($order_id));

            try {
                $liquichain_order = $this->paymentFactory->getPaymentObject(
                    $liquichain_order
                );
            } catch (ApiException $exception) {
                $this->logger->log(LogLevel::DEBUG, $exception->getMessage());
                return;
            }

            return $this->getPaymentObjectPayment(
                $liquichain_order->getLiquichainPaymentIdFromPaymentObject(),
                $this->getActiveLiquichainPaymentMode($order_id) === 'test',
                $use_cache
            );
        }

        return null;
    }

    /**
     * Check if the order has an active Liquichain payment
     *
     * @param int $order_id
     *
     * @return bool
     */
    public function hasActiveLiquichainPayment($order_id)
    {
        $liquichain_payment_id = $this->getActiveLiquichainPaymentId($order_id);

        return ! empty($liquichain_payment_id);
    }

    /**
     * Check if the order has an active Liquichain order
     *
     * @param int $order_id
     *
     * @return bool
     */
    public function hasActiveLiquichainOrder($order_id)
    {
        $liquichain_payment_id = $this->getActiveLiquichainOrderId($order_id);

        return ! empty($liquichain_payment_id);
    }

    /**
     * @param int    $order_id
     * @param string $payment_id
     *
     * @return $this
     */
    public function setCancelledLiquichainPaymentId($order_id, $payment_id)
    {
        $order = wc_get_order($order_id);
        $order->update_meta_data('_liquichain_cancelled_payment_id', $payment_id);
        $order->save();

        return $this;
    }

    /**
     * @param int $order_id
     *
     * @return null
     */
    public function unsetCancelledLiquichainPaymentId($order_id)
    {
        // If this order contains a cancelled (previous) payment, remove it.
        $order = wc_get_order($order_id);
        $liquichain_cancelled_payment_id = $order->get_meta('_liquichain_cancelled_payment_id', true);

        if (! empty($liquichain_cancelled_payment_id)) {
            $order = wc_get_order($order_id);
            $order->delete_meta_data('_liquichain_cancelled_payment_id');
            $order->save();
        }

        return null;
    }

    /**
     * @param int $order_id
     *
     * @return string|false
     */
    public function getCancelledLiquichainPaymentId($order_id)
    {
        $order = wc_get_order($order_id);
        return $order->get_meta('_liquichain_cancelled_payment_id', true);
    }

    /**
     * Check if the order has been cancelled
     *
     * @param int $order_id
     *
     * @return bool
     */
    public function hasCancelledLiquichainPayment($order_id)
    {
        $cancelled_payment_id = $this->getCancelledLiquichainPaymentId($order_id);

        return ! empty($cancelled_payment_id);
    }

    public function getLiquichainPaymentIdFromPaymentObject()
    {
    }

    public function getLiquichainCustomerIdFromPaymentObject()
    {
    }

    /**
     * @param WC_Order                     $order
     * @param Payment $payment
     * @param string                       $paymentMethodTitle
     */
    public function onWebhookPaid(WC_Order $order, $payment, $paymentMethodTitle)
    {
    }

    /**
     * @param WC_Order                     $order
     * @param Payment $payment
     * @param string                       $paymentMethodTitle
     */
    protected function onWebhookCanceled(WC_Order $order, $payment, $paymentMethodTitle)
    {
    }

    /**
     * @param WC_Order                     $order
     * @param Payment $payment
     * @param string                       $paymentMethodTitle
     */
    protected function onWebhookFailed(WC_Order $order, $payment, $paymentMethodTitle)
    {
    }

    /**
     * @param WC_Order                     $order
     * @param Payment $payment
     * @param string                       $paymentMethodTitle
     */
    protected function onWebhookExpired(WC_Order $order, $payment, $paymentMethodTitle)
    {
    }

    /**
     * Process a payment object refund
     *
     * @param object $order
     * @param int    $orderId
     * @param object $paymentObject
     * @param null   $amount
     * @param string $reason
     */
    public function refund(WC_Order $order, $orderId, $paymentObject, $amount = null, $reason = '')
    {
    }

    /**
     * @return bool
     */
    protected function setOrderPaidAndProcessed(WC_Order $order)
    {
        $order->update_meta_data('_liquichain_paid_and_processed', '1');
        $order->save();

        return true;
    }

    /**
     * @return bool
     */
    protected function isOrderPaymentStartedByOtherGateway(WC_Order $order)
    {
        $order_id = $order->get_id();
        // Get the current payment method id for the order
        $payment_method_id = get_post_meta($order_id, '_payment_method', $single = true);
        // If the current payment method id for the order is not Liquichain, return true
        return strpos($payment_method_id, 'liquichain') === false;
    }
    /**
     * @param WC_Order $order
     */
    public function deleteSubscriptionFromPending(WC_Order $order)
    {
        if (
            class_exists('WC_Subscriptions')
            && class_exists(
                'WC_Subscriptions_Admin'
            ) && $this->dataHelper->isSubscription(
                $order->get_id()
            )
        ) {
            $this->deleteSubscriptionOrderFromPendingPaymentQueue($order);
        }
    }

    /**
     * @param WC_Order       $order
     * @param Order| Payment $payment
     */
    protected function addMandateIdMetaToFirstPaymentSubscriptionOrder(
        WC_Order $order,
        $payment
    ) {

        if (class_exists('WC_Subscriptions')) {
            $payment = isset($payment->_embedded->payments[0]) ? $payment->_embedded->payments[0] : false;
            if (
                $payment && $payment->sequenceType === 'first'
                && (property_exists($payment, 'mandateId') && $payment->mandateId !== null)
            ) {
                $order->update_meta_data(
                    '_liquichain_mandate_id',
                    $payment->mandateId
                );
                $order->save();
            }
        }
    }


    protected function addSequenceTypeForSubscriptionsFirstPayments($orderId, $gateway, $paymentRequestData): array
    {
        if ($this->dataHelper->isSubscription($orderId)) {
            $disable_automatic_payments = apply_filters( $this->pluginId . '_is_automatic_payment_disabled', false );
            $supports_subscriptions = $gateway->supports('subscriptions');

            if ($supports_subscriptions == true && $disable_automatic_payments == false) {
                $paymentRequestData = $this->addSequenceTypeFirst($paymentRequestData);
            }
        }
        return $paymentRequestData;
    }

    public function addSequenceTypeFirst($paymentRequestData)
    {
    }

    /**
     * @param $order
     */
    public function deleteSubscriptionOrderFromPendingPaymentQueue($order)
    {
        global $wpdb;

        $wpdb->delete(
            $wpdb->liquichain_pending_payment,
            [
                'post_id' => $order->get_id(),
            ]
        );
    }

    /**
     * @param WC_Order $order
     *
     * @return bool
     */
    protected function isFinalOrderStatus(WC_Order $order)
    {
        $orderStatus = $order->get_status();

        return in_array(
            $orderStatus,
            self::FINAL_STATUSES,
            true
        );
    }
    /**
     * @param                               $orderId
     * @param WC_Payment_Gateway            $gateway
     * @param WC_Order                      $order
     * @param                               $newOrderStatus
     * @param                               $paymentMethodTitle
     * @param Payment|Order $payment
     */
    protected function failedSubscriptionProcess(
        $orderId,
        WC_Payment_Gateway $gateway,
        WC_Order $order,
        $newOrderStatus,
        $paymentMethodTitle,
        $payment
    ) {

        if (
            function_exists('wcs_order_contains_renewal')
            && wcs_order_contains_renewal($orderId)
        ) {
            if ($gateway instanceof LiquichainPaymentGateway) {
                $gateway->paymentService->updateOrderStatus(
                    $order,
                    $newOrderStatus,
                    sprintf(
                    /* translators: Placeholder 1: payment method title, placeholder 2: payment ID */
                        __(
                            '%1$s renewal payment failed via Liquichain (%2$s). You will need to manually review the payment and adjust product stocks if you use them.',
                            'liquichain-payments-for-woocommerce'
                        ),
                        $paymentMethodTitle,
                        $payment->id . ($payment->mode === 'test' ? (' - ' . __(
                            'test mode',
                            'liquichain-payments-for-woocommerce'
                        )) : '')
                    ),
                    $restoreStock = false
                );
            }
            $this->logger->log(
                LogLevel::DEBUG,
                __METHOD__ . ' called for order ' . $orderId . ' and payment '
                . $payment->id . ', renewal order payment failed, order set to '
                . $newOrderStatus . ' for shop-owner review.'
            );
            // Send a "Failed order" email to notify the admin
            $emails = WC()->mailer()->get_emails();
            if (
                !empty($emails) && !empty($orderId)
                && !empty($emails['WC_Email_Failed_Order'])
            ) {
                $emails['WC_Email_Failed_Order']->trigger($orderId);
            }
        } elseif ($gateway instanceof LiquichainPaymentGateway) {
            $gateway->paymentService->updateOrderStatus(
                $order,
                $newOrderStatus,
                sprintf(
                /* translators: Placeholder 1: payment method title, placeholder 2: payment ID */
                    __(
                        '%1$s payment failed via Liquichain (%2$s).',
                        'liquichain-payments-for-woocommerce'
                    ),
                    $paymentMethodTitle,
                    $payment->id . ($payment->mode === 'test' ? (' - ' . __(
                        'test mode',
                        'liquichain-payments-for-woocommerce'
                    )) : '')
                )
            );
        }
    }

    /**
     * @param $orderId
     * @param string $gatewayId
     * @param WC_Order $order
     */
    protected function informNotUpdatingStatus($orderId, $gatewayId, WC_Order $order)
    {
        $orderPaymentMethodTitle = get_post_meta(
            $orderId,
            '_payment_method_title',
            $single = true
        );

        // Add message to log
        $this->logger->log(
            LogLevel::DEBUG,
            $gatewayId . ': Order ' . $order->get_id()
            . ' webhook called, but payment also started via '
            . $orderPaymentMethodTitle . ', so order status not updated.',
            [true]
        );

        // Add order note
        $order->add_order_note(
            sprintf(
            /* translators: Placeholder 1: payment method title, placeholder 2: payment ID */
                __(
                    'Liquichain webhook called, but payment also started via %s, so the order status is not updated.',
                    'liquichain-payments-for-woocommerce'
                ),
                $orderPaymentMethodTitle
            )
        );
    }

    protected function addPaypalTransactionIdToOrder(
        WC_Order $order
    ) {

        $payment = $this->getActiveLiquichainPayment($order->get_id());

        if ($payment->isPaid() && $payment->details) {
            update_post_meta($order->get_id(), '_paypal_transaction_id', $payment->details->paypalReference);
            $order->add_order_note(sprintf(
                                   /* translators: Placeholder 1: PayPal consumer name, placeholder 2: PayPal email, placeholder 3: PayPal transaction ID */
                __("Payment completed by <strong>%1\$s</strong> - %2\$s (PayPal transaction ID: %3\$s)", 'liquichain-payments-for-woocommerce'),
                $payment->details->consumerName,
                $payment->details->consumerAccount,
                $payment->details->paypalReference
            ));
        }
    }
    /**
     * Get the url to return to on Liquichain return
     * saves the return redirect and failed redirect, so we save the page language in case there is one set
     * For example 'http://liquichain-wc.docker.myhost/wc-api/liquichain_return/?order_id=89&key=wc_order_eFZyH8jki6fge'
     *
     * @param WC_Order $order The order processed
     *
     * @return string The url with order id and key as params
     */
    public function getReturnUrl($order, $returnUrl)
    {
        $returnUrl = untrailingslashit($returnUrl);
        $returnUrl = $this->asciiDomainName($returnUrl);
        $orderId = $order->get_id();
        $orderKey = $order->get_order_key();

        $onLiquichainReturn = 'onLiquichainReturn';
        $returnUrl = $this->appendOrderArgumentsToUrl(
            $orderId,
            $orderKey,
            $returnUrl,
            $onLiquichainReturn
        );
        $returnUrl = untrailingslashit($returnUrl);
        $this->logger->log(LogLevel::DEBUG, " Order {$orderId} returnUrl: {$returnUrl}", [true]);

        return apply_filters($this->pluginId . '_return_url', $returnUrl, $order);
    }
    /**
     * Get the webhook url
     * For example 'http://liquichain-wc.docker.myhost/wc-api/liquichain_return/liquichain_wc_gateway_bancontact/?order_id=89&key=wc_order_eFZyH8jki6fge'
     *
     * @param WC_Order $order The order processed
     *
     * @return string The url with gateway and order id and key as params
     */
    public function getWebhookUrl($order, $gatewayId)
    {
        $webhookUrl = WC()->api_request_url($gatewayId);
        $webhookUrl = untrailingslashit($webhookUrl);
        $webhookUrl = $this->asciiDomainName($webhookUrl);
        $orderId = $order->get_id();
        $orderKey = $order->get_order_key();
        $webhookUrl = $this->appendOrderArgumentsToUrl(
            $orderId,
            $orderKey,
            $webhookUrl
        );
        $webhookUrl = untrailingslashit($webhookUrl);

        $this->logger->log(LogLevel::DEBUG, " Order {$orderId} webhookUrl: {$webhookUrl}", [true]);

        return apply_filters($this->pluginId . '_webhook_url', $webhookUrl, $order);
    }
    /**
     * @param $url
     *
     * @return string
     */
    protected function asciiDomainName($url): string
    {
        $parsed = parse_url($url);
        $scheme = isset($parsed['scheme'])?$parsed['scheme']:'';
        $domain = isset($parsed['host'])?$parsed['host']:false;
        $query = isset($parsed['query'])?$parsed['query']:'';
        $path = isset($parsed['path'])?$parsed['path']:'';
        if(!$domain){
            return $url;
        }

        if (function_exists('idn_to_ascii')) {
            $domain = $this->idnEncodeDomain($domain);
            $url = $scheme . "://". $domain . $path . '?' . $query;
        }

        return $url;
    }
    /**
     * @param $order_id
     * @param $order_key
     * @param $webhook_url
     * @param string $filterFlag
     *
     * @return string
     */
    protected function appendOrderArgumentsToUrl($order_id, $order_key, $webhook_url, $filterFlag = '')
    {
        $webhook_url = add_query_arg(
            [
                'order_id' => $order_id,
                'key' => $order_key,
                'filter_flag' => $filterFlag,
            ],
            $webhook_url
        );
        return $webhook_url;
    }

    /**
     * @param $domain
     * @return false|mixed|string
     */
    protected function idnEncodeDomain($domain)
    {
        if (defined('IDNA_NONTRANSITIONAL_TO_ASCII')
            && defined(
                'INTL_IDNA_VARIANT_UTS46'
            )
        ) {
            $domain = idn_to_ascii(
                $domain,
                IDNA_NONTRANSITIONAL_TO_ASCII,
                INTL_IDNA_VARIANT_UTS46
            ) ? idn_to_ascii(
                $domain,
                IDNA_NONTRANSITIONAL_TO_ASCII,
                INTL_IDNA_VARIANT_UTS46
            ) : $domain;
        } else {
            $domain = idn_to_ascii($domain) ? idn_to_ascii($domain) : $domain;
        }
        return $domain;
    }
    protected function getPaymentDescription($order, $option)
    {
        $description = !$option ? '' : trim($option);
        $description = !$description ? '{orderNumber}' : $description;

        switch ($description) {
            // Support for old deprecated options.
            // TODO: remove when deprecated
            case '{orderNumber}':
                $description =
                    /* translators: do not translate between {} */
                    _x(
                        'Order {orderNumber}',
                        'Payment description for {orderNumber}',
                        'liquichain-payments-for-woocommerce'
                    );
                $description = $this->replaceTagsDescription($order, $description);
                break;
            case '{storeName}':
                $description =
                    /* translators: do not translate between {} */
                    _x(
                        'StoreName {storeName}',
                        'Payment description for {storeName}',
                        'liquichain-payments-for-woocommerce'
                    );
                $description = $this->replaceTagsDescription($order, $description);
                break;
            case '{customer.firstname}':
                $description =
                    /* translators: do not translate between {} */
                    _x(
                        'Customer Firstname {customer.firstname}',
                        'Payment description for {customer.firstname}',
                        'liquichain-payments-for-woocommerce'
                    );
                $description = $this->replaceTagsDescription($order, $description);
                break;
            case '{customer.lastname}':
                $description =
                    /* translators: do not translate between {} */
                    _x(
                        'Customer Lastname {customer.lastname}',
                        'Payment description for {customer.lastname}',
                        'liquichain-payments-for-woocommerce'
                    );
                $description = $this->replaceTagsDescription($order, $description);
                break;
            case '{customer.company}':
                $description =
                /* translators: do not translate between {} */
                    _x(
                        'Customer Company {customer.company}',
                        'Payment description for {customer.company}',
                        'liquichain-payments-for-woocommerce'
                    );
                $description = $this->replaceTagsDescription($order, $description);
                break;
            // Support for custom string with interpolation.
            default:
                // Replace available description tags.
                $description = $this->replaceTagsDescription($order, $description);
                break;
        }

        // Fall back on default if description turns out empty.
        return !$description ? __('Order', 'woocommerce' ) . ' ' . $order->get_order_number() : $description;
    }

    /**
     * @param $order
     * @param $description
     * @return array|string|string[]
     */
    protected function replaceTagsDescription($order, $description)
    {
        $replacement_tags = [
            '{orderNumber}' => $order->get_order_number(),
            '{storeName}' => get_bloginfo('name'),
            '{customer.firstname}' => $order->get_billing_first_name(),
            '{customer.lastname}' => $order->get_billing_last_name(),
            '{customer.company}' => $order->get_billing_company(),
        ];
        foreach ($replacement_tags as $tag => $replacement) {
            $description = str_replace($tag, $replacement, $description);
        }
        return $description;
    }
}
