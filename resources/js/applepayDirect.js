import {createAppleErrors} from './applePayError.js';
import {maybeShowButton} from './maybeShowApplePayButton.js';
import {request} from './applePayRequest.js';

(
    function ({_, liquichainApplePayDirectData, jQuery}) {
        if (_.isEmpty(liquichainApplePayDirectData)) {
            return
        }

        const {product: {id, needShipping = true, isVariation = false, price}, shop: {countryCode, currencyCode = 'EUR', totalLabel = ''}, ajaxUrl} = liquichainApplePayDirectData

        if (!id || !price || !countryCode || !ajaxUrl) {
            return
        }
        if(!maybeShowButton()){
            return
        }

        const nonce = document.getElementById('woocommerce-process-checkout-nonce').value
        let productId = id
        let productQuantity = 1
        let updatedContactInfo = []
        let selectedShippingMethod = []
        let redirectionUrl = ''
        document.querySelector('input.qty').addEventListener('change', event => {
            productQuantity = event.currentTarget.value
        })

        if (isVariation) {
            let appleButton = document.querySelector('#liquichain_applepay_button');
            jQuery('.single_variation_wrap').on('show_variation', function (event, variation) {
                // Fired when the user selects all the required dropdowns / attributes
                // and a final variation is selected / shown
                if (variation.variation_id) {
                    productId = variation.variation_id
                }
                appleButton.disabled = false;
                appleButton.classList.remove("buttonDisabled");
            });
            appleButton.disabled = true;
            appleButton.classList.add("buttonDisabled");
        }
        const amountWithoutTax = productQuantity * price
        let applePaySession = () => {
            const session = new ApplePaySession(3, request(countryCode, currencyCode, totalLabel, amountWithoutTax))
            session.begin()
            if(needShipping){
                session.onshippingmethodselected = function (event) {
                    jQuery.ajax({
                        url: ajaxUrl,
                        method: 'POST',
                        data: {
                            action: 'liquichain_apple_pay_update_shipping_method',
                            shippingMethod: event.shippingMethod,
                            productId: productId,
                            callerPage: 'productDetail',
                            productQuantity: productQuantity,
                            simplifiedContact: updatedContactInfo,
                            'woocommerce-process-checkout-nonce': nonce,
                        },
                        complete: (jqXHR, textStatus) => {
                        },
                        success: (applePayShippingMethodUpdate, textStatus, jqXHR) => {
                            let response = applePayShippingMethodUpdate.data
                            selectedShippingMethod = event.shippingMethod
                            if (applePayShippingMethodUpdate.success === false) {
                                response.errors = createAppleErrors(response.errors)
                            }
                            this.completeShippingMethodSelection(response)
                        },
                        error: (jqXHR, textStatus, errorThrown) => {
                            console.warn(textStatus, errorThrown)
                            session.abort()
                        },
                    })
                }
                session.onshippingcontactselected = function (event) {
                    jQuery.ajax({
                        url: ajaxUrl,
                        method: 'POST',
                        data: {
                            action: 'liquichain_apple_pay_update_shipping_contact',
                            productId: productId,
                            callerPage: 'productDetail',
                            productQuantity: productQuantity,
                            simplifiedContact: event.shippingContact,
                            needShipping: needShipping,
                            'woocommerce-process-checkout-nonce': nonce,
                        },
                        complete: (jqXHR, textStatus) => {
                        },
                        success: (applePayShippingContactUpdate, textStatus, jqXHR) => {
                            let response = applePayShippingContactUpdate.data
                            updatedContactInfo = event.shippingContact
                            if (applePayShippingContactUpdate.success === false) {
                                response.errors = createAppleErrors(response.errors)
                            }
                            if (response.newShippingMethods) {
                                selectedShippingMethod = response.newShippingMethods[0]
                            }
                            this.completeShippingContactSelection(response)
                        },
                        error: (jqXHR, textStatus, errorThrown) => {
                            console.warn(textStatus, errorThrown)
                            session.abort()
                        },
                    })
                }
            }
            session.onvalidatemerchant = (applePayValidateMerchantEvent) => {
                jQuery.ajax({
                    url: ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'liquichain_apple_pay_validation',
                        validationUrl: applePayValidateMerchantEvent.validationURL,
                        'woocommerce-process-checkout-nonce': nonce,
                    },
                    complete: (jqXHR, textStatus) => {
                    },
                    success: (merchantSession, textStatus, jqXHR) => {
                        if (merchantSession.success === true) {
                            session.completeMerchantValidation(JSON.parse(merchantSession.data))
                        } else {
                            console.warn(merchantSession.data)
                            session.abort()
                        }
                    },
                    error: (jqXHR, textStatus, errorThrown) => {
                        console.warn(textStatus, errorThrown)
                        session.abort()
                    },
                })
            }
            session.onpaymentauthorized = (ApplePayPayment) => {
                const {billingContact, shippingContact } = ApplePayPayment.payment

                jQuery.ajax({
                    url: ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'liquichain_apple_pay_create_order',
                        productId: productId,
                        productQuantity: productQuantity,
                        shippingContact: ApplePayPayment.payment.shippingContact,
                        billingContact: ApplePayPayment.payment.billingContact,
                        token: ApplePayPayment.payment.token,
                        shippingMethod: selectedShippingMethod,
                        'liquichain-payments-for-woocommerce_issuer_applepay': 'applepay',
                        'woocommerce-process-checkout-nonce': nonce,
                        'billing_first_name':  billingContact.givenName || '',
                        'billing_last_name' : billingContact.familyName || '',
                        'billing_company': '',
                        'billing_country' : billingContact.countryCode || '',
                        'billing_address_1' : billingContact.addressLines[0] || '',
                        'billing_address_2' : billingContact.addressLines[1] || '',
                        'billing_postcode' : billingContact.postalCode || '',
                        'billing_city': billingContact.locality || '',
                        'billing_state' : billingContact.administrativeArea || '',
                        'billing_phone' : billingContact.phoneNumber || '000000000000',
                        'billing_email' : shippingContact.emailAddress || '',
                        'shipping_first_name':  shippingContact.givenName || '',
                        'shipping_last_name' : shippingContact.familyName || '',
                        'shipping_company': '',
                        'shipping_country' : shippingContact.countryCode || '',
                        'shipping_address_1' : shippingContact.addressLines[0] || '',
                        'shipping_address_2' : shippingContact.addressLines[1] || '',
                        'shipping_postcode' : shippingContact.postalCode || '',
                        'shipping_city': shippingContact.locality || '',
                        'shipping_state' : shippingContact.administrativeArea || '',
                        'shipping_phone' : shippingContact.phoneNumber || '000000000000',
                        'shipping_email' : shippingContact.emailAddress || '',
                        'order_comments' : '',
                        'payment_method' : 'liquichain_wc_gateway_applepay',
                        '_wp_http_referer' : '/?wc-ajax=update_order_review'
                    },
                    complete: (jqXHR, textStatus) => {
                    },
                    success: (authorizationResult, textStatus, jqXHR) => {
                        let result = authorizationResult.data

                        if (authorizationResult.success === true) {
                            redirectionUrl = result['returnUrl'];
                            session.completePayment(result['responseToApple'])
                            window.location.href = redirectionUrl
                        } else {
                            result.errors = createAppleErrors(result.errors)
                            session.completePayment(result)
                        }
                    },
                    error: (jqXHR, textStatus, errorThrown) => {
                        console.warn(textStatus, errorThrown)
                        session.abort()
                    },
                })
            }
        }
        document.querySelector('#liquichain_applepay_button').addEventListener('click', (evt) => {
            applePaySession()
        })
    }
)
(
    window
)
