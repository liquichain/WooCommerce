<?php

# -*- coding: utf-8 -*-

declare(strict_types=1);

namespace Liquichain\WooCommerce\Gateway;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Liquichain\WooCommerce\BlockService\CheckoutBlockService;
use Liquichain\WooCommerce\Buttons\ApplePayButton\AppleAjaxRequests;
use Liquichain\WooCommerce\Buttons\ApplePayButton\ApplePayDirectHandler;
use Liquichain\WooCommerce\Buttons\ApplePayButton\ResponsesToApple;
use Liquichain\WooCommerce\Buttons\PayPalButton\DataToPayPal;
use Liquichain\WooCommerce\Buttons\PayPalButton\PayPalAjaxRequests;
use Liquichain\WooCommerce\Buttons\PayPalButton\PayPalButtonHandler;
use Liquichain\WooCommerce\Gateway\Voucher\MaybeDisableGateway;
use Liquichain\WooCommerce\Notice\AdminNotice;
use Liquichain\WooCommerce\Notice\NoticeInterface;
use Liquichain\WooCommerce\Payment\LiquichainObject;
use Liquichain\WooCommerce\Payment\LiquichainOrderService;
use Liquichain\WooCommerce\Payment\OrderInstructionsService;
use Liquichain\WooCommerce\Payment\PaymentCheckoutRedirectService;
use Liquichain\WooCommerce\Payment\PaymentFactory;
use Liquichain\WooCommerce\Payment\PaymentFieldsService;
use Liquichain\WooCommerce\Payment\PaymentService;
use Liquichain\WooCommerce\PaymentMethods\IconFactory;
use Liquichain\WooCommerce\SDK\Api;
use Liquichain\WooCommerce\Settings\Settings;
use Liquichain\WooCommerce\Shared\Data;
use Liquichain\WooCommerce\Shared\GatewaySurchargeHandler;
use Liquichain\WooCommerce\Shared\SharedDataDictionary;
use Liquichain\WooCommerce\Subscription\LiquichainSepaRecurringGateway;
use Liquichain\WooCommerce\Subscription\LiquichainSubscriptionGateway;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface as Logger;

class GatewayModule implements ServiceModule, ExecutableModule
{
    use ModuleClassNameIdTrait;

    public const APPLE_PAY_METHOD_ALLOWED_KEY = 'liquichain_apple_pay_method_allowed';
    public const POST_DATA_KEY = 'post_data';
    /**
     * @var mixed
     */
    protected $gatewayClassnames;
    /**
     * @var mixed
     */
    protected $pluginId;

