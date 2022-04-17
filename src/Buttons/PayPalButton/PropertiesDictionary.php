<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\Buttons\PayPalButton;

class PropertiesDictionary
{
    /**
     * @var string[]
     */
    public const CREATE_ORDER_SINGLE_PROD_REQUIRED_FIELDS
        = [
            PropertiesDictionary::NONCE,
            PropertiesDictionary::PRODUCT_ID,
            self::PRODUCT_QUANTITY
        ];
    /**
     * @var string[]
     */
    public const CREATE_ORDER_CART_REQUIRED_FIELDS
        = [
            PropertiesDictionary::NONCE
        ];

    /**
     * @var string
     */
    public const PRODUCT_ID = 'productId';
    /**
     * @var string
     */
    public const NONCE = 'nonce';
    /**
     * @var string
     */
    public const PRODUCT_QUANTITY = 'productQuantity';
    /**
     * @var string
     */
    public const CALLER_PAGE = 'callerPage';
    /**
     * @var string
     */
    public const NEED_SHIPPING = 'needShipping';
    /**
     * @var string
     */
    public const CREATE_ORDER = 'liquichain_paypal_create_order';
    /**
     * @var string
     */
    public const CREATE_ORDER_CART = 'liquichain_paypal_create_order_cart';
    /**
     * @var string
     */
    public const UPDATE_AMOUNT = 'liquichain_paypal_update_amount';
    /**
     * @var string
     */
    public const REDIRECT = 'liquichain_paypal_redirect';
}
