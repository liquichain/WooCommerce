<?php

/**
 * This file is part of the  Liquichain\WooCommerce.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * PHP version 7
 *
 * @category Activation
 * @package  Liquichain\WooCommerce
 * @author   AuthorName <hello@inpsyde.com>
 * @license  GPLv2+
 * @link     https://www.inpsyde.com
 */

# -*- coding: utf-8 -*-

declare(strict_types=1);

namespace Liquichain\WooCommerce\Gateway\Voucher;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Liquichain\WooCommerce\PaymentMethods\Voucher;
use Psr\Container\ContainerInterface;

class VoucherModule implements ExecutableModule
{
    use ModuleClassNameIdTrait;

    /**
     * @param ContainerInterface $container
     *
     * @return bool
     */
    public function run(ContainerInterface $container): bool
    {
        $gatewayInstances = $container->get('gateway.instances');
        $voucherGateway = isset($gatewayInstances['liquichain_wc_gateway_voucher'])?$gatewayInstances['liquichain_wc_gateway_voucher']:false;
        $voucher = $voucherGateway? $voucherGateway->enabled === 'yes': false;

        if($voucher){
            $this->voucherEnabledHooks();
        }

        return true;
    }

    public function voucherEnabledHooks()
    {
        add_filter(
                'woocommerce_product_data_tabs',
                static function ($tabs) {
                    $tabs['LiquichainSettingsPage'] = [
                            'label' => __('Liquichain Settings', 'liquichain-payments-for-woocommerce'),
                            'target' => 'liquichain_options',
                            'class' => ['show_if_simple', 'show_if_variable'],
                    ];

                    return $tabs;
                }
        );
        add_filter('woocommerce_product_data_panels', [$this, 'liquichainOptionsProductTabContent']);
        add_action('woocommerce_process_product_meta_simple', [$this, 'saveProductVoucherOptionFields']);
        add_action('woocommerce_process_product_meta_variable', [$this, 'saveProductVoucherOptionFields']);
        add_action('woocommerce_product_after_variable_attributes', [$this, 'voucherFieldInVariations'], 10, 3);
        add_action('woocommerce_save_product_variation', [$this, 'saveVoucherFieldVariations'], 10, 2);
        add_filter('woocommerce_available_variation', [$this, 'addVoucherVariationData']);
        add_action('woocommerce_product_bulk_edit_start', [$this, 'voucherBulkEditInput']);
        add_action('woocommerce_product_bulk_edit_save', [$this, 'voucherBulkEditSave']);
        add_action('product_cat_add_form_fields', [$this, 'voucherTaxonomyFieldOnCreatePage'], 10, 1);
        add_action('product_cat_edit_form_fields', [$this, 'voucherTaxonomyFieldOnEditPage'], 10, 1);
        add_action('edited_product_cat', [$this, 'voucherTaxonomyCustomMetaSave'], 10, 1);
        add_action('create_product_cat', [$this, 'voucherTaxonomyCustomMetaSave'], 10, 1);
    }

    /**
     * Show voucher selector on product edit bulk action
     */
    public function voucherBulkEditInput()
    {
        ?>
        <div class="inline-edit-group">
            <label class="alignleft">
                <span class="title"><?php _e('Liquichain Voucher Category', 'liquichain-payments-for-woocommerce'); ?></span>
                <span class="input-text-wrap">
                <select name="_liquichain_voucher_category" class="select">
                   <option value=""><?php _e('--Please choose an option--', 'liquichain-payments-for-woocommerce'); ?></option>
                   <option value="no_category"> <?php _e('No Category', 'liquichain-payments-for-woocommerce'); ?></option>
                   <option value="meal"><?php _e('Meal', 'liquichain-payments-for-woocommerce'); ?></option>
                   <option value="eco"><?php _e('Eco', 'liquichain-payments-for-woocommerce'); ?></option>
                   <option value="gift"><?php _e('Gift', 'liquichain-payments-for-woocommerce'); ?></option>
                </select>
         </span>
            </label>
        </div>
        <?php
    }