    public function services(): array
    {
        return [
            'gateway.classnames' => static function (): array {
                return SharedDataDictionary::GATEWAY_CLASSNAMES;
            },
            'gateway.instances' => function (ContainerInterface $container): array {
                return $this->instantiatePaymentMethodGateways($container);
            },
            'gateway.paymentMethods' => static function (ContainerInterface $container): array {
                return (new self)->instantiatePaymentMethods($container);
            },
            'gateway.paymentMethodsEnabledAtLiquichain' => static function (ContainerInterface $container): array {
                /* @var Data $dataHelper */
                $dataHelper = $container->get('settings.data_helper');
                /* @var Settings $settings */
                $settings = $container->get('settings.settings_helper');
                $apiKey = $settings->getApiKey();
                $methods = $apiKey? $dataHelper->getAllPaymentMethods($apiKey):[];
                foreach ($methods as $key => $method){
                    $methods[$method['id']] = $method;
                    unset($methods[$key]);
                }
                return $methods;
            },
            IconFactory::class => static function (ContainerInterface $container): IconFactory {
                $pluginUrl = $container->get('shared.plugin_url');
                $pluginPath = $container->get('shared.plugin_path');
                return new IconFactory($pluginUrl, $pluginPath);
            },
            PaymentService::class => static function (ContainerInterface $container): PaymentService {
                $logger = $container->get(Logger::class);
                $notice = $container->get(AdminNotice::class);
                $paymentFactory = $container->get(PaymentFactory::class);
                $data = $container->get('settings.data_helper');
                $api = $container->get('SDK.api_helper');
                $settings = $container->get('settings.settings_helper');
                $pluginId = $container->get('shared.plugin_id');
                $paymentCheckoutRedirectService = $container->get(PaymentCheckoutRedirectService::class);
                return new PaymentService($notice, $logger, $paymentFactory, $data, $api, $settings, $pluginId, $paymentCheckoutRedirectService);
            },
            OrderInstructionsService::class => static function (): OrderInstructionsService {
                return new OrderInstructionsService();
            },
            PaymentFieldsService::class => static function (ContainerInterface $container): PaymentFieldsService {
                $data = $container->get('settings.data_helper');
                return new PaymentFieldsService($data);
            },
            PaymentCheckoutRedirectService::class => static function (
                ContainerInterface $container
            ): PaymentCheckoutRedirectService {
                $data = $container->get('settings.data_helper');
                return new PaymentCheckoutRedirectService($data);
            },
            Surcharge::class => static function (ContainerInterface $container): Surcharge {
                return new Surcharge();
            },
            LiquichainOrderService::class => static function (ContainerInterface $container): LiquichainOrderService {
                $HttpResponseService = $container->get('SDK.HttpResponse');
                $logger = $container->get(Logger::class);
                $paymentFactory = $container->get(PaymentFactory::class);
                $data = $container->get('settings.data_helper');
                $pluginId = $container->get('shared.plugin_id');
                return new LiquichainOrderService($HttpResponseService, $logger, $paymentFactory, $data, $pluginId);
            },
        ];
    }

    public function run(ContainerInterface $container): bool
    {
        $this->pluginId = $container->get('shared.plugin_id');
        $this->gatewayClassnames = $container->get('gateway.classnames');
        add_filter($this->pluginId . '_retrieve_payment_gateways', function () {
            return $this->gatewayClassnames;
        });

        add_filter('woocommerce_payment_gateways', function ($gateways) use ($container) {
            $liquichainGateways = $container->get('gateway.instances');
            return array_merge($gateways, $liquichainGateways);
        });
        add_filter('woocommerce_payment_gateways', [$this, 'maybeDisableApplePayGateway'], 20);
         add_filter('woocommerce_payment_gateways', static function ($gateways) {
            $maybeEnablegatewayHelper = new MaybeDisableGateway();

            return $maybeEnablegatewayHelper->maybeDisableMealVoucherGateway($gateways);
         });
        add_filter(
            'woocommerce_payment_gateways',
            [$this, 'maybeDisableBankTransferGateway'],
            20
        );
        // Disable SEPA as payment option in WooCommerce checkout
        add_filter(
            'woocommerce_available_payment_gateways',
            [$this, 'disableSEPAInCheckout'],
            11,
            1
        );

        // Disable Liquichain methods on some pages
        add_filter(
            'woocommerce_available_payment_gateways',
            [$this, 'disableLiquichainOnPaymentMethodChange'],
            11,
            1
        );
        add_action(
            'woocommerce_after_order_object_save',
            static function () {
                $liquichainWooCommerceSession = liquichainWooCommerceSession();
                if ($liquichainWooCommerceSession instanceof \WC_Session) {
                    $liquichainWooCommerceSession->__unset(self::APPLE_PAY_METHOD_ALLOWED_KEY);
                }
            }
        );

        // Set order to paid and processed when eventually completed without Liquichain
        add_action('woocommerce_payment_complete', [$this, 'setOrderPaidByOtherGateway'], 10, 1);
        $appleGateway = isset($container->get('gateway.instances')['liquichain_wc_gateway_applepay'])? $container->get('gateway.instances')['liquichain_wc_gateway_applepay']:false;

        $notice = $container->get(AdminNotice::class);
        $logger = $container->get(Logger::class);
        $pluginUrl = $container->get('shared.plugin_url');
        $apiHelper = $container->get('SDK.api_helper');
        $settingsHelper = $container->get('settings.settings_helper');
        $this->gatewaySurchargeHandling($container->get(Surcharge::class));
        if($appleGateway){
            $this->liquichainApplePayDirectHandling($notice, $logger, $apiHelper, $settingsHelper, $appleGateway);
        }

        $paypalGateway = isset($container->get('gateway.instances')['liquichain_wc_gateway_paypal'])? $container->get('gateway.instances')['liquichain_wc_gateway_paypal']:false;
        if ($paypalGateway){
            $this->liquichainPayPalButtonHandling($paypalGateway, $notice, $logger, $pluginUrl);
        }

        $maybeDisableVoucher = new MaybeDisableGateway();
        $checkoutBlockHandler = new CheckoutBlockService($container->get('settings.data_helper'), $maybeDisableVoucher);
        $checkoutBlockHandler->bootstrapAjaxRequest();
        add_action( 'woocommerce_rest_checkout_process_payment_with_context', function($paymentContext){
            if(strpos($paymentContext->payment_method, 'liquichain_wc_gateway_') === false){
                return;
            }
            $title = isset($paymentContext->payment_data['payment_method_title'])?$paymentContext->payment_data['payment_method_title']:false;
            if(!$title){
                return ;
            }
            $order = $paymentContext->order;
            $order->set_payment_method_title( $title );
            $order->save();
        } );

        return true;
    }

