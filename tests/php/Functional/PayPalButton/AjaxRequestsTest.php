<?php

namespace Liquichain\WooCommerceTests\Functional\PayPalButton;

use AjaxRequests;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Liquichain\Api\Endpoints\OrderEndpoint;
use Liquichain\WooCommerce\Buttons\PayPalButton\PayPalAjaxRequests;
use Liquichain\WooCommerce\Buttons\PayPalButton\PayPalDataObjectHttp;
use Liquichain\WooCommerce\Gateway\Surcharge;
use Liquichain\WooCommerce\Subscription\LiquichainSubscriptionGateway;
use Liquichain\WooCommerceTests\Functional\HelperMocks;
use Liquichain\WooCommerceTests\Stubs\postDTOTestsStubs;
use Liquichain\WooCommerceTests\TestCase;
use Liquichain_WC_ApplePayButton_DataObjectHttp;
use Liquichain_WC_Helper_Data;
use Liquichain_WC_Payment_RefundLineItemsBuilder;
use PHPUnit_Framework_Exception;
use PHPUnit_Framework_MockObject_MockObject;

use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\stubs;
use function Brain\Monkey\Functions\when;

class AjaxRequestsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|RefundLineItemsBuilder
     */
    private $refundLineItemsBuilder;

    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @var OrderEndpoint
     */
    private $ordersApiClient;
    /** @var HelperMocks */
    private $helperMocks;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->helperMocks = new HelperMocks();
    }


    public function testcreateWcOrderSuccess()
    {
        /*
         * Stubs
         */
        $postDummyData = new postDTOTestsStubs();

        $_POST = [
            'nonce' => $postDummyData->nonce,
            'needShipping' => true,
            'productId' => $postDummyData->productId,
            'productQuantity' => $postDummyData->productQuantity,
        ];
        $order = $this->wcOrder();
        $orderId = $order->get_id();
        $paymentSurcharge = Surcharge::NO_FEE;
        $fixedFee = 10.00;
        $percentage = 0;
        $feeLimit = 1;
        stubs(
            [
                'wc_create_order' => $order,
            ]
        );
        $logger = $this->helperMocks->loggerMock();
        $paypalGateway = $this->liquichainGateway('paypal', false, true);

        $dataObject = new PayPalDataObjectHttp($logger);
        $dataObject->orderData($_POST, 'productDetail');


        /*
         * Sut
         */
        $testee = $this->buildTesteeMock(
            PayPalAjaxRequests::class,
            [
                $paypalGateway,
                $this->helperMocks->noticeMock(),
                $logger
            ],
            [
                'updateOrderPostMeta',
                'processOrderPayment',
                'addShippingMethodsToOrder',
            ]
        )->getMock();

        /*
         * Expectations
         */
        expect('wp_verify_nonce')
            ->once()
            ->with($_POST['nonce'], 'liquichain_PayPal_button')
            ->andReturn(true);
        expect('wc_get_product')
            ->once();
        expect('get_option')
            ->with('liquichain-payments-for-woocommerce_gatewayFeeLabel')
            ->andReturn(
                $this->helperMocks->paymentMethodSettings(
                    [
                        'payment_surcharge' => $paymentSurcharge,
                        'surcharge_limit' => $feeLimit,
                        'fixed_fee' => $fixedFee,
                        'percentage' => $percentage,
                    ]
                )
            );
        expect('wp_send_json_success')
            ->once()->with(['result' => 'success']);
        $testee->expects($this->once())->method(
            'updateOrderPostMeta'
        )->with($orderId, $order);
        $testee->expects($this->once())->method(
            'processOrderPayment'
        )->with($orderId)->willReturn(['result' => 'success']);

        /*
         * Execute Test
         */
        $testee->createWcOrder();
    }

    public function liquichainGateway($paymentMethodName, $isSepa = false, $isSubscription = false){
        $gateway = $this->createConfiguredMock(
            LiquichainSubscriptionGateway::class,
            [
            ]
        );
        $gateway->paymentMethod = $this->helperMocks->paymentMethodBuilder($paymentMethodName, $isSepa, $isSubscription);

        return $gateway;
    }

    /**
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     * @throws PHPUnit_Framework_Exception
     */
    private function wcOrder()
    {
        $item = $this->createConfiguredMock(
            'WC_Order',
            [
                'get_id' => 11,
            ]
        );

        return $item;
    }

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        when('__')->returnArg(1);
    }
}
