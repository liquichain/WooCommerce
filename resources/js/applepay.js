(function (ApplePaySession) {

  document.addEventListener('DOMContentLoaded', function () {
    var applePayMethodElement = document.querySelector(
      '.payment_method_liquichain_wc_gateway_applepay',
    )

    var woocommerceCheckoutForm = document.querySelector(
      'form.woocommerce-checkout',
    )

    if (!woocommerceCheckoutForm) {
      return
    }

    if (!ApplePaySession || !ApplePaySession.canMakePayments()) {
      applePayMethodElement &&
      applePayMethodElement.parentNode.removeChild(applePayMethodElement)
      return
    }

    woocommerceCheckoutForm.insertAdjacentHTML(
      'beforeend',
      '<input type="hidden" name="liquichain_apple_pay_method_allowed" value="1" />',
    )
  })
})(window.ApplePaySession)
