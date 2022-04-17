<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods;

class Voucher extends AbstractPaymentMethod implements PaymentMethodI
{

    /**
     * @var string
     */
    public const MEAL = 'meal';
    /**
     * @var string
     */
    public const ECO = 'eco';
    /**
     * @var string
     */
    public const GIFT = 'gift';
    /**
     * @var string
     */
    public const NO_CATEGORY = 'no_category';
    /**
     * @var string
     */
    public const LIQUICHAIN_VOUCHER_CATEGORY_OPTION = '_liquichain_voucher_category';

    protected function getConfig(): array
    {
        return [
            'id' => 'voucher',
            'defaultTitle' => __('Voucher', 'liquichain-payments-for-woocommerce'),
            'settingsDescription' => '',
            'defaultDescription' => __('', 'liquichain-payments-for-woocommerce'),
            'paymentFields' => false,
            'instructions' => false,
            'supports' => [
                'products',
            ],
            'filtersOnBuild' => false,
            'confirmationDelayed' => false,
            'SEPA' => false,
        ];
    }

    public function getFormFields($generalFormFields): array
    {
        $paymentMethodFormFieds = [

            'mealvoucher_category_default' => [
                'title' => __('Select the default products category', 'liquichain-payments-for-woocommerce'),
                'type' => 'select',
                'options' => [
                    self::NO_CATEGORY => $this->categoryName(self::NO_CATEGORY),
                    self::MEAL => $this->categoryName(self::MEAL),
                    self::ECO => $this->categoryName(self::ECO),
                    self::GIFT => $this->categoryName(self::GIFT),
                ],
                'default' => self::NO_CATEGORY,
                /* translators: Placeholder 1: Default order status, placeholder 2: Link to 'Hold Stock' setting */
                'description' => __('In order to process it, all products in the order must have a category. This selector will assign the default category for the shop products', 'liquichain-payments-for-woocommerce'),
                'desc_tip' => true,
            ],
        ];
        return array_merge($generalFormFields, $paymentMethodFormFieds);
    }

    private function categoryName($category)
    {
        return __(ucwords(str_replace('_', ' ', $category)), 'liquichain-payments-for-woocommerce');
    }
}
