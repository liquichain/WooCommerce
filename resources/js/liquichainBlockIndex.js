import liquichainPaymentMethod from './blocks/liquichainPaymentMethod'

(
    function ({ liquichainBlockData, wc, _, jQuery}) {
        if (_.isEmpty(liquichainBlockData)) {
            return
        }
        const { registerPaymentMethod } = wc.wcBlocksRegistry;
        const { ajaxUrl, filters, gatewayData, availableGateways } = liquichainBlockData.gatewayData;
        const {useEffect} = wp.element;
        const isAppleSession = typeof window.ApplePaySession === "function"

        gatewayData.forEach(item=>{
            if(item.name !== 'liquichain_wc_gateway_applepay'){
                registerPaymentMethod(liquichainPaymentMethod(useEffect, ajaxUrl, filters, gatewayData, availableGateways, item, jQuery))
            }
            if(item.name === 'liquichain_wc_gateway_applepay' &&  isAppleSession && window.ApplePaySession.canMakePayments()){
                registerPaymentMethod(liquichainPaymentMethod(useEffect, ajaxUrl, filters, gatewayData, availableGateways, item, jQuery))
            }
        })
    }
)
(
    window, wc
)
