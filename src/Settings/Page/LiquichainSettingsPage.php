<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\Settings\Page;

use Liquichain\WooCommerce\Settings\Settings;
use Liquichain\WooCommerce\Shared\Data;
use WC_Admin_Settings;
use WC_Gateway_BACS;
use WC_Settings_Page;

class LiquichainSettingsPage extends WC_Settings_Page
{
    public const FILTER_COMPONENTS_SETTINGS = 'liquichain_settings';
    protected $settingsHelper;

    /**
     * @var string
     */
    protected $pluginPath;
    /**
     * @var array
     */
    protected $registeredGateways;
    /**
     * @var bool
     */
    protected $isTestModeEnabled;
    /**
     * @var Data
     */
    protected $dataHelper;
    /**
     * @var array
     */
    protected $paymentMethods;

    public function __construct(
        Settings $settingsHelper,
        string $pluginPath,
        array $gateways,
        array $paymentMethods,
        bool $isTestModeEnabled,
        Data $dataHelper
    ) {

        $this->id = 'liquichain_settings';
        $this->label = __('Liquichain Settings', 'liquichain-payments-for-woocommerce');
        $this->settingsHelper = $settingsHelper;
        $this->pluginPath = $pluginPath;
        $this->registeredGateways = $gateways;
        $this->isTestModeEnabled = $isTestModeEnabled;
        $this->dataHelper = $dataHelper;
        $this->paymentMethods = $paymentMethods;
        add_action(
            'woocommerce_sections_' . $this->id,
            [$this, 'output_sections']
        );
        parent::__construct();
    }

    public function output()
    {
        global $current_section;
        $settings = $this->get_settings($current_section);
        $settings = $this->hideKeysIntoStars($settings);

        WC_Admin_Settings::output_fields($settings);
    }

    public function get_settings($currentSection = '')
    {
        $liquichainSettings = $this->addGlobalSettingsFields([]);

        if ('liquichain_components' === $currentSection) {
            $liquichainSettings = $this->sectionSettings(
                $this->componentsFilePath()
            );
        }
        if ('applepay_button' === $currentSection) {
            $liquichainSettings = $this->sectionSettings($this->applePaySection());
        }
        if ('advanced' === $currentSection) {
            $liquichainSettings = $this->sectionSettings($this->advancedSectionFilePath());
        }

        /**
         * Filter Component Settings
         *
         * @param array $componentSettings Default components settings for the Credit Card Gateway
         */
        $liquichainSettings = apply_filters(
            self::FILTER_COMPONENTS_SETTINGS,
            $liquichainSettings
        );

        return apply_filters(
            'woocommerce_get_settings_' . $this->id,
            $liquichainSettings,
            $currentSection
        );
    }

