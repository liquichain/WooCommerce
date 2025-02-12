<?php

namespace Liquichain\WooCommerce\PaymentMethods\PaymentFieldsStrategies;

trait IssuersDropdownBehavior
{
    public function dropDownEnabled($gateway)
    {
        $defaultDropdownSetting = true;
        return $gateway->paymentMethod->getProperty('issuers_dropdown_shown') ?
            $gateway->paymentMethod->getProperty('issuers_dropdown_shown') === 'yes' :
            $defaultDropdownSetting;
    }
    public function getIssuers($gateway, $dataHelper)
    {
        $testMode = $dataHelper->isTestModeEnabled();
        $apiKey = $dataHelper->getApiKey();

        return $dataHelper->getMethodIssuers(
            $apiKey,
            $testMode,
            $gateway->paymentMethod->getProperty('id')
        );
    }

    public function renderIssuers($gateway, $issuers, $selectedIssuer)
    {
        $html = $this->issuersDropdownMarkup(
            $gateway,
            $issuers,
            $selectedIssuer
        );
        echo wpautop(wptexturize($html));
    }

    /**
     * @param $gateway
     * @param $issuers
     * @param $selectedIssuer
     *
     * @return string
     */
    public function issuersDropdownMarkup(
        $gateway,
        $issuers,
        $selectedIssuer
    ): string {

        $html = '<select name="' . $gateway->pluginId . '_issuer_'
            . $gateway->id . '">';
        $html .= $this->dropdownOptions($gateway, $issuers, $selectedIssuer);
        $html .= '</select>';
        return $html;
    }

    /**
     * @param        $description
     * @param string $html
     * @param        $issuers
     * @param        $selectedIssuer
     *
     * @return string
     */
    public function dropdownOptions(
        $gateway,
        $issuers,
        $selectedIssuer
    ): string {

        $description = $gateway->paymentMethod->getProperty(
            'issuers_empty_option'
        ) ?: $gateway->paymentMethod->getProperty('defaultDescription');

        $html = '<option value="">' . esc_html(
            __($description, 'liquichain-payments-for-woocommerce')
        ) . '</option>';
        foreach ($issuers as $issuer) {
            $html .= '<option value="' . esc_attr($issuer->id) . '"'
                . ($selectedIssuer === $issuer->id ? ' selected=""' : '') . '>'
                . esc_html($issuer->name) . '</option>';
        }
        return $html;
    }
}
