const {
    merchant,
    shopper,
    uiUnblocked,
} = require('@woocommerce/e2e-utils');

const config = require('config');
const {WP_ADMIN_WC_SETTINGS} = require("@woocommerce/e2e-utils/build/flows/constants");
const simpleProductName = config.get( 'products.simple.name' );

describe('Checkout page', () => {

    beforeAll(async () => {
        /*await merchant.login();
        await createSimpleProduct();

        // Enable iDeal payment method
        await merchant.openSettings('checkout', 'liquichain_wc_gateway_ideal');
        await setCheckbox('#liquichain_wc_gateway_ideal_enabled');
        await settingsPageSaveChanges();
        await verifyCheckboxIsSet('#liquichain_wc_gateway_ideal_enabled');

        await merchant.logout();*/
    }, 30000);

    async function liquichainPaidOrder() {
        await expect(page).toClick('.checkbox__label', {text: 'Paid'});
        await expect(page).toClick('.form__button', {text: 'Continue'});
        await page.waitForNavigation();
    }
    async function liquichainCancelOrder() {
        await expect(page).toClick('.checkbox__label', {text: 'Canceled'});
        await expect(page).toClick('.form__button', {text: 'Continue'});
        await page.waitForNavigation();
    }

    it('allows existing customer to place order of simple product', async () => {

        //await page.authenticate({'username':'', 'password': ''});

        //await shopper.login();
        await shopper.goToShop();
        await shopper.addToCartFromShopPage(simpleProductName);
        await shopper.goToCheckout();

        await uiUnblocked();
        await expect(page).toClick('.wc_payment_method label', {text: 'Przelewy24'});
        await uiUnblocked();
        await shopper.placeOrder();

        await liquichainPaidOrder();
        await expect(page).toMatch('Order received');

        let orderReceivedHtmlElement = await page.$('.woocommerce-order-overview__order.order');
        let orderReceivedText = await page.evaluate(element => element.textContent, orderReceivedHtmlElement);
        customerOrderId = orderReceivedText.split(/(\s+)/)[6].toString();

    }, 500000);

    it('store owner can confirm the order was received', async () => {

        await merchant.login();
        await merchant.goToOrder(customerOrderId);

        await uiUnblocked();
        await expect(page).toMatch('Processing');

    }, 500000);

    it('store owner can refund the order', async () => {

        await merchant.goToOrder(customerOrderId);
        page.on('dialog', async dialog => {

            await dialog.accept();
        });
        await expect(page).toClick('.refund-items', {text: 'Refund'});

        page.type('input[name=refund_amount]','9')
        await expect(page).toMatch('Refunded');

    }, 500000);

    it('allows existing customer to cancel order of simple product', async () => {
        //await page.authenticate({'username':'', 'password': ''});

        await shopper.goToShop();
        await shopper.addToCartFromShopPage(simpleProductName);
        await shopper.goToCheckout();

        await uiUnblocked();
        await expect(page).toClick('.wc_payment_method label', {text: 'Przelewy24'});
        await uiUnblocked();
        await shopper.placeOrder();

        await liquichainCancelOrder();
        await expect(page).toMatch('Pay for order');

    }, 500000);
});

describe('Settings page', () => {
    it('allows owner to see gateways in settings', async () => {
        //await page.authenticate({'username':'', 'password': ''});

        await merchant.login();
        await page.goto(WP_ADMIN_WC_SETTINGS + 'checkout', {
            waitUntil: 'networkidle0'
        });
        await expect(page).toMatch('Liquichain - Bank Transfer');
        await expect(page).toMatch('Liquichain - Belfius Direct Net');
    }, 500000);
});
