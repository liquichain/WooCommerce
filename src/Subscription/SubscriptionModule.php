<?php

# -*- coding: utf-8 -*-

declare(strict_types=1);

namespace Liquichain\WooCommerce\Subscription;

use DateTime;
use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Liquichain\WooCommerce\Gateway\LiquichainPaymentGateway;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface as Logger;
use Psr\Log\LogLevel;

class SubscriptionModule implements ExecutableModule
{
    use ModuleClassNameIdTrait;

    /**
     * @var mixed
     */
    protected $logger;
    /**
     * @var mixed
     */
    protected $dataHelper;
    /**
     * @var mixed
     */
    protected $settingsHelper;

    public function run(ContainerInterface $container): bool
    {
        $this->logger = $container->get(Logger::class);
        $this->dataHelper = $container->get('settings.data_helper');
        $this->settingsHelper = $container->get('settings.settings_helper');
        $this->maybeFixSubscriptions();
        $this->schedulePendingPaymentOrdersExpirationCheck();
        return true;
    }

    /**
     * See MOL-322, MOL-405
     */
    public function maybeFixSubscriptions()
    {
        $fixer = new MaybeFixSubscription();
        $fixer->maybeFix();
    }

    /**
     * WCSubscription related.
     */
    public function schedulePendingPaymentOrdersExpirationCheck()
    {
        if (class_exists('WC_Subscriptions_Order')) {
            $settings_helper = $this->settingsHelper;
            $time = $settings_helper->getPaymentConfirmationCheckTime();
            $nextScheduledTime = wp_next_scheduled(
                'pending_payment_confirmation_check'
            );
            if (!$nextScheduledTime) {
                wp_schedule_event(
                    $time,
                    'daily',
                    'pending_payment_confirmation_check'
                );
            }

            add_action(
                'pending_payment_confirmation_check',
                [$this, 'checkPendingPaymentOrdersExpiration']
            );
        }
    }

    /**
     *
     */
    public function checkPendingPaymentOrdersExpiration()
    {
        global $wpdb;
        $currentDate = new DateTime();
        $items = $wpdb->get_results("SELECT * FROM {$wpdb->liquichain_pending_payment} WHERE expired_time < {$currentDate->getTimestamp()};");
        foreach ($items as $item) {
            $order = wc_get_order($item->post_id);

            // Check that order actually exists
            if ($order === false) {
                return false;
            }

            if ($order->get_status() === LiquichainPaymentGateway::STATUS_COMPLETED) {
                $new_order_status = LiquichainPaymentGateway::STATUS_FAILED;
                $paymentMethodId = $order->get_meta('_payment_method_title', true);
                $liquichainPaymentId = $order->get_meta('_liquichain_payment_id', true);
                $order->add_order_note(sprintf(
                                       /* translators: Placeholder 1: payment method title, placeholder 2: payment ID */
                    __('%1$s payment failed (%2$s).', 'liquichain-payments-for-woocommerce'),
                    $paymentMethodId,
                    $liquichainPaymentId
                ));

                $order->update_status($new_order_status, '');
                if ($order->get_meta('_order_stock_reduced', $single = true)) {
                    // Restore order stock
                    $this->dataHelper->restoreOrderStock($order);

                    $this->logger->log(LogLevel::DEBUG, __METHOD__ . " Stock for order {$order->get_id()} restored.");
                }

                $wpdb->delete(
                    $wpdb->liquichain_pending_payment,
                    [
                        'post_id' => $order->get_id(),
                    ]
                );
            }
        }
    }
}
