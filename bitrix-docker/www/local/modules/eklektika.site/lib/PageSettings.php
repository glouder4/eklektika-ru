<?php

namespace OnlineService\Site;

use OnlineService\Site\Config\SiteModuleConfig;

final class PageSettings
{
    /** @var array<string, self|null> */
    private static $instances = [];

    /** @var int */
    private $elementId;

    /** @var int */
    private $iblockId;

    /** @var array */
    private $properties = [];

    /** @var bool */
    private $loaded = false;

    private function __construct($elementId, $iblockId)
    {
        $this->elementId = $elementId;
        $this->iblockId = $iblockId;
        $this->load();
    }

    public static function getInstance($elementId, $iblockId = SiteModuleConfig::PAGE_SETTINGS_DEFAULT_IBLOCK_ID)
    {
        $elementId = (int) $elementId;
        $iblockId = (int) $iblockId;

        if ($elementId <= 0 || $iblockId <= 0) {
            return null;
        }

        $cacheKey = $iblockId . ':' . $elementId;

        if (!array_key_exists($cacheKey, self::$instances) || self::$instances[$cacheKey] === null) {
            $instance = new self($elementId, $iblockId);
            self::$instances[$cacheKey] = $instance->isLoaded() ? $instance : null;
        }

        return self::$instances[$cacheKey];
    }

    public function isLoaded()
    {
        return $this->loaded;
    }

    public function getValue($code, $default = null, $type = 'TEXT')
    {
        $code = trim($code);

        if ($code === '' || !$this->isLoaded()) {
            return $default;
        }

        if (!array_key_exists($code, $this->properties)) {
            return $default;
        }

        $property = $this->properties[$code];

        if (!is_array($property)) {
            return $property ?? $default;
        }

        $value = $property['VALUE'] ?? null;

        if (is_array($value)) {
            $filtered = array_filter($value, static function ($item) {
                if (is_array($item)) {
                    return !empty(array_filter($item, static function ($nested) {
                        return $nested !== null && $nested !== '' && $nested !== false;
                    }));
                }

                return $item !== null && $item !== '' && $item !== false;
            });

            return empty($filtered) ? $default : $filtered;
        }

        return ($value === null || $value === '' || $value === false) ? $default : $value;
    }

    public function getProperty($code)
    {
        $code = trim($code);

        if ($code === '' || !$this->isLoaded() || !array_key_exists($code, $this->properties)) {
            return null;
        }

        return $this->properties[$code];
    }

    public function all()
    {
        return $this->properties;
    }

    private function load()
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return;
        }

        $catalogElementResult = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $this->iblockId,
                'ID' => $this->elementId,
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            ['nTopCount' => 1],
            ['ID', 'IBLOCK_ID']
        );

        if ($catalogElementResult && ($catalogElementObject = $catalogElementResult->GetNextElement())) {
            $this->properties = $catalogElementObject->GetProperties();
            $this->loaded = true;
        }
    }
}
