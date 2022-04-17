<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\SDK;

use Liquichain\Api\LiquichainApiClient;

class Api
{
    /**
     * @var \Liquichain\Api\LiquichainApiClient
     */
    protected static $api_client;
    /**
     * @var string
     */
    protected $pluginVersion;
    /**
     * @var string
     */
    protected $pluginId;

    public function __construct(string $pluginVersion, string $pluginId)
    {
        $this->pluginVersion = $pluginVersion;
        $this->pluginId = $pluginId;
    }

    /**
     * @param bool $test_mode
     * @param bool $needToUpdateApiKey If the apiKey was updated discard the old instance, and create a new one with the new key.
     *
     * @return \Liquichain\Api\LiquichainApiClient
     * @throws \Liquichain\Api\Exceptions\ApiException
     */
    public function getApiClient($apiKey, $needToUpdateApiKey = false)
    {

        global $wp_version;

        if (has_filter('liquichain_api_key_filter')) {
            $apiKey = apply_filters('liquichain_api_key_filter', $apiKey);
        }

        if (empty($apiKey)) {
            throw new \Liquichain\Api\Exceptions\ApiException(__('No API key provided. Please set your Liquichain API keys below.', 'liquichain-payments-for-woocommerce'));
        } elseif (! preg_match('#^(live|test)_\w{30,}$#', $apiKey)) {
            throw new \Liquichain\Api\Exceptions\ApiException(sprintf(__("Invalid API key(s). Get them on the %1\$sDevelopers page in the Liquichain dashboard%2\$s. The API key(s) must start with 'live_' or 'test_', be at least 30 characters and must not contain any special characters.", 'liquichain-payments-for-woocommerce'), '<a href="https://www.liquichain.io/dashboard/developers/api-keys" target="_blank">', '</a>'));
        }

        if (empty(self::$api_client) || $needToUpdateApiKey) {
            $client = new LiquichainApiClient(null, new WordPressHttpAdapterPicker());
            $client->setApiKey($apiKey);
            $client->setApiEndpoint($this->getApiEndpoint());
            $client->addVersionString('WooCommerce/' . get_option('woocommerce_version', 'Unknown'));
            $client->addVersionString('WooCommerceSubscriptions/' . get_option('woocommerce_subscriptions_active_version', 'Unknown'));
            $client->addVersionString('LiquichainWoo/' . $this->pluginVersion);

            self::$api_client = $client;
        }

        return self::$api_client;
    }

    /**
     * Get API endpoint. Override using filter.
     * @return string
     */
    public function getApiEndpoint()
    {
        return apply_filters($this->pluginId . '_api_endpoint', \Liquichain\Api\LiquichainApiClient::API_ENDPOINT);
    }
}