    /**
     * @param array $settings
     * @return array
     */
    public function addGlobalSettingsFields(array $settings): array
    {
        $presentationText = __(
            'Quickly integrate all major payment methods in WooCommerce, wherever you need them.',
            'liquichain-payments-for-woocommerce'
        );
        $presentationText .= __(
            ' Simply drop them ready-made into your WooCommerce webshop with this powerful plugin by Liquichain.',
            'liquichain-payments-for-woocommerce'
        );
        $presentationText .= __(
            ' Liquichain is dedicated to making payments better for WooCommerce. ',
            'liquichain-payments-for-woocommerce'
        );
        $presentationText .= '<p>' . __(
            'Please go to',
            'liquichain-payments-for-woocommerce'
        ) . '<a href="https://www.liquichain.io/dashboard/signup">' . __(
            ' the signup page',
            'liquichain-payments-for-woocommerce'
        ) . '</a> ';
        $presentationText .= __(
            'to create a new Liquichain account and start receiving payments in a couple of minutes. ',
            'liquichain-payments-for-woocommerce'
        );
        $presentationText .= __(
            'Contact ',
            'liquichain-payments-for-woocommerce'
        ) . '<a href="mailto:info@liquichain.io">info@liquichain.io</a>';
        $presentationText .= __(
            ' if you have any questions or comments about this plugin.',
            'liquichain-payments-for-woocommerce'
        ) . '</p>';
        $presentationText .= '<p style="border-left: 4px solid black; padding: 8px; height:32px; font-weight:bold; font-size: medium;">' . __(
            'Our pricing is always per transaction. No startup fees, no monthly fees, and no gateway fees. No hidden fees, period.',
            'liquichain-payments-for-woocommerce'
        ) . '</p>';

        $presentation = ''
            . '<div style="width:1000px"><div style="font-weight:bold;"><a href="https://github.com/liquichain/WooCommerce/wiki">' . __(
                'Plugin Documentation',
                'liquichain-payments-for-woocommerce'
            ) . '</a> | <a href="https://liquichain.inpsyde.com/docs/how-to-request-support-via-website-widget/">' . __(
                'Contact Support',
                'liquichain-payments-for-woocommerce'
            ) . '</a></div></div>'
            . '<span></span>'
            . '<div id="" class="" style="width: 1000px; padding:5px 0 0 10px"><p>' . $presentationText . '</p></div>';

        $content = ''
            . $presentation
            . $this->settingsHelper->getPluginStatus()
            . $this->getLiquichainMethods();

        $debugDesc = __('Log plugin events.', 'liquichain-payments-for-woocommerce');

        // Display location of log files


        $debugDesc .= ' ' . sprintf(
            /* translators: Placeholder 1: Location of the log files */
            __(
                'Log files are saved to <code>%s</code>',
                'liquichain-payments-for-woocommerce'
            ),
            defined('WC_LOG_DIR') ? WC_LOG_DIR
                    : WC()->plugin_path() . '/logs/'
        );

        // Global Liquichain settings
        $liquichainSettings = [
            [
                'id' => $this->settingsHelper->getSettingId('title'),
                'title' => __('Liquichain Settings', 'liquichain-payments-for-woocommerce'),
                'type' => 'title',
                'desc' => '<p id="' . $this->settingsHelper->pluginId . '">' . $content . '</p>'
                    . '<p>' . __(
                        'The following options are required to use the plugin and are used by all Liquichain payment methods',
                        'liquichain-payments-for-woocommerce'
                    ) . '</p>',
            ],
            [
                'id' => $this->settingsHelper->getSettingId('live_api_key'),
                'title' => __('Live API key', 'liquichain-payments-for-woocommerce'),
                'default' => '',
                'type' => 'text',
                'desc' => sprintf(
                /* translators: Placeholder 1: API key mode (live or test). The surrounding %s's Will be replaced by a link to the Liquichain profile */
                    __(
                        'The API key is used to connect to Liquichain. You can find your <strong>%1$s</strong> API key in your %2$sLiquichain profile%3$s',
                        'liquichain-payments-for-woocommerce'
                    ),
                    'live',
                    '<a href="https://www.liquichain.io/dashboard/settings/profiles" target="_blank">',
                    '</a>'
                ),
                'css' => 'width: 350px',
                'placeholder' => __(
                    'Live API key should start with live_',
                    'liquichain-payments-for-woocommerce'
                ),
            ],
            [
                'id' => $this->settingsHelper->getSettingId('test_mode_enabled'),
                'title' => __('Enable test mode', 'liquichain-payments-for-woocommerce'),
                'default' => 'no',
                'type' => 'checkbox',
                'desc_tip' => __(
                    'Enable test mode if you want to test the plugin without using real payments.',
                    'liquichain-payments-for-woocommerce'
                ),
            ],
            [
                'id' => $this->settingsHelper->getSettingId('test_api_key'),
                'title' => __('Test API key', 'liquichain-payments-for-woocommerce'),
                'default' => '',
                'type' => 'text',
                'desc' => sprintf(
                /* translators: Placeholder 1: API key mode (live or test). The surrounding %s's Will be replaced by a link to the Liquichain profile */
                    __(
                        'The API key is used to connect to Liquichain. You can find your <strong>%1$s</strong> API key in your %2$sLiquichain profile%3$s',
                        'liquichain-payments-for-woocommerce'
                    ),
                    'test',
                    '<a href="https://www.liquichain.io/dashboard/settings/profiles" target="_blank">',
                    '</a>'
                ),
                'css' => 'width: 350px',
                'placeholder' => __(
                    'Test API key should start with test_',
                    'liquichain-payments-for-woocommerce'
                ),
            ],
            [
                'id' => $this->settingsHelper->getSettingId('debug'),
                'title' => __('Debug Log', 'liquichain-payments-for-woocommerce'),
                'type' => 'checkbox',
                'desc' => $debugDesc,
                'default' => 'yes',
            ],
            [
                'id' => $this->settingsHelper->getSettingId('sectionend'),
                'type' => 'sectionend',
            ],
        ];

        return $this->mergeSettings($settings, $liquichainSettings);
    }

