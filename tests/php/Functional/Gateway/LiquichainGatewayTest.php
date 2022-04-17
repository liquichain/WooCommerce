<?php # -*- coding: utf-8 -*-

namespace Liquichain\WooCommerceTests\Functional\Gateway;

use Liquichain\Api\Endpoints\CustomerEndpoint;
use Liquichain\Api\Endpoints\PaymentEndpoint;
use Liquichain\Api\Resources\Customer;
use Liquichain\Api\Resources\Mandate;
use Liquichain\Api\Resources\MandateCollection;
use Liquichain\Api\Resources\Payment;
use Liquichain\WooCommerce\Gateway\LiquichainPaymentGateway;
use Liquichain\WooCommerce\Payment\LiquichainObject;
use Liquichain\WooCommerce\SDK\HttpResponse;
use Liquichain\WooCommerce\Subscription\LiquichainSubscriptionGateway;
use Liquichain\WooCommerceTests\Functional\HelperMocks;
use Liquichain\WooCommerceTests\Stubs\WooCommerceMocks;
use Liquichain\WooCommerceTests\TestCase;

use function Brain\Monkey\Functions\expect;


/**
 * Class Liquichain_WC_Plugin_Test
 */
class LiquichainGatewayTest extends TestCase
{
    /** @var HelperMocks */
    private $helperMocks;
    /**
     * @var WooCommerceMocks
     */
    protected $wooCommerceMocks;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->helperMocks = new HelperMocks();
        $this->wooCommerceMocks = new WooCommerceMocks();
    }

    /**
     * WHEN gateway setting 'enabled' !== 'yes'
     * THEN is_available returns false
     * @test
     */
    public function gatewayNOTEnabledIsNOTAvailable()
    {
        $testee = $this->buildTestee(['enabled'=>'no']);

        $expectedResult = false;
        $result = $testee->is_available();
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * WHEN gateway setting 'enabled' !== 'yes'
     * THEN is_available returns true
     * @test
     */
    public function gatewayEnabledIsAvailable()
    {
        $testee = $this->buildTestee(['enabled'=>'yes']);
        $total = 10.00;
        $WC = $this->wooCommerceMocks->wooCommerce(10.00, 0, $total, 0);
        expect('WC')->andReturn($WC);
        $testee->expects($this->atLeast(2))->method('get_order_total')->willReturn($total);
        expect('get_woocommerce_currency')->andReturn('EUR');
        expect('get_transient')->andReturn([['id'=>'ideal']]);
        expect('wc_get_base_location')->andReturn(['country'=>'ES']);

        $expectedResult = true;
        $result = $testee->is_available();
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * WHEN gateway setting 'enabled' !== 'yes'
     * AND the customer has no country set
     * THEN we fallback to the shop country and is_available returns true
     * @test
     */
    public function gatewayAvailableWhenNoCountrySelected()
    {
        $testee = $this->buildTestee(['enabled'=>'yes']);
        $total = 10.00;
        $WC = $this->wooCommerceMocks->wooCommerce(10.00, 0, $total, 0, '');
        expect('WC')->andReturn($WC);
        $testee->expects($this->atLeast(2))->method('get_order_total')->willReturn($total);
        expect('get_woocommerce_currency')->andReturn('EUR');
        expect('get_transient')->andReturn([['id'=>'ideal']]);
        expect('wc_get_base_location')->andReturn(['country'=>'ES']);

        $expectedResult = true;
        $result = $testee->is_available();
        $this->assertEquals($expectedResult, $result);
    }

    private function buildTestee($settings){
        $paymentMethod = $this->helperMocks->paymentMethodBuilder('Ideal', false, false, $settings);
        $paymentService = $this->helperMocks->paymentService();
        $orderInstructionsService = $this->helperMocks->orderInstructionsService();
        $liquichainOrderService = $this->helperMocks->liquichainOrderService();
        $data = $this->helperMocks->dataHelper();
        $logger = $this->helperMocks->loggerMock();
        $notice = $this->helperMocks->noticeMock();
        $HttpResponseService = new HttpResponse();
        $liquichainObject = $this->createMock(LiquichainObject::class);
        $apiClientMock = $this->helperMocks->apiClient();

        $paymentFactory = $this->helperMocks->paymentFactory($apiClientMock);
        $pluginId = $this->helperMocks->pluginId();

        return $this->buildTesteeMock(
            LiquichainPaymentGateway::class,
            [
                $paymentMethod,
                $paymentService,
                $orderInstructionsService,
                $liquichainOrderService,
                $data,
                $logger,
                $notice,
                $HttpResponseService,
                $liquichainObject,
                $paymentFactory,
                $pluginId
            ],
            [
                'init_form_fields',
                'initDescription',
                'initIcon',
                'get_order_total'
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



