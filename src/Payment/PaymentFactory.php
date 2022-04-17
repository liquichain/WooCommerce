<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\Payment;

use Liquichain\Api\Exceptions\ApiException;
use Liquichain\WooCommerce\SDK\Api;
use Liquichain\WooCommerce\Settings\Settings;
use Liquichain\WooCommerce\Shared\Data;

class PaymentFactory
{
    /**
     * @var Data
     */
    protected $dataHelper;
    /**
     * @var Api
     */
    protected $apiHelper;
    protected $settingsHelper;
    /**
     * @var string
     */
    protected $pluginId;
    protected $logger;

    /**
     * PaymentFactory constructor.
     */
    public function __construct(Data $dataHelper, Api $apiHelper, Settings $settingsHelper, string $pluginId, $logger)
    {
        $this->dataHelper = $dataHelper;
        $this->apiHelper = $apiHelper;
        $this->settingsHelper = $settingsHelper;
        $this->pluginId = $pluginId;
        $this->logger = $logger;
    }

    /**
     * @param $data
     * @return bool|LiquichainOrder|LiquichainPayment
     * @throws ApiException
     */
    public function getPaymentObject($data)
    {

        if (
            (!is_object($data) && $data === 'order')
            || (!is_object($data) && strpos($data, 'ord_') !== false)
            || (is_object($data) && $data->resource === 'order')
        ) {

            $refundLineItemsBuilder = new RefundLineItemsBuilder($this->dataHelper);
            $apiKey = $this->settingsHelper->getApiKey();
            $orderItemsRefunded = new OrderItemsRefunder(
                $refundLineItemsBuilder,
                $this->dataHelper,
                $this->apiHelper->getApiClient($apiKey)->orders
            );

            return new LiquichainOrder(
                $orderItemsRefunded,
                $data,
                $this->pluginId,
                $this->apiHelper,
                $this->settingsHelper,
                $this->dataHelper,
                $this->logger
            );
        }

        if (
            (!is_object($data) && $data === 'payment')
            || (!is_object($data) && strpos($data, 'tr_') !== false)
            || (is_object($data) && $data->resource === 'payment')
        ) {
            return new LiquichainPayment(
                $data,
                $this->pluginId,
                $this->apiHelper,
                $this->settingsHelper,
                $this->dataHelper,
                $this->logger
            );
        }

        return false;
    }
}