    public function getLiquichainMethods()
    {
        $content = '';

        $dataHelper = $this->dataHelper;

        // Is Test mode enabled?
        $testMode = $this->isTestModeEnabled;
        $apiKey = $this->settingsHelper->getApiKey();

        if (
            isset($_GET['refresh-methods']) && wp_verify_nonce(
                $_GET['nonce_liquichain_refresh_methods'],
                'nonce_liquichain_refresh_methods'
            )
        ) {
            /* Reload active Liquichain methods */
            $methods = $dataHelper->getAllPaymentMethods($apiKey, $testMode, false);
            foreach ($methods as $key => $method){
                $methods['liquichain_wc_gateway_'.$method['id']] = $method;
                unset($methods[$key]);
            }
            $this->registeredGateways = $methods;
        }
        if (
            isset($_GET['cleanDB-liquichain']) && wp_verify_nonce(
                $_GET['nonce_liquichain_cleanDb'],
                'nonce_liquichain_cleanDb'
            )
        ) {
            $cleaner = $this->settingsHelper->cleanDb();
            $cleaner->cleanAll();
        }

        $iconAvailable = ' <span style="color: green; cursor: help;" title="' . __(
            'Gateway enabled',
            'liquichain-payments-for-woocommerce'
        ) . '">' . strtolower(__('Enabled', 'liquichain-payments-for-woocommerce')) . '</span>';
        $iconNoAvailable = ' <span style="color: red; cursor: help;" title="' . __(
            'Gateway disabled',
            'liquichain-payments-for-woocommerce'
        ) . '">' . strtolower(__('Disabled', 'liquichain-payments-for-woocommerce')) . '</span>';

        $content .= '<br /><br />';
        $content .= '<div style="width:1000px;height:350px; background:white; padding:10px; margin-top:10px;">';

        if ($testMode) {
            $content .= '<strong>' . __('Test mode enabled.', 'liquichain-payments-for-woocommerce') . '</strong> ';
        }

        $content .= sprintf(
        /* translators: The surrounding %s's Will be replaced by a link to the Liquichain profile */
            __(
                'The following payment methods are activated in your %1$sLiquichain profile%2$s:',
                'liquichain-payments-for-woocommerce'
            ),
            '<a href="https://www.liquichain.io/dashboard/settings/profiles" target="_blank">',
            '</a>'
        );

        // Set a "refresh" link so payment method status can be refreshed from Liquichain API
        $nonce_liquichain_refresh_methods = wp_create_nonce('nonce_liquichain_refresh_methods');
        $refresh_methods_url = add_query_arg(
            ['refresh-methods' => 1, 'nonce_liquichain_refresh_methods' => $nonce_liquichain_refresh_methods]
        );

        $content .= ' (<a href="' . esc_attr($refresh_methods_url) . '">' . strtolower(
            __('Refresh', 'liquichain-payments-for-woocommerce')
        ) . '</a>)';

        $content .= '<ul style="width: 1000px; padding:20px 0 0 10px">';

        $liquichainGateways = $this->registeredGateways;//this are the gateways enabled
        $paymentMethods = $this->paymentMethods;
        foreach ($paymentMethods as $paymentMethod) {
            $paymentMethodId = $paymentMethod->getProperty('id');
            $gatewayKey = 'liquichain_wc_gateway_' . $paymentMethodId;
            $paymentMethodEnabledAtLiquichain = array_key_exists($gatewayKey , $liquichainGateways);
            $content .= '<li style="float: left; width: 32%; height:32px;">';
            $content .= $paymentMethod->getIconUrl();
            $content .= ' ' . esc_html($paymentMethod->getProperty('defaultTitle'));
            if ($paymentMethodEnabledAtLiquichain) {
                $content .= $iconAvailable;
                $content .= ' <a href="' . $this->getGatewaySettingsUrl($gatewayKey) . '">' . strtolower(
                    __('Edit', 'liquichain-payments-for-woocommerce')
                ) . '</a>';

                $content .= '</li>';
                continue;
            }
            $content .= $iconNoAvailable;
            $content .= ' <a href="https://www.liquichain.io/dashboard/settings/profiles" target="_blank">' . strtolower(
                    __('Activate', 'liquichain-payments-for-woocommerce')
                ) . '</a>';

            $content .= '</li>';
        }

        $content .= '</ul></div>';
        $content .= '<div class="clear"></div>';

        // Make sure users also enable iDEAL when they enable SEPA Direct Debit
        // iDEAL is needed for the first payment of subscriptions with SEPA Direct Debit
        $content = $this->checkDirectDebitStatus($content);

        // Advice users to use bank transfer via Liquichain, not
        // WooCommerce default BACS method
        $content = $this->checkLiquichainBankTransferNotBACS($content);

        // Warn users that all default WooCommerce checkout fields
        // are required to accept Klarna as payment method
        $content = $this->warnAboutRequiredCheckoutFieldForKlarna($content);

        return $content;
    }

