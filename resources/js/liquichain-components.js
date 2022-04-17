const SELECTOR_TOKEN_ELEMENT = '.cardToken'
const SELECTOR_LIQUICHAIN_COMPONENTS_CONTAINER = '.liquichain-components'
const SELECTOR_FORM = 'form'
const SELECTOR_LIQUICHAIN_GATEWAY_CONTAINER = '.wc_payment_methods'
const SELECTOR_LIQUICHAIN_GATEWAY_BLOCK_CONTAINER = '.wc-block-components-radio-control'

const SELECTOR_LIQUICHAIN_NOTICE_CONTAINER = '#liquichain-notice'

function returnFalse ()
{
  return false
}
function returnTrue ()
{
    return true
}

/* -------------------------------------------------------------------
   Containers
   ---------------------------------------------------------------- */
function gatewayContainer (container)
{
    let checkoutContainer = container ? container.querySelector(SELECTOR_LIQUICHAIN_GATEWAY_CONTAINER) : null
    let blockContainer = container ? container.querySelector(SELECTOR_LIQUICHAIN_GATEWAY_BLOCK_CONTAINER) : null
  return checkoutContainer ? checkoutContainer : blockContainer
}

function containerForGateway (gateway, container)
{
  return container ? container.querySelector(`.payment_method_liquichain_wc_gateway_${gateway}`) : null
}

function noticeContainer (container)
{
  return container ? container.querySelector(SELECTOR_LIQUICHAIN_NOTICE_CONTAINER) : null
}

function componentsContainerFromWithin (container)
{
  return container ? container.querySelector(SELECTOR_LIQUICHAIN_COMPONENTS_CONTAINER) : null
}

function cleanContainer (container)
{
  if (!container) {
    return
  }

  container.innerText = ''
}

/* -------------------------------------------------------------------
   Notice
   ---------------------------------------------------------------- */
function renderNotice ({ content, type })
{
  return `
      <div id="liquichain-notice" class="woocommerce-${type}">
        ${content}
      </div>
    `
}

function printNotice (jQuery, noticeData)
{
  const container = gatewayContainer(document)
  const formContainer = closestFormForElement(container).parentNode || null
  const liquichainNotice = noticeContainer(document)
  const renderedNotice = renderNotice(noticeData)

  liquichainNotice && liquichainNotice.remove()

  if (!formContainer) {
    alert(noticeData.content)
    return
  }

  formContainer.insertAdjacentHTML('beforebegin', renderedNotice)

  scrollToNotice(jQuery)
}

function scrollToNotice (jQuery)
{
  var scrollToElement = noticeContainer(document)

  if (!scrollToElement) {
    scrollToElement = gatewayContainer(document)
  }

  jQuery.scroll_to_notices(jQuery(scrollToElement))
}

/* -------------------------------------------------------------------
   Token
   ---------------------------------------------------------------- */
function createTokenFieldWithin (container)
{
  container.insertAdjacentHTML(
    'beforeend',
    '<input type="hidden" name="cardToken" class="cardToken" value="" />'
  )
}

function tokenElementWithin (container)
{
  return container.querySelector(SELECTOR_TOKEN_ELEMENT)
}

async function retrievePaymentToken (liquichain)
{
  const { token, error } = await liquichain.createToken(SELECTOR_TOKEN_ELEMENT)

  if (error) {
    throw new Error(error.message || '')
  }

  return token
}

function setTokenValueToField (token, tokenFieldElement)
{
  if (!tokenFieldElement) {
    return
  }

  tokenFieldElement.value = token
  tokenFieldElement.setAttribute('value', token)
}

/* -------------------------------------------------------------------
   Form
   ---------------------------------------------------------------- */
function closestFormForElement (element)
{
  return element ? element.closest(SELECTOR_FORM) : null
}

function turnLiquichainComponentsSubmissionOff ($form)
{
  $form.off('checkout_place_order', returnFalse)
  $form.off('submit', submitForm)
}

function turnBlockListenerOff (target)
{
    target.off('click', submitForm)
}

