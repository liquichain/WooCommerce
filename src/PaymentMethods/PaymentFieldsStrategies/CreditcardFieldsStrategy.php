<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\PaymentMethods\PaymentFieldsStrategies;

use Liquichain\WooCommerce\PaymentMethods\PaymentMethodI;

class CreditcardFieldsStrategy implements PaymentFieldsStrategyI
{

    public function execute($gateway, $dataHelper)
    {
        if (!$this->isLiquichainComponentsEnabled($gateway->paymentMethod)) {
            return;
        }
        $gateway->has_fields = true;

        ?>
        <div class="liquichain-components"></div>
        <p class="liquichain-components-description">
            <?php
            printf(
            /* translators: Placeholder 1: Lock icon. Placeholder 2: Liquichain logo. */
            __('%1$s Secure payments provided by %2$s',
                    'liquichain-payments-for-woocommerce'),
                $this->lockIcon($dataHelper),
                $this->liquichainLogo($dataHelper)
            );
            ?>
        </p>
        <?php
    }

    public function getFieldMarkup($gateway, $dataHelper)
    {
        if (!$this->isLiquichainComponentsEnabled($gateway->paymentMethod)) {
            return false;
        }
        $gateway->has_fields = true;
        $descriptionTranslated = __('Secure payments provided by', 'liquichain-payments-for-woocommerce');
        $componentsDescription = "{$this->lockIcon($dataHelper)} {$descriptionTranslated} {$this->liquichainLogo($dataHelper)}";
        return "<div class='payment_method_liquichain_wc_gateway_creditcard'><div class='liquichain-components'></div><p class='liquichain-components-description'>{$componentsDescription}</p></div>";
    }

    protected function isLiquichainComponentsEnabled(PaymentMethodI $paymentMethod): bool
    {
        return $paymentMethod->getProperty('liquichain_components_enabled') === 'yes';
    }

    protected function lockIcon($dataHelper)
    {
        return file_get_contents(
            $dataHelper->pluginPath . '/' . 'public/images/lock-icon.svg'
        );
    }

    protected function liquichainLogo($dataHelper)
    {
        return file_get_contents(
            $dataHelper->pluginPath . '/' . 'public/images/liquichain-logo.svg'
        );
    }
}
