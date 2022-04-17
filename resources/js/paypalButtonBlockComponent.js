import {ajaxCallToOrder} from "./paypalButtonUtils";

(
    function ({  liquichainpaypalButtonCart})
    {
        if (liquichainpaypalButtonCart.length === 0 ) {
            return
        }

        const { registerPlugin } = wp.plugins;
        const { ExperimentalOrderMeta } = wc.blocksCheckout;
        const { minFee, ajaxUrl, buttonMarkup } = liquichainpaypalButtonCart;
        const PayPalButtonComponent = ( { cart, extensions } ) => {
            let cartTotal = cart.cartTotals.total_price/Math.pow(10, cart.cartTotals.currency_minor_unit)
            const amountOverRangeSetting = cartTotal > minFee;
            const cartNeedsShipping = cart.cartNeedsShipping
            return amountOverRangeSetting && !cartNeedsShipping ? <div dangerouslySetInnerHTML={ {__html: buttonMarkup} }/>: null
        }
        const LiquichainPayPalButtonCart = () => {
            return  <ExperimentalOrderMeta>
                    <PayPalButtonComponent />
                </ExperimentalOrderMeta>
        };

        registerPlugin( 'liquichain-paypal-block-button', {
            render: () => {
                return <LiquichainPayPalButtonCart />;
            },
            scope: 'woocommerce-checkout'
        } );

        setTimeout(function(){
            let payPalButton = document.getElementById('liquichain-PayPal-button');
            if(payPalButton == null || payPalButton.parentNode == null){
                return
            }
            ajaxCallToOrder(ajaxUrl)
        },500);
    }
)
(
    window
)