    /**
     * Save value entered on product edit bulk action.
     */
    public function voucherBulkEditSave($product)
    {
        $post_id = $product->get_id();
        $optionName = Voucher::MOLLIE_VOUCHER_CATEGORY_OPTION;
        if (isset($_REQUEST[$optionName])) {
            $option = $_REQUEST[$optionName];
            update_post_meta($post_id, $optionName, wc_clean($option));
        }
    }

    /**
     * Show voucher selector on create product category page.
     */
    public function voucherTaxonomyFieldOnCreatePage()
    {
        ?>
        <div class="form-field">
            <label for="_liquichain_voucher_category"><?php _e('Liquichain Voucher Category', 'liquichain-payments-for-woocommerce'); ?></label>
            <select name="_liquichain_voucher_category" id="_liquichain_voucher_category" class="select">
                <option value=""><?php _e('--Please choose an option--', 'liquichain-payments-for-woocommerce'); ?></option>
                <option value="no_category"> <?php _e('No Category', 'liquichain-payments-for-woocommerce'); ?></option>
                <option value="meal"><?php _e('Meal', 'liquichain-payments-for-woocommerce'); ?></option>
                <option value="eco"><?php _e('Eco', 'liquichain-payments-for-woocommerce'); ?></option>
                <option value="gift"><?php _e('Gift', 'liquichain-payments-for-woocommerce'); ?></option>
            </select>
            <p class="description"><?php _e('Select a voucher category to apply to all products with this category', 'liquichain-payments-for-woocommerce'); ?></p>
        </div>
        <?php
    }