    /**
     * Disable Bank Transfer Gateway
     *
     * @param array $gateways
     * @return array
     */
    public function maybeDisableBankTransferGateway(array $gateways): array
    {
        $isWcApiRequest = (bool)filter_input(INPUT_GET, 'wc-api', FILTER_SANITIZE_STRING);
        $bankTransferSettings = get_option('liquichain_wc_gateway_banktransfer_settings', false);
        $isSettingActivated = false;
        if ($bankTransferSettings && isset($bankTransferSettings['order_dueDate'])) {
            $isSettingActivated = $bankTransferSettings['order_dueDate'] > 0;
        }

        /*
         * There is only one case where we want to filter the gateway and it's when the
         * pay-page render the available payments methods AND the setting is enabled
         *
         * For any other case we want to be sure bank transfer gateway is included.
         */
        if (
            $isWcApiRequest ||
            !$isSettingActivated ||
            is_checkout() && ! is_wc_endpoint_url('order-pay') ||
            !wp_doing_ajax() && ! is_wc_endpoint_url('order-pay') ||
            is_admin()
        ) {
            return $gateways;
        }
        $bankTransferGatewayClassName = 'liquichain_wc_gateway_banktransfer';
        unset($gateways[$bankTransferGatewayClassName]);

        return  $gateways;
    }

    /**
     * Disable Apple Pay Gateway
     *
     * @param array $gateways
     * @return array
     */
    public function maybeDisableApplePayGateway(array $gateways): array
    {
        $isWcApiRequest = (bool)filter_input(INPUT_GET, 'wc-api', FILTER_SANITIZE_STRING);
        $wooCommerceSession = liquichainWooCommerceSession();

        /*
         * There is only one case where we want to filter the gateway and it's when the checkout
         * page render the available payments methods.
         *
         * For any other case we want to be sure apple pay gateway is included.
         */
        if (
            $isWcApiRequest ||
            !$wooCommerceSession instanceof \WC_Session ||
            !doing_action('woocommerce_payment_gateways') ||
            !wp_doing_ajax() && ! is_wc_endpoint_url('order-pay') ||
            is_admin()
        ) {
            return $gateways;
        }

        if ($wooCommerceSession->get(self::APPLE_PAY_METHOD_ALLOWED_KEY, false)) {
            return $gateways;
        }

        $applePayGatewayClassName = 'liquichain_wc_gateway_applepay';
        $postData = (string)filter_input(
            INPUT_POST,
            self::POST_DATA_KEY,
            FILTER_SANITIZE_STRING
        ) ?: '';
        parse_str($postData, $postData);

        $applePayAllowed = isset($postData[self::APPLE_PAY_METHOD_ALLOWED_KEY])
            && $postData[self::APPLE_PAY_METHOD_ALLOWED_KEY];

        if (!$applePayAllowed) {
            unset($gateways[$applePayGatewayClassName]);
        }

        if ($applePayAllowed) {
            $wooCommerceSession->set(self::APPLE_PAY_METHOD_ALLOWED_KEY, true);
        }

        return $gateways;
    }

