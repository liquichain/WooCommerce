(
    function ({_, jQuery }) {

        function liquichain_settings__insertTextAtCursor(target, text, dontIgnoreSelection) {
            if (target.setRangeText) {
                if ( !dontIgnoreSelection ) {
                    // insert at end
                    target.setRangeText(text, target.value.length, target.value.length, "end");
                } else {
                    // replace selection
                    target.setRangeText(text, target.selectionStart, target.selectionEnd, "end");
                }
            } else {
                target.focus();
                document.execCommand("insertText", false /*no UI*/, text);
            }
            target.focus();
        }
        jQuery(document).ready(function($) {
            $(".liquichain-settings-advanced-payment-desc-label")
                .data("ignore-click", "false")
                .on("mousedown", function(e) {
                    const input = document.getElementById("liquichain-payments-for-woocommerce_api_payment_description");
                    if ( document.activeElement && input === document.activeElement ) {
                        $(this).on("mouseup.liquichainsettings", function(e) {
                            $(this).data("ignore-click", "true");
                            $(this).off(".liquichainsettings");
                            const tag = $(this).data("tag");
                            const input = document.getElementById("liquichain-payments-for-woocommerce_api_payment_description");
                            liquichain_settings__insertTextAtCursor(input, tag, true);
                        });
                    }
                    let $this = $(this);
                    $(window).on("mouseup.liquichainsettings drag.liquichainsettings blur.liquichainsettings", function(e) {
                        $this.off(".liquichainsettings");
                        $(window).off(".liquichainsettings");
                    });
                })
                .on("click", function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    if ( $(this).data("ignore-click") === "false" ) {
                        const tag = $(this).data("tag");
                        const input = document.getElementById("liquichain-payments-for-woocommerce_api_payment_description");
                        liquichain_settings__insertTextAtCursor(input, tag, false);
                    } else {
                        $(this).data("ignore-click", "false");
                    }
                })
            ;
        });
    }
)
(
    window
)