function isGatewaySelected (gateway)
{
  const gatewayContainer = containerForGateway(gateway, document)
  const gatewayInput = gatewayContainer
    ? gatewayContainer.querySelector(`#payment_method_liquichain_wc_gateway_${gateway}`)
    : null
    //if we are in blocks then the input is different
    const gatewayBlockInput = document.getElementById("radio-control-wc-payment-method-options-liquichain_wc_gateway_creditcard")
    if(gatewayBlockInput){
        return gatewayBlockInput.checked || false
    }

  if (!gatewayInput) {
    return false
  }

  return gatewayInput.checked || false
}

async function submitForm (evt)
{
  let token = ''
  const { jQuery, liquichain, gateway, gatewayContainer, messages } = evt.data
  const form = closestFormForElement(gatewayContainer)
  const $form = jQuery(form)
  const $document = jQuery(document.body)

  if (!isGatewaySelected(gateway)) {
    // Let other gateway to submit the form
    turnLiquichainComponentsSubmissionOff($form)
    $form.submit()
    return
  }

  evt.preventDefault()
  evt.stopImmediatePropagation()

  try {
    token = await retrievePaymentToken(liquichain)
  } catch (error) {
    const content = { message = messages.defaultErrorMessage } = error
    content && printNotice(jQuery, { content, type: 'error' })

    $form.removeClass('processing').unblock()
    $document.trigger('checkout_error')
    return
  }

  turnLiquichainComponentsSubmissionOff($form)

  token && setTokenValueToField(token, tokenElementWithin(gatewayContainer))
    if(evt.type === 'click'){
        turnBlockListenerOff(jQuery(evt.target))
        let readyToSubmitBlock = new Event("liquichain_components_ready_to_submit", {bubbles: true});
        document.documentElement.dispatchEvent(readyToSubmitBlock);
        return
    }

  $form.submit()
}

/* -------------------------------------------------------------------
   Component
   ---------------------------------------------------------------- */
function componentElementByNameFromWithin (name, container)
{
  return container ? container.querySelector(`.liquichain-component--${name}`) : null
}

function createComponentLabelElementWithin (container, { label })
{
  container.insertAdjacentHTML(
    'beforebegin',
    `<b class="liquichain-component-label">${label}</b>`
  )
}

function createComponentsErrorContainerWithin (container, { name })
{
  container.insertAdjacentHTML(
    'afterend',
    `<div role="alert" id="${name}-errors"></div>`
  )
}

function componentByName (name, liquichain, settings, liquichainComponentsMap)
{
  let component

  if (liquichainComponentsMap.has(name)) {
    component = liquichainComponentsMap.get(name)
  }
  if (!component) {
    component = liquichain.createComponent(name, settings)
  }

  return component
}

function unmountComponents (liquichainComponentsMap)
{
  liquichainComponentsMap.forEach(component => component.unmount())
}

function mountComponent (
  liquichain,
  componentSettings,
  componentAttributes,
  liquichainComponentsMap,
  baseContainer
)
{
  const { name: componentName } = componentAttributes
  const component = componentByName(componentName, liquichain, componentSettings, liquichainComponentsMap)
  const liquichainComponentsContainer = componentsContainerFromWithin(baseContainer)

  liquichainComponentsContainer.insertAdjacentHTML('beforeend', `<div id="${componentName}"></div>`)
  component.mount(`#${componentName}`)

  const currentComponentElement = componentElementByNameFromWithin(componentName, baseContainer)
  if (!currentComponentElement) {
    console.warn(`Component ${componentName} not found in the DOM. Probably had problem during mount.`)
    return
  }

  createComponentLabelElementWithin(currentComponentElement, componentAttributes)
  createComponentsErrorContainerWithin(currentComponentElement, componentAttributes)
  let componentError = document.querySelector('#' + componentName + '-errors')
  component.addEventListener('change', event => {
      if (event.error && event.touched) {
          componentError.textContent = event.error
      } else {
          componentError.textContent = ''
      }
  })

  !liquichainComponentsMap.has(componentName) && liquichainComponentsMap.set(componentName, component)
}

function mountComponents (
  liquichain,
  componentSettings,
  componentsAttributes,
  liquichainComponentsMap,
  baseContainer
)
{
  componentsAttributes.forEach(
    componentAttributes => mountComponent(
      liquichain,
      componentSettings,
      componentAttributes,
      liquichainComponentsMap,
      baseContainer
    )
  )
}