    /**
     * @param string $gateway_class_name
     * @return string
     */
    protected function getGatewaySettingsUrl($gateway_class_name): string
    {
        return admin_url(
            'admin.php?page=wc-settings&tab=checkout&section=' . sanitize_title(strtolower($gateway_class_name))
        );
    }

    /**
     * @param $content
     *
     * @return string
     */
    protected function checkDirectDebitStatus($content): string
    {
        $idealGateway = !empty($this->registeredGateways["liquichain_wc_gateway_ideal"]) && $this->paymentMethods["ideal"]->getProperty('enabled') === 'yes';
        $sepaGateway = !empty($this->registeredGateways["liquichain_wc_gateway_directdebit"]) && $this->paymentMethods["directdebit"]->getProperty('enabled') === 'yes';

        if ((class_exists('WC_Subscription')) && $idealGateway && !$sepaGateway) {
            $warning_message = __(
                'You have WooCommerce Subscriptions activated, but not SEPA Direct Debit. Enable SEPA Direct Debit if you want to allow customers to pay subscriptions with iDEAL and/or other "first" payment methods.',
                'liquichain-payments-for-woocommerce'
            );

            $content .= '<div class="notice notice-warning is-dismissible"><p>';
            $content .= $warning_message;
            $content .= '</p></div> ';

            return $content;
        }

        return $content;
    }

    /**
     * @param $content
     *
     * @return string
     */
    protected function checkLiquichainBankTransferNotBACS($content)
    {
        $woocommerce_banktransfer_gateway = new WC_Gateway_BACS();

        if ($woocommerce_banktransfer_gateway->is_available()) {
            $content .= '<div class="notice notice-warning is-dismissible"><p>';
            $content .= __(
                'You have the WooCommerce default Direct Bank Transfer (BACS) payment gateway enabled in WooCommerce. Liquichain strongly advices only using Bank Transfer via Liquichain and disabling the default WooCommerce BACS payment gateway to prevent possible conflicts.',
                'liquichain-payments-for-woocommerce'
            );
            $content .= '</p></div> ';

            return $content;
        }

        return $content;
    }