    public function gatewaySurchargeHandling(Surcharge $surcharge)
    {
        new GatewaySurchargeHandler($surcharge);
    }

    /**
     * Don't show SEPA Direct Debit in WooCommerce Checkout
     */
    public function disableSEPAInCheckout($available_gateways)
    {
        if (is_checkout()) {
            unset($available_gateways['liquichain_wc_gateway_directdebit']);
        }

        return $available_gateways;
    }

    /**
     * Don't show Liquichain Payment Methods in WooCommerce Account > Subscriptions
     */
    public function disableLiquichainOnPaymentMethodChange($available_gateways)
    {
        // Can't use $wp->request or is_wc_endpoint_url()
        // to check if this code only runs on /subscriptions and /view-subscriptions,
        // because slugs/endpoints can be translated (with WPML) and other plugins.
        // So disabling on is_account_page (if not checkout, bug in WC) and $_GET['change_payment_method'] for now.

        // Only disable payment methods if WooCommerce Subscriptions is installed
        if (class_exists('WC_Subscription')) {
            // Do not disable if account page is also checkout
            // (workaround for bug in WC), do disable on change payment method page (param)
            if ((! is_checkout() && is_account_page()) || ! empty($_GET['change_payment_method'])) {
                foreach ($available_gateways as $key => $value) {
                    if (strpos($key, 'liquichain_') !== false) {
                        unset($available_gateways[ $key ]);
                    }
                }
            }
        }

        return $available_gateways;
    }

    /**
     * If an order is paid with another payment method (gateway) after a first payment was
     * placed with Liquichain, set a flag, so status updates (like expired) aren't processed by
     * Liquichain Payments for WooCommerce.
     */
    public function setOrderPaidByOtherGateway($order_id)
    {
        $order = wc_get_order($order_id);

        $liquichain_payment_id = $order->get_meta('_liquichain_payment_id', $single = true);
        $order_payment_method = $order->get_payment_method();

        if ($liquichain_payment_id !== '' && (strpos($order_payment_method, 'liquichain') === false)) {
            $order->update_meta_data('_liquichain_paid_by_other_gateway', '1');
            $order->save();
        }
        return true;
    }

    /**
     * Bootstrap the ApplePay button logic if feature enabled
     */
    public function liquichainApplePayDirectHandling(NoticeInterface $notice, Logger $logger, Api $apiHelper, Settings $settingsHelper, LiquichainSubscriptionGateway $appleGateway)
    {
        $buttonEnabledCart = liquichainWooCommerceIsApplePayDirectEnabled('cart');
        $buttonEnabledProduct = liquichainWooCommerceIsApplePayDirectEnabled('product');

        if ($buttonEnabledCart || $buttonEnabledProduct) {
            $notices = new AdminNotice();
            $responseTemplates = new ResponsesToApple($logger, $appleGateway);
            $ajaxRequests = new AppleAjaxRequests($responseTemplates, $notice, $logger, $apiHelper, $settingsHelper);
            $applePayHandler = new ApplePayDirectHandler($notices, $ajaxRequests);
            $applePayHandler->bootstrap($buttonEnabledProduct, $buttonEnabledCart);
        }
    }

    /**
     * Bootstrap the Liquichain_WC_Gateway_PayPal button logic if feature enabled
     */
    public function liquichainPayPalButtonHandling(
        $gateway,
        NoticeInterface $notice,
        Logger $logger,
        string $pluginUrl
    ) {

        $enabledInProduct = (liquichainWooCommerceIsPayPalButtonEnabled('product'));
        $enabledInCart = (liquichainWooCommerceIsPayPalButtonEnabled('cart'));
        $shouldBuildIt = $enabledInProduct || $enabledInCart;

        if ($shouldBuildIt) {
            $ajaxRequests = new PayPalAjaxRequests($gateway, $notice, $logger);
            $data = new DataToPayPal($pluginUrl);
            $payPalHandler = new PayPalButtonHandler($ajaxRequests, $data);
            $payPalHandler->bootstrap($enabledInProduct, $enabledInCart);
        }
    }

