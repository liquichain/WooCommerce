<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods;

use WC_Order;

class Banktransfer extends AbstractPaymentMethod implements PaymentMethodI
{

    /**
     * @var int
     */
    public const EXPIRY_DEFAULT_DAYS = 12;
    /**
     * @var int
     */
    public const EXPIRY_MIN_DAYS = 5;
    /**
     * @var int
     */
    public const EXPIRY_MAX_DAYS = 60;
    /**
     * @var string
     */
    public const EXPIRY_DAYS_OPTION = 'order_dueDate';

    protected function getConfig(): array
    {
        return [
            'id' => 'banktransfer',
            'defaultTitle' => __('Bank Transfer', 'liquichain-payments-for-woocommerce'),
            'settingsDescription' => '',
            'defaultDescription' => '',
            'paymentFields' => false,
            'instructions' => true,
            'supports' => [
                'products',
                'refunds',
            ],
            'filtersOnBuild' => true,
            'confirmationDelayed' => true,
            'SEPA' => false,
            'customRedirect' => true,
        ];
    }

    public function getFormFields($generalFormFields): array
    {
        unset($generalFormFields['activate_expiry_days_setting']);
        unset($generalFormFields['order_dueDate']);
        $paymentMethodFormFieds = [
            'activate_expiry_days_setting' => [
                'title' => __('Activate expiry date setting', 'liquichain-payments-for-woocommerce'),
                'label' => __('Enable expiry date for payments', 'liquichain-payments-for-woocommerce'),
                'description' => __('Enable this option if you want to be able to set the number of days after the payment will expire. This will turn all transactions into payments instead of orders', 'liquichain-payments-for-woocommerce'),
                'type' => 'checkbox',
                'default' => 'no',
            ],
            'order_dueDate' => [
                'title' => __('Expiry date', 'liquichain-payments-for-woocommerce'),
                'type' => 'number',
                /* translators: Placeholder 1: Default expiry days. */
                'description' => sprintf(__('Number of DAYS after the payment will expire. Default <code>%d</code> days', 'liquichain-payments-for-woocommerce'), self::EXPIRY_DEFAULT_DAYS),
                'default' => self::EXPIRY_DEFAULT_DAYS,
                'custom_attributes' => [
                    'min' => self::EXPIRY_MIN_DAYS,
                    'max' => self::EXPIRY_MAX_DAYS,
                    'step' => 1,
                ],
            ],
            'skip_liquichain_payment_screen' => [
                'title' => __('Skip Liquichain payment screen', 'liquichain-payments-for-woocommerce'),
                'label' => __('Skip Liquichain payment screen when Bank Transfer is selected', 'liquichain-payments-for-woocommerce'),
                'description' => __('Enable this option if you want to skip redirecting your user to the Liquichain payment screen, instead this will redirect your user directly to the WooCommerce order received page displaying instructions how to complete the Bank Transfer payment.', 'liquichain-payments-for-woocommerce'),
                'type' => 'checkbox',
                'default' => 'no',
            ],
        ];
        return array_merge($generalFormFields, $paymentMethodFormFieds);
    }

    public function filtersOnBuild()
    {
        add_filter('woocommerce_' . $this->getProperty('id') . '_args', function (array $args, \WC_Order $order): array {
            return $this->addPaymentArguments($args, $order);
        }, 10, 2);
    }
    /**
     * @param WC_Order $order
     * @return array
     */
    public function addPaymentArguments(array $args, WC_Order $order)
    {
        // Expiry date
        $expiry_days = (int)$this->getProperty(self::EXPIRY_DAYS_OPTION) ?: self::EXPIRY_DEFAULT_DAYS;

        if ($expiry_days >= self::EXPIRY_MIN_DAYS && $expiry_days <= self::EXPIRY_MAX_DAYS) {
            $expiry_date = date("Y-m-d", strtotime(sprintf('+%s days', $expiry_days)));

            // Add dueDate at the correct location
            if (isset($args['payment'])) {
                $args['payment']['dueDate'] = $expiry_date;
            } else {
                $args['dueDate'] = $expiry_date;
            }
            $email = (ctype_space($order->get_billing_email())) ? null
                : $order->get_billing_email();
            if ($email) {
                $args['billingEmail'] = $email;
            }
        }

        return $args;
    }

    //TODO is this needed??
    public function isExpiredDateSettingActivated()
    {
        $expiryDays = $this->getProperty(
            'activate_expiry_days_setting'
        );
        return liquichainWooCommerceStringToBoolOption($expiryDays);
    }
}