    /**
     * @param $content
     *
     * @return string
     */
    protected function warnAboutRequiredCheckoutFieldForKlarna($content)
    {
        $woocommerceKlarnapaylaterGateway = !empty($this->registeredGateways["liquichain_wc_gateway_klarnapaylater"]) && $this->paymentMethods["klarnapaylater"]->getProperty('enabled') === 'yes';
        $woocommerceKlarnasliceitGateway = !empty($this->registeredGateways["liquichain_wc_gateway_klarnasliceit"]) && $this->paymentMethods["klarnasliceit"]->getProperty('enabled') === 'yes';
        $woocommerceKlarnapaynowGateway = !empty($this->registeredGateways["liquichain_wc_gateway_klarnapaynow"]) && $this->paymentMethods["klarnapaynow"]->getProperty('enabled') === 'yes';

        if (
            $woocommerceKlarnapaylaterGateway || $woocommerceKlarnasliceitGateway || $woocommerceKlarnapaynowGateway
        ) {
            $content .= '<div class="notice notice-warning is-dismissible"><p>';
            $content .= sprintf(
            /* translators: Placeholder 1: Opening link tag. Placeholder 2: Closing link tag. Placeholder 3: Opening link tag. Placeholder 4: Closing link tag. */
                __(
                    'You have activated Klarna. To accept payments, please make sure all default WooCommerce checkout fields are enabled and required. For more information, go to %1$1sKlarna Pay Later documentation%2$2s or  %3$3sKlarna Slice it documentation%4$4s',
                    'liquichain-payments-for-woocommerce'
                ),
                '<a href="https://github.com/liquichain/WooCommerce/wiki/Setting-up-Klarna-Pay-later-gateway">',
                '</a>',
                '<a href=" https://github.com/liquichain/WooCommerce/wiki/Setting-up-Klarna-Slice-it-gateway">',
                '</a>'
            );
            $content .= '</p></div> ';

            return $content;
        }

        return $content;
    }

    /**
     * @param array $settings
     * @param array $liquichain_settings
     * @return array
     */
    protected function mergeSettings(array $settings, array $liquichain_settings): array
    {
        $new_settings = [];
        $liquichain_settings_merged = false;

        // Find payment gateway options index
        foreach ($settings as $index => $setting) {
            if (
                isset($setting['id']) && $setting['id'] === 'payment_gateways_options'
                && (!isset($setting['type']) || $setting['type'] != 'sectionend')
            ) {
                $new_settings = array_merge($new_settings, $liquichain_settings);
                $liquichain_settings_merged = true;
            }

            $new_settings[] = $setting;
        }

        // Liquichain settings not merged yet, payment_gateways_options not found
        if (!$liquichain_settings_merged) {
            // Append Liquichain settings
            $new_settings = array_merge($new_settings, $liquichain_settings);
        }

        return $new_settings;
    }

    /**
     * @param $filePath
     *
     * @return array|mixed
     */
    protected function sectionSettings($filePath)
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $section = include $filePath;

        if (!is_array($section)) {
            $section = [];
        }

