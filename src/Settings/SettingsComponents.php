<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\Settings;

use Liquichain\WooCommerce\Components\StylesPropertiesDictionary;

class SettingsComponents
{

    /**
     * @var string[]
     */
    public const STYLE_KEY_PREFIXES = [
        'invalid_',
    ];
    /**
     * @var string
     */
    protected $pluginPath;

    /**
     * SettingsComponents constructor.
     */
    public function __construct(string $pluginPath)
    {
        $this->pluginPath = $pluginPath;
    }

    public function styles()
    {
        $defaults = $this->defaultSettings();
        $settings = [];

        $settings[StylesPropertiesDictionary::BASE_STYLE_KEY] = $this->optionsFor(
            StylesPropertiesDictionary::STYLES_OPTIONS_KEYS_MAP,
            $defaults
        );
        $settings[StylesPropertiesDictionary::INVALID_STYLE_KEY] = $this->optionsFor(
            StylesPropertiesDictionary::INVALID_STYLES_OPTIONS_KEYS_MAP,
            $defaults
        );

        return $settings;
    }

    protected function optionsFor($group, $defaults)
    {
        $settings = [];

        foreach ($group as $key) {
            $styleKey = str_replace(self::STYLE_KEY_PREFIXES, '', $key);
            $optionValue = get_option(
                sprintf('liquichain_components_%s', $key),
                $this->defaultOptionFor($defaults, $key)
            );
            $settings[$styleKey] = $optionValue;
        }

        return $settings;
    }

    protected function defaultSettings()
    {
        $liquichainComponentsFilePath = $this->pluginPath . '/inc/settings/liquichain_components.php';

        if (!file_exists($liquichainComponentsFilePath)) {
            return [];
        }

        $componentsFields = include $liquichainComponentsFilePath;

        return (array)$componentsFields;
    }

    protected function defaultOptionFor($options, $key)
    {
        return isset($options[$key]['default']) ? $options[$key]['default'] : null;
    }
}
