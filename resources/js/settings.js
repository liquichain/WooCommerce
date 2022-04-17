(
    function ({_, liquichainSettingsData, jQuery })
    {
        const {current_section = false} = liquichainSettingsData
        jQuery(function($) {

            $('#liquichain-payments-for-woocommerce_test_mode_enabled').change(function() {
                if ($(this).is(':checked'))
                {
                    $('#liquichain-payments-for-woocommerce_test_api_key').attr('required', true).closest('tr').show();
                }
                else
                {
                    $('#liquichain-payments-for-woocommerce_test_api_key').removeAttr('required').closest('tr').hide();
                }
            }).change();

            if(_.isEmpty(liquichainSettingsData)){
                return
            }
            const gatewayName = current_section
            if(!gatewayName){
                return
            }
            let fixedField = $('#'+gatewayName+'_fixed_fee').closest('tr')
            let percentField = $('#'+gatewayName+'_percentage').closest('tr')
            let limitField = $('#'+gatewayName+'_surcharge_limit').closest('tr')
            let maxField = $('#'+gatewayName+'_maximum_limit').closest('tr')

            $('#'+gatewayName+'_payment_surcharge').change(function() {
                switch ($(this).val()){
                    case 'no_fee':
                        fixedField.hide()
                        percentField.hide()
                        limitField.hide()
                        maxField.hide()
                        break
                    case 'fixed_fee':
                        fixedField.show()
                        maxField.show()
                        percentField.hide()
                        limitField.hide()
                        break
                    case 'percentage':
                        fixedField.hide()
                        maxField.show()
                        percentField.show()
                        limitField.show()
                        break
                    case 'fixed_fee_percentage':
                    default:
                        fixedField.show()
                        percentField.show()
                        limitField.show()
                        maxField.show()
                }
            }).change();
        });
    }
)
(
    window
)
