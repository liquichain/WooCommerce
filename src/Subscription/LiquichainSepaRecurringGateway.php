<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\Subscription;

use DateInterval;
use DateTime;
use Liquichain\Api\Exceptions\ApiException;
use Liquichain\Api\Types\SequenceType;
use Liquichain\WooCommerce\Gateway\LiquichainPaymentGateway;
use Liquichain\WooCommerce\Notice\NoticeInterface;
use Liquichain\WooCommerce\Payment\LiquichainObject;
use Liquichain\WooCommerce\Payment\LiquichainOrderService;
use Liquichain\WooCommerce\Payment\OrderInstructionsService;
use Liquichain\WooCommerce\Payment\PaymentCheckoutRedirectService;
use Liquichain\WooCommerce\Payment\PaymentFactory;
use Liquichain\WooCommerce\Payment\PaymentService;
use Liquichain\WooCommerce\PaymentMethods\PaymentMethodI;
use Liquichain\WooCommerce\SDK\Api;
use Liquichain\WooCommerce\SDK\HttpResponse;
use Liquichain\WooCommerce\Settings\Settings;
use Liquichain\WooCommerce\Shared\Data;
use Psr\Log\LoggerInterface as Logger;
use Psr\Log\LogLevel;

class LiquichainSepaRecurringGateway extends LiquichainSubscriptionGateway
{

    const WAITING_CONFIRMATION_PERIOD_DAYS = '21';

    protected $recurringLiquichainMethod = null;
    protected $dataHelper;

    /**
     * AbstractSepaRecurring constructor.
     */
    public function __construct(
        PaymentMethodI $directDebitPaymentMethod,
        PaymentMethodI $paymentMethod,
        PaymentService $paymentService,
        OrderInstructionsService $orderInstructionsService,
        LiquichainOrderService $liquichainOrderService,
        Data $dataService,
        Logger $logger,
        NoticeInterface $notice,
        HttpResponse $httpResponse,
        Settings $settingsHelper,
        LiquichainObject $liquichainObject,
        PaymentFactory $paymentFactory,
        string $pluginId,
        Api $apiHelper
    ) {

        parent::__construct(
            $paymentMethod,
            $paymentService,
            $orderInstructionsService,
            $liquichainOrderService,
            $dataService,
            $logger,
            $notice,
            $httpResponse,
            $settingsHelper,
            $liquichainObject,
            $paymentFactory,
            $pluginId,
            $apiHelper
        );
        $directDebit = new LiquichainPaymentGateway(
            $directDebitPaymentMethod,
            $paymentService,
            $orderInstructionsService,
            $liquichainOrderService,
            $dataService,
            $logger,
            $notice,
            $httpResponse,
            $liquichainObject,
            $paymentFactory,
            $pluginId
        );
        if ($directDebit->enabled === 'yes') {
            $this->initSubscriptionSupport();
            $this->recurringLiquichainMethod = $directDebit;
        }
        return $this;
    }

    /**
     * @return string
     */
    protected function getRecurringLiquichainMethodId()
    {
        $result = null;
        if ($this->recurringLiquichainMethod) {
            $result = $this->recurringLiquichainMethod->paymentMethod->getProperty('id');
        }

        return $result;
    }

    /**
     * @return string
     */
    protected function getRecurringLiquichainMethodTitle()
    {
        $result = null;
        if ($this->recurringLiquichainMethod) {
            $result = $this->recurringLiquichainMethod->paymentMethod->getProperty('title');
        }

        return $result;
    }

    /**
     * @param $renewal_order
     * @param $initial_order_status
     * @param $payment
     */
    protected function _updateScheduledPaymentOrder($renewal_order, $initial_order_status, $payment)
    {
        $this->liquichainOrderService->updateOrderStatus(
            $renewal_order,
            $initial_order_status,
            sprintf(
                __("Awaiting payment confirmation.", 'liquichain-payments-for-woocommerce') . "\n",
                self::WAITING_CONFIRMATION_PERIOD_DAYS
            )
        );

        $payment_method_title = $this->getPaymentMethodTitle($payment);

        $renewal_order->add_order_note(sprintf(
        /* translators: Placeholder 1: Payment method title, placeholder 2: payment ID */
            __('%1$s payment started (%2$s).', 'liquichain-payments-for-woocommerce'),
            $payment_method_title,
            $payment->id . ($payment->mode === 'test' ? (' - ' . __('test mode', 'liquichain-payments-for-woocommerce')) : '')
        ));

        $this->addPendingPaymentOrder($renewal_order);
    }

    /**
     * @return bool
     */
    public function paymentConfirmationAfterCoupleOfDays(): bool
    {
        return true;
    }

    /**
     * @param $renewal_order
     */
    protected function addPendingPaymentOrder($renewal_order)
    {
        global $wpdb;

        $confirmationDate = new DateTime();
        $period = 'P' . self::WAITING_CONFIRMATION_PERIOD_DAYS . 'D';
        $confirmationDate->add(new DateInterval($period));
        $wpdb->insert(
            $wpdb->liquichain_pending_payment,
            [
                'post_id' => $renewal_order->get_id(),
                'expired_time' => $confirmationDate->getTimestamp(),
            ]
        );
    }

    /**
     * @param null $payment
     * @return string
     */
    protected function getPaymentMethodTitle($payment)
    {
        $payment_method_title = $this->method_title;
        $orderId = isset($payment->metadata) ? $payment->metadata->order_id : false;
        if ($orderId && $this->dataService->isWcSubscription($orderId) && $payment->method === $this->getRecurringLiquichainMethodId()) {
            $payment_method_title = $this->getRecurringLiquichainMethodTitle();
        }

        return $payment_method_title;
    }

    /**
     * @param $order
     * @param $payment
     */
    public function handlePaidOrderWebhook($order, $payment)
    {
        $orderId = $order->get_id();

        // Duplicate webhook call
        if (
            $this->dataService->isWcSubscription($orderId)
            && isset($payment->sequenceType)
            && $payment->sequenceType === SequenceType::SEQUENCETYPE_RECURRING
        ) {
            $payment_method_title = $this->getPaymentMethodTitle($payment);

            $isTestMode = $payment->mode === 'test';
            $paymentMessage = $payment->id . (
                $isTestMode
                    ? (' - ' . __('test mode', 'liquichain-payments-for-woocommerce'))
                    : ''
                );
            $order->add_order_note(
                sprintf(
                /* translators: Placeholder 1: payment method title, placeholder 2: payment ID */
                    __(
                        'Order completed using %1$s payment (%2$s).',
                        'liquichain-payments-for-woocommerce'
                    ),
                    $payment_method_title,
                    $paymentMessage
                )
            );

            try {
                $payment_object = $this->paymentFactory->getPaymentObject(
                    $payment
                );
            } catch (ApiException $exception) {
                $this->logger->log(LogLevel::DEBUG, $exception->getMessage());
                return;
            }

            $payment_object->deleteSubscriptionOrderFromPendingPaymentQueue($order);
            return;
        }

        parent::handlePaidOrderWebhook($order, $payment);
    }
}
