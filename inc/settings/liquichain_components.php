<?php

use Liquichain\WooCommerce\Components\StylesPropertiesDictionary;

return [
    [
        'type' => 'title',
        'id' => 'liquichain_components_styles',
        'title' => _x(
            'Base Styles',
            'Liquichain Components Settings',
            'liquichain-payments-for-woocommerce'
        ),
    ],
    StylesPropertiesDictionary::BACKGROUND_COLOR => [
        'type' => 'color',
        'id' => 'liquichain_components_' . StylesPropertiesDictionary::BACKGROUND_COLOR,
        'title' => _x('Background Color', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
        'default' => '#ffffff',
    ],
    StylesPropertiesDictionary::TEXT_COLOR => [
        'type' => 'color',
        'id' => 'liquichain_components_' . StylesPropertiesDictionary::TEXT_COLOR,
        'title' => _x('Text Color', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
        'default' => '#000000',
    ],
    StylesPropertiesDictionary::INPUT_PLACEHOLDER => [
        'type' => 'color',
        'id' => 'liquichain_components_' . StylesPropertiesDictionary::INPUT_PLACEHOLDER,
        'title' => _x('Placeholder Color', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
        'default' => '#cccccc',
    ],
    StylesPropertiesDictionary::FONT_SIZE => [
        'type' => 'text',
        'id' => 'liquichain_components_' . StylesPropertiesDictionary::FONT_SIZE,
        'title' => _x('Font Size', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
        'desc_tip' => _x(
            'Defines the component font size. Allowed units: \'em\', \'px\', \'rem\'.',
            'Liquichain Components Settings',
            'liquichain-payments-for-woocommerce'
        ),
        'default' => '16px',
    ],
    StylesPropertiesDictionary::FONT_WEIGHT => [
        'type' => 'select',
        'id' => 'liquichain_components_' . StylesPropertiesDictionary::FONT_WEIGHT,
        'title' => _x('Font Weight', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
        'default' => 'normal',
        'options' => [
            'lighter' => _x('Lighter', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
            'normal' => _x('Regular', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
            'bolder' => _x('Bold', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
        ],
    ],
    StylesPropertiesDictionary::LETTER_SPACING => [
        'type' => 'number',
        'id' => 'liquichain_components_' . StylesPropertiesDictionary::LETTER_SPACING,
        'title' => _x('Letter Spacing', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
        'default' => '0',
    ],
    StylesPropertiesDictionary::LINE_HEIGHT => [
        'type' => 'number',
        'id' => 'liquichain_components_' . StylesPropertiesDictionary::LINE_HEIGHT,
        'title' => _x('Line Height', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
        'default' => '1.2',
        'custom_attributes' => [
            'step' => '.1',
        ],
    ],
    StylesPropertiesDictionary::PADDING => [
        'type' => 'text',
        'id' => 'liquichain_components_' . StylesPropertiesDictionary::PADDING,
        'title' => _x('Padding', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
        'desc_tip' => _x(
            'Add padding to the components. Allowed units include `16px 16px 16px 16px` and `em`, `px`, `rem`.',
            'Liquichain Components Settings',
            'liquichain-payments-for-woocommerce'
        ),
        'default' => '.63em',
    ],
    StylesPropertiesDictionary::TEXT_ALIGN => [
        'type' => 'select',
        'id' => 'liquichain_components_' . StylesPropertiesDictionary::TEXT_ALIGN,
        'title' => _x('Align Text', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
        'default' => 'left',
        'options' => [
            'left' => _x('Left', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
            'right' => _x('Right', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
            'center' => _x('Center', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
            'justify' => _x('Justify', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
        ],
    ],
    StylesPropertiesDictionary::TEXT_TRANSFORM => [
        'type' => 'select',
        'id' => 'liquichain_components_' . StylesPropertiesDictionary::TEXT_TRANSFORM,
        'title' => _x('Transform Text ', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
        'default' => 'none',
        'options' => [
            'none' => _x(
                'None',
                'Liquichain Components Settings',
                'liquichain-payments-for-woocommerce'
            ),
            'capitalize' => _x(
                'Capitalize',
                'Liquichain Components Settings',
                'liquichain-payments-for-woocommerce'
            ),
            'uppercase' => _x(
                'Uppercase',
                'Liquichain Components Settings',
                'liquichain-payments-for-woocommerce'
            ),
            'lowercase' => _x(
                'Lowercase',
                'Liquichain Components Settings',
                'liquichain-payments-for-woocommerce'
            ),
            'full-width' => _x(
                'Full Width',
                'Liquichain Components Settings',
                'liquichain-payments-for-woocommerce'
            ),
            'full-size-kana' => _x(
                'Full Size Kana',
                'Liquichain Components Settings',
                'liquichain-payments-for-woocommerce'
            ),
        ],
    ],
    [
        'type' => 'sectionend',
        'id' => 'liquichain_components_styles',
    ],
    [
        'type' => 'title',
        'id' => 'liquichain_components_invalid_styles',
        'title' => _x(
            'Invalid Status Styles',
            'Liquichain Components Settings',
            'liquichain-payments-for-woocommerce'
        ),
    ],
    StylesPropertiesDictionary::INVALID_TEXT_COLOR => [
        'type' => 'color',
        'id' => 'liquichain_components_' . StylesPropertiesDictionary::INVALID_TEXT_COLOR,
        'title' => _x('Text Color', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
        'desc_tip' => _x(
            'Text Color for invalid input.',
            'Liquichain Components Settings',
            'liquichain-payments-for-woocommerce'
        ),
        'default' => '#000000',
    ],
    StylesPropertiesDictionary::INVALID_BACKGROUND_COLOR => [
        'type' => 'color',
        'id' => 'liquichain_components_' . StylesPropertiesDictionary::INVALID_BACKGROUND_COLOR,
        'title' => _x('Background Color', 'Liquichain Components Settings', 'liquichain-payments-for-woocommerce'),
        'desc_tip' => _x(
            'Background Color for invalid input.',
            'Liquichain Components Settings',
            'liquichain-payments-for-woocommerce'
        ),
        'default' => '#FFF0F0',
    ],
    [
        'type' => 'sectionend',
        'id' => 'liquichain_components_invalid_styles',
    ],
];
