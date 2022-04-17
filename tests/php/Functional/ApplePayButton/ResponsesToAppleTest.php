<?php

namespace Liquichain\WooCommerceTests\Functional\ApplePayButton;

use Faker;
use Faker\Generator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Liquichain\WooCommerce\Buttons\ApplePayButton\ResponsesToApple;
use Liquichain\WooCommerce\Subscription\LiquichainSubscriptionGateway;
use Liquichain\WooCommerceTests\Functional\HelperMocks;
use Liquichain\WooCommerceTests\TestCase;

use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;


class ResponsesToAppleTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var Generator
     */
    protected $faker;
    /** @var HelperMocks */
    private $helperMocks;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->helperMocks = new HelperMocks();
    }

    /**
     *
     */
    public function testAppleFormattedResponseWithoutShippingMethod()
    {
        $fakeFactory = new Faker\Factory();
        $this->faker = $fakeFactory->create();
        $subtotal = $this->faker->numberBetween(1, 200);
        $taxes = $this->faker->numberBetween(1, 200);
        $total = $this->faker->numberBetween(1, 200);
        $totalLabel = $this->faker->word;
        $paymentDetails = [
            'subtotal' => $subtotal,
            'shipping' => [
                'amount' =>  null,
                'label' =>  null
            ],
            'shippingMethods' => null,
            'taxes' => $taxes,
            'total' => $total
        ];
        $expectedResponse = [
            'newLineItems'=>[
                [
                    "label" => 'Subtotal',
                    "amount" => $subtotal,
                    "type" => 'final'
                ],
                [
                    "label" => 'Estimated Tax',
                    "amount" => $taxes,
                    "type" => 'final'
                ]
            ],
            'newTotal'=>[
                "label" => $totalLabel,
                "amount" => $total,
                "type" => 'final'
            ]

        ];

        expect('get_bloginfo')
            ->once()
            ->with('name')
            ->andReturn($totalLabel);
        /*
         * Sut
         */
        $logger = $this->helperMocks->loggerMock();
        $appleGateway = $this->liquichainGateway('applepay', false, true);
        $responsesTemplate = new ResponsesToApple($logger, $appleGateway);
        $response = $responsesTemplate->appleFormattedResponse($paymentDetails);

        self::assertEquals($response, $expectedResponse);
    }

    public function testAppleFormattedResponseWithShippingMethod()
    {
        $fakeFactory = new Faker\Factory();
        $this->faker = $fakeFactory->create();
        $subtotal = $this->faker->numberBetween(1, 200);
        $taxes = $this->faker->numberBetween(1, 200);
        $total = $this->faker->numberBetween(1, 200);
        $shippingTotal = $this->faker->numberBetween(1, 200);
        $totalLabel = $this->faker->word;
        $shippingLabel = $this->faker->word;
        $paymentDetails = [
            'subtotal' => $subtotal,
            'shipping' => [
                'amount' =>  $shippingTotal,
                'label' =>  $shippingLabel
            ],
            'shippingMethods' => null,
            'taxes' => $taxes,
            'total' => $total
        ];
        $expectedResponse = [
            'newLineItems'=>[
                [
                    "label" => 'Subtotal',
                    "amount" => $subtotal,
                    "type" => 'final'
                ],
                [
                    "label" => $shippingLabel,
                    "amount" => $shippingTotal,
                    "type" => 'final'
                ],
                [
                    "label" => 'Estimated Tax',
                    "amount" => $taxes,
                    "type" => 'final'
                ]
            ],
            'newTotal'=>[
                "label" => $totalLabel,
                "amount" => $total,
                "type" => 'final'
            ]

        ];

        expect('get_bloginfo')
            ->once()
            ->with('name')
            ->andReturn($totalLabel);
        /*
         * Sut
         */
        $logger = $this->helperMocks->loggerMock();
        $appleGateway = $this->liquichainGateway('applepay', false, true);
        $responsesTemplate = new ResponsesToApple($logger, $appleGateway);
        $response = $responsesTemplate->appleFormattedResponse($paymentDetails);

        self::assertEquals($response, $expectedResponse);
    }

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        when('__')->returnArg(1);
    }

    public function liquichainGateway($paymentMethodName, $isSepa = false, $isSubscription = false){
        $gateway = $this->createConfiguredMock(
            LiquichainSubscriptionGateway::class,
            []
        );
        $gateway->paymentMethod = $this->helperMocks->paymentMethodBuilder($paymentMethodName, $isSepa, $isSubscription);

        return $gateway;
    }
}