    public function instantiatePaymentMethodGateways(ContainerInterface $container): array
    {
        $logger = $container->get(Logger::class);
        $notice = $container->get(AdminNotice::class);
        $paymentService = $container->get(PaymentService::class);
        $liquichainOrderService = $container->get(LiquichainOrderService::class);
        $HttpResponseService = $container->get('SDK.HttpResponse');
        $settingsHelper = $container->get('settings.settings_helper');
        $apiHelper = $container->get('SDK.api_helper');
        $paymentMethods = $container->get('gateway.paymentMethods');
        $data = $container->get('settings.data_helper');
        $orderInstructionsService = new OrderInstructionsService();
        $liquichainObject = $container->get(LiquichainObject::class);
        $paymentFactory = $container->get(PaymentFactory::class);
        $pluginId = $container->get('shared.plugin_id');
        $methodsEnabledAtLiquichain = $container->get('gateway.paymentMethodsEnabledAtLiquichain');
        $gateways = [];
        if(empty($methodsEnabledAtLiquichain)){
            return $gateways;
        }

        foreach ($paymentMethods as $paymentMethod) {
            $paymentMethodId = $paymentMethod->getProperty('id');
            if(!$this->paymentMethodEnabledAtLiquichain($paymentMethodId, $methodsEnabledAtLiquichain)){
                continue;
            }

            $isSepa = $paymentMethod->getProperty('SEPA');
            $key = 'liquichain_wc_gateway_' . $paymentMethodId;
            if ($isSepa) {
                $directDebit = $paymentMethods['directdebit'];
                $gateways[$key] = new LiquichainSepaRecurringGateway(
                    $directDebit,
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
                );
            } elseif ($paymentMethod->getProperty('Subscription')) {
                $gateways[$key] = new LiquichainSubscriptionGateway(
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
                );
            } else {
                $gateways[$key] = new LiquichainPaymentGateway(
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
                );
            }
        }
        return $gateways;
    }

    private function paymentMethodEnabledAtLiquichain($paymentMethodName, $methodsEnabledAtLiquichain)
    {
        return array_key_exists(strtolower($paymentMethodName), $methodsEnabledAtLiquichain);
    }

    /**
     * @param $container
     * @return array
     */
    protected function instantiatePaymentMethods($container): array
    {
        $paymentMethods = [];
        $paymentMethodsNames = [
            'Banktransfer',
            'Belfius',
            'Creditcard',
            'Directdebit',
            'Eps',
            'Giropay',
            'Ideal',
            'Kbc',
            'Klarnapaylater',
            'Klarnapaynow',
            'Klarnasliceit',
            'Bancontact',
            'Paypal',
            'Paysafecard',
            'Przelewy24',
            'Sofort',
            'Giftcard',
            'Applepay',
            'Mybank',
            'Voucher',
        ];
        $iconFactory = $container->get(IconFactory::class);
        $settingsHelper = $container->get('settings.settings_helper');
        $surchargeService = $container->get(Surcharge::class);
        $paymentFieldsService = $container->get(PaymentFieldsService::class);
        foreach ($paymentMethodsNames as $paymentMethodName) {
            $paymentMethodClassName = 'Liquichain\\WooCommerce\\PaymentMethods\\' . $paymentMethodName;
            $paymentMethod = new $paymentMethodClassName(
                $iconFactory,
                $settingsHelper,
                $paymentFieldsService,
                $surchargeService
            );
            $paymentMethodId = $paymentMethod->getProperty('id');
            $paymentMethods[$paymentMethodId] = $paymentMethod;
        }

        return $paymentMethods;
    }
}
