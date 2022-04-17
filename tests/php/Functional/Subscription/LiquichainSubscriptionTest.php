<?php # -*- coding: utf-8 -*-

namespace Liquichain\WooCommerceTests\Functional\Subscription;

use Liquichain\Api\Endpoints\CustomerEndpoint;
use Liquichain\Api\Endpoints\PaymentEndpoint;
use Liquichain\Api\Resources\Customer;
use Liquichain\Api\Resources\Mandate;
use Liquichain\Api\Resources\MandateCollection;
use Liquichain\Api\Resources\Payment;
use Liquichain\WooCommerce\Payment\LiquichainObject;
use Liquichain\WooCommerce\SDK\HttpResponse;
use Liquichain\WooCommerce\Subscription\LiquichainSubscriptionGateway;
use Liquichain\WooCommerceTests\Functional\HelperMocks;
use Liquichain\WooCommerceTests\TestCase;

use function Brain\Monkey\Functions\expect;


/**
 * Class Liquichain_WC_Plugin_Test
 */
class LiquichainSubscriptionTest extends TestCase
{
    /** @var HelperMocks */
    private $helperMocks;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->helperMocks = new HelperMocks();
    }

    /**
     * GIVEN I RECEIVE A WC ORDER WITH SUBSCRIPTION
     * THEN CREATES CORRECT LIQUICHAIN REQUEST ORDER
     * THEN THE DEBUG LOGS ARE CORRECT
     * THEN THE ORDER NOTES ARE CREATED
     * @test
     */
    public function renewSubcriptionPaymentTest()
    {
        $gatewayName = 'liquichain_wc_gateway_ideal';
        $renewalOrder = $this->wcOrder();
        $subscription = $this->wcOrder(2, $gatewayName, $renewalOrder, 'active' );

        $testee = $this->buildTestee();

        expect('wcs_get_subscriptions_for_renewal_order')->andReturn(
            [$subscription]
        );
        $testee->expects($this->once())->method(
            'restore_liquichain_customer_id_and_mandate'
        )->willReturn(false);
        expect('wc_get_payment_gateway_by_order')->andReturn($gatewayName);
        $renewalOrder->expects($this->once())->method(
            'set_payment_method'
        )->with($gatewayName);
        expect('get_post_meta')->with(1, '_payment_method', true);
        expect('wc_get_order')->with(1)->andReturn($renewalOrder);
        expect('wcs_order_contains_renewal')->with(1)->andReturn($renewalOrder);
        expect('wcs_get_subscription')->andReturn($subscription);

        $expectedResult = ['result' => 'success'];
        $result = $testee->scheduled_subscription_payment(1.02, $renewalOrder);
        $this->assertEquals($expectedResult, $result);
    }

    private function buildTestee(){
        $paymentMethod = $this->helperMocks->paymentMethodBuilder('Ideal');
        $paymentService = $this->helperMocks->paymentService();
        $orderInstructionsService = $this->helperMocks->orderInstructionsService();
        $liquichainOrderService = $this->helperMocks->liquichainOrderService();
        $data = $this->helperMocks->dataHelper();
        $logger = $this->helperMocks->loggerMock();
        $notice = $this->helperMocks->noticeMock();
        $HttpResponseService = new HttpResponse();
        $settingsHelper = $this->helperMocks->settingsHelper();
        $liquichainObject = $this->createMock(LiquichainObject::class);
        $apiClientMock = $this->helperMocks->apiClient();
        $mandate = $this->createMock(Mandate::class);
        $mandate->status = 'valid';
        $mandate->method = 'liquichain_wc_gateway_ideal';
        $customer = $this->createConfiguredMock(
            Customer::class,
            [
                'mandates'=> [$mandate]
            ]
        );
        $apiClientMock->customers = $this->createConfiguredMock(
            CustomerEndpoint::class,
            [
                'get'=> $customer
            ]
        );
        $paymentResponse = $this->createMock(Payment::class);
        $paymentResponse->method = 'ideal';
        $paymentResponse->mandateId = 'mandateId';
        $paymentResponse->resource = 'payment';
        $apiClientMock->payments = $this->createConfiguredMock(
            PaymentEndpoint::class,
            [
                'create'=> $paymentResponse
            ]
        );
        $paymentFactory = $this->helperMocks->paymentFactory($apiClientMock);
        $pluginId = $this->helperMocks->pluginId();
        $apiHelper = $this->helperMocks->apiHelper($apiClientMock);
        return $this->buildTesteeMock(
            LiquichainSubscriptionGateway::class,
            [
                $paymentMethod,
                $paymentService,
                $orderInstructionsService,
                $liquichainOrderService,
                $data,
                $logger,
                $notice,
                $HttpResponseService,
                $settingsHelper,
                $liquichainObject,
                $paymentFactory,
                $pluginId,
                $apiHelper
            ],
            [
                'init_form_fields',
                'initDescription',
                'initIcon',
                'isTestModeEnabledForRenewalOrder',
                'restore_liquichain_customer_id_and_mandate'
            ]
        )->getMock();
    }

    private function wcOrder($id = 1, $meta = false, $parentOrder = false, $status = 'processing')
    {
        $item = $this->createConfiguredMock(
            'WC_Order',
            [
                'get_id' => $id,
                'get_order_key' => 'wc_order_hxZniP1zDcnM8',
                'get_total' => '20',
                'get_items' => [$this->wcOrderItem()],
                'get_billing_first_name' => 'billingggivenName',
                'get_billing_last_name' => 'billingfamilyName',
                'get_billing_email' => 'billingemail',
                'get_shipping_first_name' => 'shippinggivenName',
                'get_shipping_last_name' => 'shippingfamilyName',
                'get_billing_address_1' => 'shippingstreetAndNumber',
                'get_billing_address_2' => 'billingstreetAdditional',
                'get_billing_postcode' => 'billingpostalCode',
                'get_billing_city' => 'billingcity',
                'get_billing_state' => 'billingregion',
                'get_billing_country' => 'billingcountry',
                'get_shipping_address_1' => 'shippingstreetAndNumber',
                'get_shipping_address_2' => 'shippingstreetAdditional',
                'get_shipping_postcode' => 'shippingpostalCode',
                'get_shipping_city' => 'shippingcity',
                'get_shipping_state' => 'shippingregion',
                'get_shipping_country' => 'shippingcountry',
                'get_shipping_methods' => false,
                'get_order_number' => 1,
                'get_payment_method' => 'liquichain_wc_gateway_ideal',
                'get_currency' => 'EUR',
                'get_meta' => $meta,
                'get_parent' => $parentOrder,
                'update_status'=>$status
            ]
        );

        return $item;
    }
    private function wcOrderItem()
    {
        $item = new \WC_Order_Item_Product();

        $item['quantity'] = 1;
        $item['variation_id'] = null;
        $item['product_id'] = 1;
        $item['line_subtotal_tax']= 0;
        $item['line_total']= 20;
        $item['line_subtotal']= 20;
        $item['line_tax']= 0;
        $item['tax_status']= '';
        $item['total']= 20;
        $item['name']= 'productName';

        return $item;
    }

}