/* -------------------------------------------------------------------
   Init
   ---------------------------------------------------------------- */

/**
 * Unmount and Mount the components if them already exists, create them if it's the first time
 * the components are created.
 */
function initializeComponents (
  jQuery,
  liquichain,
  {
    options,
    merchantProfileId,
    componentsSettings,
    componentsAttributes,
    enabledGateways,
    messages
  },
  liquichainComponentsMap
)
{

  /*
   * WooCommerce update the DOM when something on checkout page happen.
   * Liquichain does not allow to keep a copy of the mounted components.
   *
   * We have to mount every time the components but we cannot recreate them.
   */
  unmountComponents(liquichainComponentsMap)

  enabledGateways.forEach(gateway =>
  {
    const gatewayContainer = containerForGateway(gateway, document)
    const liquichainComponentsContainer = componentsContainerFromWithin(gatewayContainer)
    const form = closestFormForElement(gatewayContainer)
    const $form = jQuery(form)

    if (!gatewayContainer) {
      console.warn(`Cannot initialize Liquichain Components for gateway ${gateway}.`)
      return
    }

    if (!form) {
      console.warn('Cannot initialize Liquichain Components, no form found.')
      return
    }

    // Remove old listener before add new ones or form will not be submitted
    turnLiquichainComponentsSubmissionOff($form)

    /*
     * Clean container for liquichain components because we do not know in which context we may need
     * to create components.
     */
    cleanContainer(liquichainComponentsContainer)
    createTokenFieldWithin(liquichainComponentsContainer)

    mountComponents(
      liquichain,
      componentsSettings[gateway],
      componentsAttributes,
      liquichainComponentsMap,
      gatewayContainer
    )

    $form.on('checkout_place_order', returnFalse)
    $form.on(
      'submit',
      null,
      {
        jQuery,
        liquichain,
        gateway,
        gatewayContainer,
        messages
      },
      submitForm
    )
      //waiting for the blocks to load, this should receive an event to look for the button instead
      setTimeout(function (){
          submitButton = jQuery(".wc-block-components-checkout-place-order-button")

          jQuery(submitButton).click(
              {
                  jQuery,
                  liquichain,
                  gateway,
                  gatewayContainer,
                  messages
              },
              submitForm
          )
      },500)
  })
}

(
    function ({ _, Liquichain, liquichainComponentsSettings, jQuery })
    {
        if (_.isEmpty(liquichainComponentsSettings) || !_.isFunction(Liquichain)) {
            return
        }

        let eventName = 'updated_checkout'
        const liquichainComponentsMap = new Map()
        const $document = jQuery(document)
        const { merchantProfileId, options, isCheckoutPayPage } = liquichainComponentsSettings
        const liquichain = new Liquichain(merchantProfileId, options)


        if (isCheckoutPayPage) {
            eventName = 'payment_method_selected'
            $document.on(
                eventName,
                () => initializeComponents(
                    jQuery,
                    liquichain,
                    liquichainComponentsSettings,
                    liquichainComponentsMap
                )
            )
            return
        }

        document.addEventListener("liquichain_creditcard_component_selected", function(event) {
            setTimeout(function(){
                initializeComponents(
                    jQuery,
                    liquichain,
                    liquichainComponentsSettings,
                    liquichainComponentsMap
                )
            },500);
        });

        function checkInit() {
            return function () {
                let copySettings = JSON.parse(JSON.stringify(liquichainComponentsSettings))
                liquichainComponentsSettings.enabledGateways.forEach(function (gateway, index) {
                    const gatewayContainer = containerForGateway(gateway, document)
                    if (!gatewayContainer) {
                        copySettings.enabledGateways.splice(index, 1)
                        const $form = jQuery('form[name="checkout"]')
                        $form.on('checkout_place_order', returnTrue)
                    }
                })
                if (_.isEmpty(copySettings.enabledGateways)) {
                    return
                }
                initializeComponents(
                    jQuery,
                    liquichain,
                    copySettings,
                    liquichainComponentsMap
                )
            };
        }

        $document.on(
            eventName,
            checkInit()
        )
        $document.on(
            'update_checkout',
            checkInit()
        )
    }
)
(
    window
)
