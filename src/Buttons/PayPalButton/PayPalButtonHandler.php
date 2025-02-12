<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\Buttons\PayPalButton;

class PayPalButtonHandler
{
    /**
     * @var PayPalAjaxRequests
     */
    private $ajaxRequests;
    /**
     * @var string
     */
    protected $dataPaypal;

    /**
     * PayPalHandler constructor.
     */
    public function __construct(PayPalAjaxRequests $ajaxRequests, DataToPayPal $dataPaypal)
    {
        $this->ajaxRequests = $ajaxRequests;
        $this->dataPaypal = $dataPaypal;
    }

    /**
     * Initial method that puts the button in place
     * and adds all the necessary actions
     */
    public function bootstrap($enabledInProduct, $enabledInCart)
    {
        //y no hay shipping enseño, luego ya la cantidad en js

        if($enabledInProduct){
            add_action(
                    'woocommerce_after_single_product',
                    function () {
                        $product = wc_get_product(get_the_id());
                        if (!$product || $product->is_type('subscription')) {
                            return;
                        }
                        $productNeedShipping = liquichainWooCommerceCheckIfNeedShipping($product);
                        if(!$productNeedShipping){
                            $this->renderPayPalButton();
                        }
                    }
            );
        }
        if($enabledInCart){
            add_action(
                    'woocommerce_cart_totals_after_order_total',
                    function () {
                        $cart = WC()->cart;
                        foreach ($cart->get_cart_contents() as $product){
                            if($product['data']->is_type('subscription')){
                                return;
                            }
                        }
                        if(!$cart->needs_shipping()){
                            $this->renderPayPalButton();
                        }
                    }
            );
        }

        admin_url('admin-ajax.php');
        $this->ajaxRequests->bootstrapAjaxRequest();
    }

    /**
     * PayPal button markup
     */
    protected function renderPayPalButton()
    {
        $assetsImagesUrl
                = $this->dataPaypal->selectedPaypalButtonUrl();

        ?>
        <div id="liquichain-PayPal-button" class="mol-PayPal">
            <?php wp_nonce_field('liquichain_PayPal_button'); ?>
            <input type="image" src="<?php echo esc_url( $assetsImagesUrl)?>" alt="PayPal Button">
        </div>
        <?php
    }
}