    /**
     * Show voucher selector on edit product category page.
     */
    public function voucherTaxonomyFieldOnEditPage($term)
    {
        $term_id = $term->term_id;
        $savedCategory = get_term_meta($term_id, '_liquichain_voucher_category', true);

        ?>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="_liquichain_voucher_category"><?php _e('Liquichain Voucher Category', 'liquichain-payments-for-woocommerce'); ?></label></th>
            <td>
                <select name="_liquichain_voucher_category" id="_liquichain_voucher_category" class="select">
                    <option value="">
                        <?php _e(
                            '--Please choose an option--',
                            'liquichain-payments-for-woocommerce'
                        ); ?></option>
                    <option value="no_category" <?php selected($savedCategory, 'no_category'); ?>>
                        <?php _e('No Category', 'liquichain-payments-for-woocommerce'); ?>
                    </option>
                    <option value="meal" <?php selected($savedCategory, 'meal'); ?>>
                        <?php _e('Meal', 'liquichain-payments-for-woocommerce'); ?>
                    </option>
                    <option value="eco" <?php selected($savedCategory, 'eco'); ?>>
                        <?php _e('Eco', 'liquichain-payments-for-woocommerce'); ?>
                    </option>
                    <option value="gift" <?php selected($savedCategory, 'gift'); ?>>
                        <?php _e('Gift', 'liquichain-payments-for-woocommerce'); ?>
                    </option>
                </select>
                <p class="description">
                    <?php _e(
                        'Select a voucher category to apply to all products with this category',
                        'liquichain-payments-for-woocommerce'
                    ); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save voucher category on product category meta term.
     */
    public function voucherTaxonomyCustomMetaSave($term_id)
    {

        $metaOption = filter_input(INPUT_POST, '_liquichain_voucher_category', FILTER_SANITIZE_STRING);

        update_term_meta($term_id, '_liquichain_voucher_category', $metaOption);
    }

    /**
     * Contents of the Liquichain options product tab.
     */
    public function liquichainOptionsProductTabContent()
    {
        ?>
        <div id='liquichain_options' class='panel woocommerce_options_panel'><div class='options_group'><?php
            $voucherSettings = get_option('liquichain_wc_gateway_voucher_settings');
            if(!$voucherSettings){
                $voucherSettings = get_option('liquichain_wc_gateway_mealvoucher_settings');
            }
            $defaultCategory = $voucherSettings
                    ? $voucherSettings['mealvoucher_category_default']
                    : Voucher::NO_CATEGORY;
            woocommerce_wp_select(
                [
                    'id' => Voucher::MOLLIE_VOUCHER_CATEGORY_OPTION,
                    'title' => __(
                        'Select the default products category',
                        'liquichain-payments-for-woocommerce'
                    ),
                    'label' => __(
                        'Products voucher category',
                        'liquichain-payments-for-woocommerce'
                    ),

                    'type' => 'select',
                    'options' => [
                        $defaultCategory => __('Same as default category', 'liquichain-payments-for-woocommerce'),
                        Voucher::NO_CATEGORY => __('No Category', 'liquichain-payments-for-woocommerce'),
                        Voucher::MEAL => __('Meal', 'liquichain-payments-for-woocommerce'),
                        Voucher::ECO => __('Eco', 'liquichain-payments-for-woocommerce'),
                        Voucher::GIFT => __('Gift', 'liquichain-payments-for-woocommerce'),

                    ],
                    'default' => $defaultCategory,
                    /* translators: Placeholder 1: Default order status, placeholder 2: Link to 'Hold Stock' setting */
                    'description' => sprintf(
                        __(
                            'In order to process it, all products in the order must have a category. To disable the product from voucher selection select "No category" option.',
                            'liquichain-payments-for-woocommerce'
                        )
                    ),
                    'desc_tip' => true,
                ]
              ); ?>
        </div>

        </div><?php
    }

    /**
     * Save the product voucher local category option.
     *
     * @param $post_id
     */
    public function saveProductVoucherOptionFields($post_id)
    {
        $option = filter_input(
            INPUT_POST,
            Voucher::MOLLIE_VOUCHER_CATEGORY_OPTION,
            FILTER_SANITIZE_STRING
        );
        $voucherCategory = $option ?? '';

        update_post_meta(
            $post_id,
            Voucher::MOLLIE_VOUCHER_CATEGORY_OPTION,
            $voucherCategory
        );
    }

    /**
     * Add dedicated voucher category field for variations.
     * Default is the same as the general voucher category
     * @param $loop
     * @param $variation_data
     * @param $variation
     */
    public function voucherFieldInVariations($loop, $variation_data, $variation)
    {
        $voucherSettings = get_option(
            'liquichain_wc_gateway_mealvoucher_settings'
        );
        $defaultCategory = $voucherSettings ?
            $voucherSettings['mealvoucher_category_default']
            : Voucher::NO_CATEGORY;
        woocommerce_wp_select(
            [
                'id' => 'voucher[' . $variation->ID . ']',
                'label' => __('Liquichain Voucher category', 'liquichain-payments-for-woocommerce'),
                'value' => get_post_meta($variation->ID, 'voucher', true),
                'options' => [
                    $defaultCategory => __('Same as default category', 'liquichain-payments-for-woocommerce'),
                    Voucher::NO_CATEGORY => __('No Category', 'liquichain-payments-for-woocommerce'),
                    Voucher::MEAL => __('Meal', 'liquichain-payments-for-woocommerce'),
                    Voucher::ECO => __('Eco', 'liquichain-payments-for-woocommerce'),
                    Voucher::GIFT => __('Gift', 'liquichain-payments-for-woocommerce'),
                ],
            ]
        );
    }

    /**
     * Save the voucher option in the variation product
     * @param $variation_id
     * @param $i
     */
    public function saveVoucherFieldVariations($variation_id, $i)
    {
        $optionName = 'voucher';
        $args = [$optionName => ['filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_REQUIRE_ARRAY]];
        $option = filter_input_array(INPUT_POST, $args);
        $voucherCategory = $option[$optionName][$variation_id] ?: null;

        if (isset($voucherCategory)) {
            update_post_meta($variation_id, $optionName, esc_attr($voucherCategory));
        }
    }

    public function addVoucherVariationData($variations)
    {
        $optionName = 'voucher';
        $variations[$optionName] = get_post_meta($variations[ 'variation_id' ], $optionName, true);
        return $variations;
    }
}
