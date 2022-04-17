<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\Subscription;

class MaybeFixSubscription
{

    public function maybeFix()
    {
        $fixSubscriptionsProcess = get_option('liquichain_wc_fix_subscriptions2', false);

        $hasSubscriptionPlugin = function_exists('wcs_get_subscriptions');
        $canSchedule = function_exists('as_schedule_single_action');
        if (!$fixSubscriptionsProcess && $hasSubscriptionPlugin && $canSchedule) {
            as_schedule_single_action(time(), 'runScheduledFix');
            add_action('runScheduledFix', function () {
                return $this->retrieveAndFixBrokenSubscriptions();
            });
        }
    }

    public function retrieveAndFixBrokenSubscriptions()
    {
        $subscriptions = wcs_get_subscriptions(
            [
                'subscriptions_per_page' => '-1',
                'meta_query' => [
                    [
                        'key' => '_liquichain_customer_id',
                        'value' => '',
                        'compare' => 'NOT EXISTS',
                    ],
                ],
            ]
        );
        foreach ($subscriptions as $subscription) {
            $customer = $subscription->get_meta('_liquichain_customer_id');
            //cst_*
            if (strlen($customer) < 5) {
                $parent = $subscription->get_parent();
                if ($parent) {
                    $subscription->update_meta_data('_liquichain_customer_id', $parent->get_meta('_liquichain_customer_id'));
                    $subscription->update_meta_data('_liquichain_order_id', $parent->get_meta('_liquichain_order_id'));
                    $subscription->update_meta_data('_liquichain_payment_id', $parent->get_meta('_liquichain_payment_id'));
                    $subscription->update_meta_data('_liquichain_payment_mode', $parent->get_meta('_liquichain_payment_mode'));
                    $subscription->save();
                }
            }
        }
        update_option('liquichain_wc_fix_subscriptions2', true);
    }
}