        return $section;
    }

    /**
     * @return string
     */
    protected function componentsFilePath()
    {
        return $this->pluginPath . '/inc/settings/liquichain_components.php';
    }

    /**
     * @return string
     */
    protected function applePaySection()
    {
        return $this->pluginPath . '/inc/settings/liquichain_applepay_settings.php';
    }

    /**
     * @return string
     */
    protected function advancedSectionFilePath()
    {
        return $this->pluginPath . '/inc/settings/liquichain_advanced_settings.php';
    }

    /**
     * @param $settings
     *
     * @return array
     */
    protected function hideKeysIntoStars($settings)
    {
        $liveKeyName = 'liquichain-payments-for-woocommerce_live_api_key';
        $testKeyName = 'liquichain-payments-for-woocommerce_test_api_key';
        $liveValue = get_option($liveKeyName);
        $testValue = get_option($testKeyName);

        foreach ($settings as $key => $setting) {
            if (
                ($setting['id']
                    === $liveKeyName
                    && $liveValue)
                || ($setting['id']
                    === $testKeyName
                    && $testValue)
            ) {
                $settings[$key]['value'] = '**********';
            }
        }
        return $settings;
    }

    /**
     * Save settings
     *
     * @since 1.0
     */
    public function save()
    {
        global $current_section;

        $settings = $this->get_settings($current_section);
        if ('applepay_button' === $current_section) {
            $this->saveApplePaySettings();
        } else {
            $settings = $this->saveApiKeys($settings);
            WC_Admin_Settings::save_fields($settings);
        }
    }

    protected function saveApplePaySettings()
    {
        $data = filter_var_array($_POST, FILTER_SANITIZE_STRING);

        $applepaySettings = [];
        isset($data['enabled']) && ($data['enabled'] === '1') ?
            $applepaySettings['enabled'] = 'yes'
            : $applepaySettings['enabled'] = 'no';
        isset($data['display_logo']) && ($data['display_logo'] === '1') ?
            $applepaySettings['display_logo'] = 'yes'
            : $applepaySettings['display_logo'] = 'no';
        isset($data['liquichain_apple_pay_button_enabled_cart'])
        && ($data['liquichain_apple_pay_button_enabled_cart'] === '1') ?
            $applepaySettings['liquichain_apple_pay_button_enabled_cart'] = 'yes'
            : $applepaySettings['liquichain_apple_pay_button_enabled_cart'] = 'no';
        isset($data['liquichain_apple_pay_button_enabled_product'])
        && ($data['liquichain_apple_pay_button_enabled_product'] === '1')
            ?
            $applepaySettings['liquichain_apple_pay_button_enabled_product'] = 'yes'
            :
            $applepaySettings['liquichain_apple_pay_button_enabled_product'] = 'no';
        isset($data['title']) ? $applepaySettings['title'] = $data['title']
            : $applepaySettings['title'] = '';
        isset($data['description']) ?
            $applepaySettings['description'] = $data['description']
            : $applepaySettings['description'] = '';
        update_option(
            'liquichain_wc_gateway_applepay_settings',
            $applepaySettings
        );
    }

    /**
     * @param $settings
     *
     * @return array
     */
    protected function saveApiKeys($settings)
    {
        $liveKeyName = 'liquichain-payments-for-woocommerce_live_api_key';
        $testKeyName = 'liquichain-payments-for-woocommerce_test_api_key';
        $liveValueInDb = get_option($liveKeyName);
        $testValueInDb = get_option($testKeyName);
        $postedLiveValue = isset($_POST[$liveKeyName]) ? sanitize_text_field($_POST[$liveKeyName]) : '';
        $postedTestValue = isset($_POST[$testKeyName]) ? sanitize_text_field($_POST[$testKeyName]) : '';

        foreach ($settings as $setting) {
            if (
                $setting['id']
                === $liveKeyName
                && $liveValueInDb
            ) {
                if ($postedLiveValue === '**********') {
                    $_POST[$liveKeyName] = $liveValueInDb;
                } else {
                    $pattern = '/^live_\w{30,}$/';
                    $this->validateApiKeyOrRemove(
                        $pattern,
                        $postedLiveValue,
                        $liveKeyName
                    );
                }
            } elseif (
                $setting['id']
                === $testKeyName
                && $testValueInDb
            ) {
                if ($postedTestValue === '**********') {
                    $_POST[$testKeyName] = $testValueInDb;
                } else {
                    $pattern = '/^test_\w{30,}$/';
                    $this->validateApiKeyOrRemove(
                        $pattern,
                        $postedTestValue,
                        $testKeyName
                    );
                }
            }
        }
        return $settings;
    }

    /**
     * @param       $pattern
     * @param       $value
     * @param       $keyName
     *
     */
    protected function validateApiKeyOrRemove($pattern, $value, $keyName)
    {
        $hasApiFormat = preg_match($pattern, $value);
        if (!$hasApiFormat) {
            unset($_POST[$keyName]);
        }
    }

    /**
     * @return array|mixed|void|null
     */
    public function get_sections()
    {
        $isAppleEnabled =array_key_exists('liquichain_wc_gateway_applepay', $this->registeredGateways);
        $sections = [
            '' => __('General', 'liquichain-payments-for-woocommerce'),
            'liquichain_components' => __(
                'Liquichain Components',
                'liquichain-payments-for-woocommerce'
            ),
            'advanced' => __('Advanced', 'liquichain-payments-for-woocommerce'),
        ];
        if($isAppleEnabled){
            $sections['applepay_button'] = __(
                'Apple Pay Button',
                'liquichain-payments-for-woocommerce'
            );
        }

        return apply_filters(
            'woocommerce_get_sections_' . $this->id,
            $sections
        );
    }
}
