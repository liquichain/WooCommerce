<?php

namespace Liquichain\WooCommerceTests\Functional\ApplePayButton;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Liquichain\Api\Endpoints\OrderEndpoint;
use Liquichain\Api\LiquichainApiClient;
use Liquichain\WooCommerce\Buttons\ApplePayButton\AppleAjaxRequests;
use Liquichain\WooCommerce\Buttons\ApplePayButton\ResponsesToApple;
use Liquichain\WooCommerce\Gateway\Surcharge;
use Liquichain\WooCommerce\Payment\RefundLineItemsBuilder;
use Liquichain\WooCommerce\Shared\Data;
use Liquichain\WooCommerce\Subscription\LiquichainSubscriptionGateway;
use Liquichain\WooCommerceTests\Functional\HelperMocks;
use Liquichain\WooCommerceTests\Stubs\postDTOTestsStubs;
use Liquichain\WooCommerceTests\Stubs\WooCommerceMocks;
use Liquichain\WooCommerceTests\TestCase;
use PHPUnit_Framework_Exception;
use WC_Countries;

use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\stubs;
use function Brain\Monkey\Functions\when;

class AjaxRequestsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var RefundLineItemsBuilder
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
    /** @var WooCommerceMocks */
    private $wooCommerceMocks;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->helperMocks = new HelperMocks();
        $this->wooCommerceMocks = new WooCommerceMocks();
    }

    public function testValidateMerchant()
    {
        /*
         * Stubs
         */
        $postDummyData = new postDTOTestsStubs();
        $_POST = [
            'validationUrl' => $postDummyData->validationUrl,
            'woocommerce-process-checkout-nonce' => $postDummyData->nonce,
        ];
        $responseFromLiquichain = ["response from Liquichain"];
        stubs(
            [
                'get_site_url' => 'http://www.testdomain.com',

            ]
        );
        list($logger, $responsesTemplate) = $this->responsesToApple();
        $apiClientMock = $this->createConfiguredMock(
            LiquichainApiClient::class,
            []
        );

        /*
         * Sut
         */
        $testee = $this->buildTesteeMock(
            AppleAjaxRequests::class,
            [
                $responsesTemplate,
                $this->helperMocks->noticeMock(),
                $logger,
                $this->helperMocks->apiHelper($apiClientMock),
                $this->helperMocks->settingsHelper(),
            ],
            ['validationApiWalletsEndpointCall']
        )->getMock();


        /*
         * Expectations
         */
        expect('wp_verify_nonce')
            ->once()
            ->with($_POST['woocommerce-process-checkout-nonce'], 'woocommerce-process_checkout')
            ->andReturn(true);
        $testee->expects($this->once())->method(
            'validationApiWalletsEndpointCall'
        )->with('www.testdomain.com', $_POST['validationUrl'])->willReturn(
            $responseFromLiquichain
        );
        expect('update_option')
            ->once()
            ->with('liquichain_wc_applepay_validated', 'yes');
        expect('wp_send_json_success')
            ->once()
            ->with($responseFromLiquichain);
        /*
         * Execute Test
         */
        $testee->validateMerchant();
    }



    public function testUpdateShippingContactError()
    {
        /*
         * Stubs
         */
        $postDummyData = new postDTOTestsStubs();
        $expected = [
            'errors' => [
                [
                    "code" => 'addressUnserviceable',
                    "contactField" => null,
                    "message" => "",
                ]
            ],
            'newTotal' => [
                'label' => "Blog Name",
                'amount' => "0",
                'type' => "pending"
            ]
        ];
        $_POST = [
            'callerPage' => 'productDetail',
            'woocommerce-process-checkout-nonce' => $postDummyData->nonce,
            'simplifiedContact' => [
                'locality' => 'locality',
                'postalCode' => 'postalCode',
                'countryCode' => 'ES'
            ],
            'needShipping' => $postDummyData->needShipping,
            'productId' => $postDummyData->productId,
            'productQuantity' => $postDummyData->productQuantity

        ];
        stubs(
            [
                'WC' => $this->wooCommerce('1.00', '1.00', '2.20', '0.20'),
                'wc_get_base_location' => ['country' => 'IT'],
                'get_bloginfo' => 'Blog Name'

            ]
        );
        list($logger, $responsesTemplate) = $this->responsesToApple();
        $apiClientMock = $this->createConfiguredMock(
            LiquichainApiClient::class,
            []
        );

        /*
         * Sut
         */
        $testee = $this->buildTesteeMock(
            AppleAjaxRequests::class,
            [
                $responsesTemplate,
                $this->helperMocks->noticeMock(),
                $logger,
                $this->helperMocks->apiHelper($apiClientMock),
                $this->helperMocks->settingsHelper(),
            ],
            ['createWCCountries', 'getShippingPackages']
        )->getMock();

        /*
         * Expectations
         */
        expect('wp_verify_nonce')
            ->once()
            ->with($_POST['woocommerce-process-checkout-nonce'], 'woocommerce-process_checkout')
            ->andReturn(true);
        $testee->expects($this->once())
            ->method('createWCCountries')
            ->willReturn($this->wcCountries());
        $testee->expects($this->never())
            ->method('getShippingPackages');

        expect('wp_send_json_error')
            ->once()
            ->with($expected);

        /*
         * Execute Test
         */
        $testee->updateShippingContact();
    }

    public function testUpdateShippingContactErrorMissingData()
    {
        /*
         * Stubs
         */
        $postDummyData = new postDTOTestsStubs();
        $expected = [
            'errors' => [
                [
                    "code" => 'shipping Contact Invalid',
                    "contactField" => 'postalCode',
                    "message" => "Missing postalCode",
                ],
                [
                    "code" => 'shipping Contact Invalid',
                    "contactField" => 'countryCode',
                    "message" => "Missing countryCode",
                ],
            ],
            'newTotal' => [
                'label' => "Blog Name",
                'amount' => "0",
                'type' => "pending"
            ]
        ];
        $_POST = [
            'callerPage' => 'productDetail',
            'woocommerce-process-checkout-nonce' => $postDummyData->nonce,
            'simplifiedContact' => [
                'locality' => 'locality',
                'postalCode' => '',
                'countryCode' => ''
            ],
            'needShipping' => $postDummyData->needShipping,
            'productId' => $postDummyData->productId,
            'productQuantity' => $postDummyData->productQuantity

        ];
        stubs(
            [
                'wc_get_base_location' => ['country' => 'IT'],
                'get_bloginfo' => 'Blog Name'

            ]
        );
        list($logger, $responsesTemplate) = $this->responsesToApple();
        $apiClientMock = $this->createConfiguredMock(
            LiquichainApiClient::class,
            []
        );

        /*
         * Sut
         */
        $testee = $this->buildTesteeMock(
            AppleAjaxRequests::class,
            [
                $responsesTemplate,
                $this->helperMocks->noticeMock(),
                $logger,
                $this->helperMocks->apiHelper($apiClientMock),
                $this->helperMocks->settingsHelper(),
            ],
            ['createWCCountries']
        )->getMock();


        /*
         * Expectations
         */
        expect('wp_verify_nonce')
            ->once()
            ->with($_POST['woocommerce-process-checkout-nonce'], 'woocommerce-process_checkout')
            ->andReturn(true);
        $testee->expects($this->never())
            ->method('createWCCountries')
            ->willReturn($this->wcCountries());
        expect('wp_send_json_error')
            ->once()
            ->with($expected);

        /*
         * Execute Test
         */
        $testee->updateShippingContact();
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
    private function wooCommerce(
        $subtotal = 0,
        $shippingTotal = 0,
        $total = 0,
        $tax = 0
    ) {
        return $this->wooCommerceMocks->wooCommerce($subtotal, $shippingTotal, $total, $tax);
    }

    /**
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     * @throws PHPUnit_Framework_Exception
     */
    private function wcCart($subtotal, $shippingTotal, $total, $tax)
    {
        return $this->wooCommerceMocks->wcCart($subtotal, $shippingTotal, $total, $tax);
    }

    /**
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     * @throws PHPUnit_Framework_Exception
     */
    private function wcCustomer()
    {
        return $this->wooCommerceMocks->wcCustomer();
    }

    /**
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     * @throws PHPUnit_Framework_Exception
     */
    private function wcCountries()
    {
        return $this->wooCommerceMocks->wcCountries();
    }

    /**
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     * @throws PHPUnit_Framework_Exception
     */
    private function wcShipping()
    {
        return $this->wooCommerceMocks->wcShipping();
    }

    /**
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     * @throws PHPUnit_Framework_Exception
     */
    private function wcShippingRate($id, $label, $cost)
    {
        return $this->wooCommerceMocks->wcShippingRate($id, $label, $cost);
    }

    /**
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     * @throws PHPUnit_Framework_Exception
     */
    private function wcSession()
    {
        return $this->wooCommerceMocks->wcSession();
    }

    /**
     *
     * @throws PHPUnit_Framework_Exception
     */
    private function wcOrder()
    {
        return $this->wooCommerceMocks->wcOrder();
    }

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        when('__')->returnArg(1);
    }

    /**
     * @return array
     */
    protected function responsesToApple(): array
    {
        $logger = $this->helperMocks->loggerMock();
        $appleGateway = $this->liquichainGateway('applepay', false, true);
        $responsesTemplate = new ResponsesToApple($logger, $appleGateway);
        return array($logger, $responsesTemplate);
    }
}
