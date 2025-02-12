<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods\PaymentFieldsStrategies;

class GiftcardFieldsStrategy implements PaymentFieldsStrategyI
{
    use IssuersDropdownBehavior;

    public function execute($gateway, $dataHelper)
    {
        if (!$this->dropDownEnabled($gateway)) {
            return;
        }

        $issuers = $this->getIssuers($gateway, $dataHelper);

        $selectedIssuer = $gateway->getSelectedIssuer();

        $html = '';

        // If only one gift card issuers is available, show it without a dropdown
        if (count($issuers) === 1) {
            $issuerImageSvg = $this->checkSvgIssuers($issuers);
            $issuerImageSvg && ($html .= '<img src="' . $issuerImageSvg . '" style="vertical-align:middle" />');
            $html .= $issuers->description;
            echo wpautop(wptexturize($html));

            return;
        }

        $this->renderIssuers($gateway, $issuers, $selectedIssuer);
    }

    public function getFieldMarkup($gateway, $dataHelper)
    {
        if (!$this->dropDownEnabled($gateway)) {
            return "";
        }
        $issuers = $this->getIssuers($gateway, $dataHelper);
        $selectedIssuer = $gateway->getSelectedIssuer();
        $markup = $this->dropdownOptions($gateway, $issuers, $selectedIssuer);
        return $markup;
    }

    /**
     * @param $issuers
     */
    protected function checkSvgIssuers($issuers): string
    {
        if (!isset($issuers[0]) || ! is_object($issuers[0])) {
            return '';
        }
        $image = property_exists($issuers[0], 'image') && $issuers[0]->image !== null ? $issuers[0]->image : null;
        if (!$image) {
            return '';
        }
        return property_exists($image, 'svg') && $image->svg !== null && is_string($image->svg) ? $image->svg : '';
    }
}
