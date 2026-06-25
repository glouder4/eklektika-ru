<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $property */
$propertyDisplayValue = $property['DISPLAY_VALUE'] ?? ($property['VALUE'] ?? '');
if (is_array($propertyDisplayValue)) {
    $propertyDisplayValue = implode(', ', array_map('strval', $propertyDisplayValue));
}
echo htmlspecialchars((string)$propertyDisplayValue);
