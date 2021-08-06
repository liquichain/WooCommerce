<?php

declare(strict_types=1);

namespace Mollie\WooCommerce\Gateway\Voucher;

use Mollie\WooCommerce\Gateway\AbstractGateway;
use Mollie\WooCommerce\Gateway\PaymentService;
use Mollie\WooCommerce\Gateway\SurchargeService;
use Mollie\WooCommerce\Notice\NoticeInterface;
use Mollie\WooCommerce\Payment\MollieOrderService;
use Mollie\WooCommerce\SDK\HttpResponse;
use Mollie\WooCommerce\Utils\IconFactory;
use Psr\Log\LoggerInterface as Logger;

class Mollie_WC_Gateway_Voucher extends AbstractGateway
{
    const MEAL = 'meal';
    const ECO = 'eco';
    const GIFT = 'gift';
    const NO_CATEGORY = 'no_category';
    const MOLLIE_VOUCHER_CATEGORY_OPTION = '_mollie_voucher_category';

    /**
     *
     */
    public function __construct(
        IconFactory $iconFactory,
        PaymentService $paymentService,
        SurchargeService $surchargeService,
        MollieOrderService $mollieOrderService,
        Logger $logger,
        NoticeInterface $notice,
        HttpResponse $httpResponse
    ) {

        $this->supports = [
            'products',
        ];

        /* Has issuers dropdown */
        //$this->has_fields = TRUE;

        parent::__construct(
            $iconFactory,
            $paymentService,
            $surchargeService,
            $mollieOrderService,
            $logger,
            $notice,
            $httpResponse
        );
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        parent::init_form_fields();

        $this->form_fields = array_merge($this->form_fields, [
            'mealvoucher_category_default' => [
                'title' => __('Select the default products category', 'mollie-payments-for-woocommerce'),
                'type' => 'select',
                'options' => [
                    self::NO_CATEGORY => $this->categoryName(self::NO_CATEGORY),
                    self::MEAL => $this->categoryName(self::MEAL),
                    self::ECO => $this->categoryName(self::ECO),
                    self::GIFT => $this->categoryName(self::GIFT),
                ],
                'default' => self::NO_CATEGORY,
                /* translators: Placeholder 1: Default order status, placeholder 2: Link to 'Hold Stock' setting */
                'description' => sprintf(
                    __('In order to process it, all products in the order must have a category. This selector will assign the default category for the shop products', 'mollie-payments-for-woocommerce')
                ),
                'desc_tip' => true,
            ],
        ]);
    }

    /**
     * @return string
     */
    public function getMollieMethodId()
    {
        return 'voucher';
    }

    /**
     * @return string
     */
    public function getDefaultTitle()
    {
        return __('Voucher', 'mollie-payments-for-woocommerce');
    }

    /**
     * @return string
     */
    protected function getSettingsDescription()
    {
        return '';
    }

    /**
     * @return string
     */
    protected function getDefaultDescription()
    {
        /* translators: Default gift card dropdown description, displayed above issuer drop down */
        return __('voucher', 'mollie-payments-for-woocommerce');
    }

    private function categoryName($category)
    {
        return ucfirst(str_replace('_', ' ', $category));
    }
}
