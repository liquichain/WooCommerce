<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\Uninstall;

use Liquichain\WooCommerce\Shared\SharedDataDictionary;

class CleanDb
{
    /**
     * @var array
     */
    protected $gatewayClassnames;

    /**
     * CleanDb constructor.
     */
    public function __construct(array $gatewayClassnames)
    {
        $this->gatewayClassnames = $gatewayClassnames;
    }

    public function cleanAll(){
        $options = $this->allLiquichainOptionNames();
        $this->deleteSiteOptions($options);
        $this->cleanScheduledJobs();
    }

    /**
     * @param array $options
     * @return int
     */
    protected function deleteSiteOptions(array $options): void
    {
        foreach ($options as $option){
            delete_option($option);
        }
    }

    protected function cleanScheduledJobs()
    {
        as_unschedule_action( 'liquichain_woocommerce_cancel_unpaid_orders' );
    }

    protected function allLiquichainOptionNames(): array
    {
        $names = SharedDataDictionary::MOLLIE_OPTIONS_NAMES;
        foreach ($this->gatewayClassnames as $gateway){
            $option = strtolower($gateway) . "_settings";
            $names[] = $option;
        }

        return $names;
    }
}
