
(
    function ({_, liquichainpaypalbutton, jQuery}) {

        if (_.isEmpty(liquichainpaypalbutton)) {
            return
        }

        const {product: {id, needShipping = true, isVariation = false, price, minFee}, ajaxUrl} = liquichainpaypalbutton

        if (!id || !price || !ajaxUrl) {
            return
        }
        const payPalButton = document.querySelector('#liquichain-PayPal-button');

        const maybeShowButton = (underRange) => {
            if(underRange){
                hideButton()
            }else{
                showButton()
            }
        }
        const checkPriceRange = (productQuantity) => {
            let updatedPrice = productQuantity * price
            jQuery.ajax({
                url: ajaxUrl,
                method: 'POST',
                data: {
                    action: 'liquichain_paypal_update_amount',
                    productId: productId,
                    productQuantity: productQuantity,
                    nonce: nonce,
                },
                success: (response) => {
                    updatedPrice = parseFloat(response.data)
                    const underRange = parseFloat(minFee) > updatedPrice
                    maybeShowButton(underRange)
                },
                error: (response) => {
                    console.warn(response)
                },
            })
        }
        const hideButton = () => {
            if(payPalButton.parentNode !== null){
                payPalButton.parentNode.removeChild(payPalButton)
            }
        }
        const showButton = () => {
            const elements = document.getElementsByClassName('entry-summary');
            const parent = elements[0]
            parent.appendChild(payPalButton)
        }
        const nonce = payPalButton.children[0].value
        let productId = id
        let productQuantity = 1
        let redirectionUrl = ''
        document.querySelector('input.qty').addEventListener('change', event => {
            productQuantity = event.currentTarget.value
            checkPriceRange(productQuantity)
        })
        checkPriceRange(productQuantity)

        const fadeButton = () => {
            payPalButton.disabled = true;
            payPalButton.classList.add("buttonDisabled");
        }

        if (isVariation) {
            jQuery('.single_variation_wrap').on('show_variation', function (event, variation) {
                // Fired when the user selects all the required dropdowns / attributes
                // and a final variation is selected / shown
                if (variation.is_virtual && variation.variation_id) {
                    productId = variation.variation_id
                    payPalButton.disabled = false;
                    payPalButton.classList.remove("buttonDisabled");
                }
            });
            jQuery('.reset_variations').on('click.wc-variation-form', function (event) {
                productId = ''
                fadeButton();

            });
            fadeButton();
        }
        if(payPalButton.parentNode == null){
            return
        }
        let preventSpam = false
        document.querySelector('#liquichain-PayPal-button').addEventListener('click', (evt) => {
            if(!(payPalButton.parentNode !== null) || payPalButton.disabled){
                return
            }
            payPalButton.disabled = true;
            payPalButton.classList.add("buttonDisabled");
            if(!preventSpam){
                jQuery.ajax({
                    url: ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'liquichain_paypal_create_order',
                        productId: productId,
                        productQuantity: productQuantity,
                        needShipping: needShipping,
                        'liquichain-payments-for-woocommerce_issuer_paypal_button': 'paypal',
                        nonce: nonce,
                    },
                    success: (response) => {
                        let result = response.data

                        if (response.success === true) {
                            redirectionUrl = result['redirect'];
                            window.location.href = redirectionUrl
                        } else {
                            console.log(response.data)
                        }
                    },
                    error: (jqXHR, textStatus, errorThrown) => {
                        payPalButton.disabled = false;
                        payPalButton.classList.remove("buttonDisabled");
                        console.warn(textStatus, errorThrown)
                    },
                })
            }
            preventSpam = true
            if(preventSpam){
                setTimeout(function() {
                    payPalButton.disabled = false;
                    payPalButton.classList.remove("buttonDisabled");
                    preventSpam = false
                }, 3000);
            }
        })
    }
)
(
    window
)



