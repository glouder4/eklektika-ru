<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Sale;
use OnlineService\Catalog\NanesenieOptionsResolver;

if (!Loader::includeModule('sale') || !Loader::includeModule('catalog') || !Loader::includeModule('iblock')) {
    http_response_code(500);
    echo 'Required modules are not loaded.';
    exit;
}

function excelColumnName($index)
{
    $name = '';
    while ($index >= 0) {
        $name = chr(($index % 26) + 65) . $name;
        $index = intdiv($index, 26) - 1;
    }
    return $name;
}

function xmlEscape($value)
{
    $value = (string)$value;
    if ($value === '') {
        return '';
    }
    if (function_exists('mb_convert_encoding')) {
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '';

    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function getAbsoluteUrl($url, $baseUrl)
{
    $url = trim((string)$url);
    if ($url === '' || $baseUrl === '') {
        return $url;
    }
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        return $url;
    }
    return $baseUrl . (strpos($url, '/') === 0 ? '' : '/') . $url;
}

$formatMoney = static function ($value) {
    $value = (float)$value;
    if (abs($value - round($value)) < 0.00001) {
        return (string)(int)round($value);
    }
    return number_format($value, 2, '.', '');
};

$decodeText = static function ($value) {
    $text = html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
};

$getPropertyValuesByCode = static function ($iblockId, $elementId, $propertyCode) {
    $values = [];
    $propRes = \CIBlockElement::GetProperty((int)$iblockId, (int)$elementId, ['sort' => 'asc'], ['CODE' => (string)$propertyCode]);
    while ($prop = $propRes->Fetch()) {
        $value = trim((string)($prop['VALUE_ENUM'] ?? ''));
        if ($value === '') {
            $value = trim((string)($prop['VALUE'] ?? ''));
        }
        if ($value !== '' && ctype_digit($value)) {
            $enum = \CIBlockPropertyEnum::GetByID((int)$value);
            if (is_array($enum) && !empty($enum['VALUE'])) {
                $value = trim((string)$enum['VALUE']);
            }
        }
        if ($value !== '') {
            $values[] = $value;
        }
    }
    if (empty($values)) {
        return '';
    }
    $values = array_values(array_unique($values));
    return implode(', ', $values);
};

$getElementSeoPageTitle = static function (int $iblockId, int $elementId) use ($decodeText): string {
    if ($elementId <= 0 || $iblockId <= 0 || !class_exists(\Bitrix\Iblock\InheritedProperty\ElementValues::class)) {
        return '';
    }

    try {
        $seo = new \Bitrix\Iblock\InheritedProperty\ElementValues($iblockId, $elementId);
        $values = $seo->getValues();

        return $decodeText($values['ELEMENT_PAGE_TITLE'] ?? ($values['ELEMENT_META_TITLE'] ?? ''));
    } catch (\Throwable $e) {
        return '';
    }
};

$getElementNameFromIblock = static function (int $elementId) use ($decodeText, $getElementSeoPageTitle): string {
    if ($elementId <= 0) {
        return '';
    }

    $res = \CIBlockElement::GetList([], ['ID' => $elementId], false, false, ['ID', 'NAME', 'IBLOCK_ID']);
    if (!$el = $res->GetNext()) {
        return '';
    }

    $name = $decodeText($el['~NAME'] ?? $el['NAME'] ?? '');
    if ($name !== '') {
        return $name;
    }

    return $getElementSeoPageTitle((int)($el['IBLOCK_ID'] ?? 0), $elementId);
};

$getCatalogProviderProductName = static function (int $productId) use ($decodeText): string {
    if ($productId <= 0 || !Loader::includeModule('catalog')) {
        return '';
    }
    if (!class_exists(\CCatalogProductProvider::class) || !method_exists(\CCatalogProductProvider::class, 'GetProductData')) {
        return '';
    }

    $data = \CCatalogProductProvider::GetProductData([
        'PRODUCT_ID' => $productId,
        'QUANTITY' => 1,
        'RENEW' => 'N',
        'CHECK_QUANTITY' => 'N',
        'CHECK_PRICE' => 'N',
        'CHECK_DISCOUNT' => 'N',
    ]);
    if (!is_array($data)) {
        return '';
    }

    return $decodeText($data['~NAME'] ?? $data['NAME'] ?? '');
};

$buildOfferVariantSuffix = static function (int $offerId) use ($getPropertyValuesByCode): string {
    $parts = [];
    foreach (['MATERIAL', 'TSVET'] as $propertyCode) {
        $value = $getPropertyValuesByCode(14, $offerId, $propertyCode);
        if ($value === '') {
            continue;
        }
        foreach (preg_split('/\s*,\s*/u', $value) ?: [] as $part) {
            $part = trim((string)$part);
            if ($part !== '') {
                $parts[] = $part;
            }
        }
    }
    if ($parts === []) {
        return '';
    }

    return ' (' . implode('; ', $parts) . ')';
};

$resolveCommercialOfferProductName = static function (int $offerId, int $parentProductId, string $basketName) use (
    $decodeText,
    $buildOfferVariantSuffix,
    $getCatalogProviderProductName,
    $getElementNameFromIblock
): string {
    $name = $getCatalogProviderProductName($offerId);
    if ($name === '') {
        $name = $getElementNameFromIblock($offerId);
    }
    if ($name === '') {
        $name = $decodeText($basketName);
    }

    $parentName = '';
    if ($parentProductId > 0 && $parentProductId !== $offerId) {
        $parentName = $getCatalogProviderProductName($parentProductId);
        if ($parentName === '') {
            $parentName = $getElementNameFromIblock($parentProductId);
        }
    }

    if ($parentName !== '' && $name !== '' && preg_match('/^\([^)]+\)$/u', $name)) {
        $name = $parentName . ' ' . $name;
    } elseif ($name === '' && $parentName !== '') {
        $name = $parentName;
    }

    $variantSuffix = $buildOfferVariantSuffix($offerId);
    if ($variantSuffix !== '' && $name !== '' && mb_stripos($name, $variantSuffix) === false && !preg_match('/\([^)]*;[^)]*\)\s*$/u', $name)) {
        $name .= $variantSuffix;
    } elseif ($name === '' && $parentName !== '' && $variantSuffix !== '') {
        $name = $parentName . $variantSuffix;
    }

    return $name;
};

$findProductNameByArticle = static function (string $article) use ($getCatalogProviderProductName, $getElementNameFromIblock): string {
    $article = trim($article);
    if ($article === '') {
        return '';
    }

    foreach ([14, 13] as $iblockId) {
        foreach (['CML2_ARTICLE', 'ARTIKUL'] as $propertyCode) {
            $res = \CIBlockElement::GetList(
                [],
                ['IBLOCK_ID' => $iblockId, 'PROPERTY_' . $propertyCode => $article],
                false,
                ['nTopCount' => 1],
                ['ID', 'IBLOCK_ID']
            );
            if (!$row = $res->Fetch()) {
                continue;
            }

            $elementId = (int)$row['ID'];
            $name = $getCatalogProviderProductName($elementId);
            if ($name !== '') {
                return $name;
            }

            $name = $getElementNameFromIblock($elementId);
            if ($name !== '') {
                return $name;
            }
        }
    }

    return '';
};

$wrapCellText = static function ($value, $lineLength = 28) use ($decodeText) {
    $text = $decodeText($value);
    if ($text === '') {
        return '';
    }

    if (!function_exists('mb_strlen') || !function_exists('mb_substr')) {
        return $text;
    }

    if (mb_strlen($text, 'UTF-8') <= $lineLength) {
        return $text;
    }

    $lines = [];
    while ($text !== '') {
        if (mb_strlen($text, 'UTF-8') <= $lineLength) {
            $lines[] = $text;
            break;
        }

        $chunk = mb_substr($text, 0, $lineLength, 'UTF-8');
        $breakPos = mb_strrpos($chunk, ' ', 0, 'UTF-8');
        if ($breakPos === false || $breakPos < (int)($lineLength * 0.4)) {
            $lines[] = $chunk;
            $text = mb_substr($text, $lineLength, null, 'UTF-8');
            continue;
        }

        $lines[] = mb_substr($chunk, 0, $breakPos, 'UTF-8');
        $text = ltrim(mb_substr($text, $breakPos, null, 'UTF-8'));
    }

    return implode("\n", $lines);
};

$getFirstFileByPropertyCode = static function ($iblockId, $elementId, $propertyCode) {
    $res = \CIBlockElement::GetProperty((int)$iblockId, (int)$elementId, ['sort' => 'asc'], ['CODE' => $propertyCode]);
    while ($prop = $res->Fetch()) {
        $fileId = (int)($prop['VALUE'] ?? 0);
        if ($fileId > 0) {
            $file = \CFile::GetFileArray($fileId);
            if (is_array($file) && !empty($file['SRC'])) {
                return (string)$file['SRC'];
            }
        }
    }
    return '';
};

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = (string)($_SERVER['HTTP_HOST'] ?? '');
$siteBaseUrl = $host !== '' ? ($scheme . '://' . $host) : '';

$offerDate = date('d.m.Y');
$rows = [
    ['', '', '', '', '', '', '', '', '', '', '', '', ''],
    ['Коммерческое предложение от ' . $offerDate, '', '', '', '', '', '', '', '', '', '', '', ''],
    ['', '', '', '', '', '', '', '', '', '', '', '', ''],
    ['', '', '', '', '', '', '', '', '', '', '', '', ''],
    ['Фото', 'Фото, ссылка', 'Артикул', 'Название', 'Описание', 'Цвет', 'Материал', 'Размер', 'Метод нанесения', 'Тираж', 'Цена за шт., руб.', 'Стоимость тиража, руб.', 'ИТОГ, руб.'],
];

$fuserId = Sale\Fuser::getId();
$basket = Sale\Basket::loadItemsForFUser($fuserId, SITE_ID);
$grandTotal = 0.0;

foreach ($basket->getBasketItems() as $basketItem) {
    if ((method_exists($basketItem, 'isDelay') && $basketItem->isDelay()) || (method_exists($basketItem, 'isSubscribe') && $basketItem->isSubscribe())) {
        continue;
    }

    $quantity = (float)$basketItem->getQuantity();
    if ($quantity <= 0) {
        continue;
    }

    $offerId = (int)$basketItem->getProductId();
    $price = (float)$basketItem->getPrice();
    $sum = $price * $quantity;

    $article = '';
    $detailUrl = '';
    $imageSrc = '';
    $description = '';
    $color = '';
    $material = '';
    $size = '';

    $parentProductId = $offerId;
    $skuInfo = \CCatalogSku::GetProductInfo($offerId);
    if (is_array($skuInfo) && !empty($skuInfo['ID'])) {
        $parentProductId = (int)$skuInfo['ID'];
    }

    $name = $resolveCommercialOfferProductName(
        $offerId,
        $parentProductId,
        (string)$basketItem->getField('NAME')
    );

    $articlePropRes = \CIBlockElement::GetProperty(14, $offerId, ['sort' => 'asc'], ['CODE' => 'CML2_ARTICLE']);
    if ($articleProp = $articlePropRes->Fetch()) {
        $article = (string)($articleProp['VALUE'] ?? '');
    }
    if ($article === '') {
        $articlePropRes = \CIBlockElement::GetProperty(14, $offerId, ['sort' => 'asc'], ['CODE' => 'ARTIKUL']);
        if ($articleProp = $articlePropRes->Fetch()) {
            $article = (string)($articleProp['VALUE'] ?? '');
        }
    }
    if ($name === '' && $article !== '') {
        $name = $findProductNameByArticle($article);
    }

    $elementRes = \CIBlockElement::GetList([], ['ID' => $parentProductId, 'ACTIVE' => 'Y'], false, false, ['ID', 'IBLOCK_ID', 'DETAIL_PAGE_URL', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'DETAIL_TEXT']);
    if ($element = $elementRes->GetNext()) {
        $detailUrl = (string)($element['DETAIL_PAGE_URL'] ?? '');
        $rawDescription = (string)($element['~DETAIL_TEXT'] ?? $element['DETAIL_TEXT'] ?? '');
        $description = $decodeText(strip_tags($rawDescription));

        $pictureId = (int)($element['PREVIEW_PICTURE'] ?: $element['DETAIL_PICTURE']);
        if ($pictureId > 0) {
            $file = \CFile::GetFileArray($pictureId);
            if (is_array($file) && !empty($file['SRC'])) {
                $imageSrc = (string)$file['SRC'];
            }
        }

        $elementIblockId = (int)($element['IBLOCK_ID'] ?? 0);
        if ($elementIblockId > 0) {
            $color = $getPropertyValuesByCode($elementIblockId, $parentProductId, 'TSVET');
            $material = $getPropertyValuesByCode($elementIblockId, $parentProductId, 'MATERIAL');
            $size = $getPropertyValuesByCode($elementIblockId, $parentProductId, 'RAZMERY');
        }
    }
    if ($color === '') {
        $color = $getPropertyValuesByCode(14, $offerId, 'TSVET');
    }
    if ($material === '') {
        $material = $getPropertyValuesByCode(14, $offerId, 'MATERIAL');
    }
    if ($size === '') {
        $size = $getPropertyValuesByCode(14, $offerId, 'RAZMERY');
    }

    if ($imageSrc === '') {
        $imageSrc = $getFirstFileByPropertyCode(13, $parentProductId, 'MORE_PHOTO');
    }
    if ($imageSrc === '') {
        $imageSrc = $getFirstFileByPropertyCode(13, $parentProductId, 'PHOTOS');
    }
    if ($imageSrc === '') {
        $offerRes = \CIBlockElement::GetList([], ['ID' => $offerId, 'ACTIVE' => 'Y'], false, false, ['ID', 'IBLOCK_ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE']);
        if ($offer = $offerRes->GetNext()) {
            $offerPictureId = (int)($offer['PREVIEW_PICTURE'] ?: $offer['DETAIL_PICTURE']);
            if ($offerPictureId > 0) {
                $file = \CFile::GetFileArray($offerPictureId);
                if (is_array($file) && !empty($file['SRC'])) {
                    $imageSrc = (string)$file['SRC'];
                }
            }
            if ($imageSrc === '') {
                $offerIblockId = (int)($offer['IBLOCK_ID'] ?? 14);
                $imageSrc = $getFirstFileByPropertyCode($offerIblockId, $offerId, 'PHOTOS');
            }
            if ($imageSrc === '') {
                $offerIblockId = (int)($offer['IBLOCK_ID'] ?? 14);
                $imageSrc = $getFirstFileByPropertyCode($offerIblockId, $offerId, 'MORE_PHOTO');
            }
        }
    }

    $colorFromBasket = '';
    $materialFromBasket = '';
    $propertyCollection = $basketItem->getPropertyCollection();
    $nanesenieValues = NanesenieOptionsResolver::extractSelectedFromPropertyCollection($propertyCollection);
    $nanesenie = implode(', ', $nanesenieValues);
    if ($propertyCollection) {
        foreach ($propertyCollection as $propertyItem) {
            $propertyCode = trim((string)$propertyItem->getField('CODE'));
            $propertyCodeUpper = strtoupper($propertyCode);
            $propertyValue = trim((string)$propertyItem->getField('VALUE'));
            if ($propertyCodeUpper === 'NANESENIE') {
                continue;
            }
            if ($propertyValue !== '' && ($propertyCodeUpper === 'TSVET' || strpos($propertyCodeUpper, 'PROP[77]') !== false || $propertyCodeUpper === 'PROP_77' || $propertyCodeUpper === '77')) {
                $colorFromBasket = $propertyValue;
                continue;
            }
            if ($propertyValue !== '' && ($propertyCodeUpper === 'MATERIAL' || strpos($propertyCodeUpper, 'PROP[35]') !== false || $propertyCodeUpper === 'PROP_35' || $propertyCodeUpper === '35')) {
                $materialFromBasket = $propertyValue;
            }
        }
    }
    if ($colorFromBasket !== '') {
        $color = $decodeText($colorFromBasket);
    }
    if ($materialFromBasket !== '') {
        $material = $decodeText($materialFromBasket);
    }

    $lineTotal = $formatMoney($sum);
    $rows[] = [
        getAbsoluteUrl($imageSrc, $siteBaseUrl),
        getAbsoluteUrl($imageSrc, $siteBaseUrl),
        $article,
        $wrapCellText($name, 26),
        $wrapCellText($description, 26),
        $wrapCellText($color, 16),
        $wrapCellText($material, 16),
        $wrapCellText($size, 16),
        $wrapCellText($nanesenie, 18),
        number_format($quantity, 0, '.', ''),
        $formatMoney($price),
        $lineTotal,
        $lineTotal,
    ];
    $grandTotal += $sum;
}

if (count($rows) === 5) {
    $rows[] = ['', '', '', 'Корзина пуста', '', '', '', '', '', '', '', '', ''];
}
$rows[] = ['', '', '', '', '', '', '', '', '', 'Итого', '', $formatMoney($grandTotal), $formatMoney($grandTotal)];

$totalRow = count($rows);
$sheetData = '';
foreach ($rows as $rowIndex => $row) {
    $excelRow = $rowIndex + 1;
    $isDataRow = ($excelRow >= 6 && $excelRow < $totalRow);
    $rowAttributes = ' r="' . $excelRow . '"';
    if ($isDataRow) {
        $rowAttributes .= ' ht="90" customHeight="1"';
    }
    $sheetData .= '<row' . $rowAttributes . '>';
    foreach ($row as $colIndex => $cellValue) {
        $styleId = 0;
        if ($excelRow === 2 && $colIndex === 0) {
            $styleId = 1;
        } elseif ($excelRow === 5) {
            $styleId = 2;
        } elseif ($excelRow === $totalRow) {
            $styleId = ($colIndex >= 10 && $colIndex <= 12) ? 5 : 4;
        } elseif ($excelRow >= 6 && $excelRow < $totalRow) {
            if ($colIndex >= 9 && $colIndex <= 12) {
                $styleId = 3;
            } else {
                $styleId = 6;
            }
        }
        $cellRef = excelColumnName($colIndex) . $excelRow;
        if ($isDataRow && $colIndex === 0 && (string)$cellValue !== '') {
            $formulaUrl = str_replace('"', '""', (string)$cellValue);
            $sheetData .= '<c r="' . $cellRef . '" s="' . $styleId . '"><f>IMAGE("' . xmlEscape($formulaUrl) . '",4,120,120)</f></c>';
            continue;
        }
        $sheetData .= '<c r="' . $cellRef . '" s="' . $styleId . '" t="inlineStr"><is><t xml:space="preserve">' . xmlEscape($cellValue) . '</t></is></c>';
    }
    $sheetData .= '</row>';
}

$stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="4"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="16"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFB0C4DE"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFECECEC"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color auto="1"/></left><right style="thin"><color auto="1"/></right><top style="thin"><color auto="1"/></top><bottom style="thin"><color auto="1"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="7"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/><xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';

$worksheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols><col min="1" max="1" width="18" customWidth="1"/><col min="2" max="2" width="40" customWidth="1"/><col min="3" max="3" width="14" customWidth="1"/><col min="4" max="4" width="38" customWidth="1"/><col min="5" max="5" width="28" customWidth="1"/><col min="6" max="8" width="16" customWidth="1"/><col min="9" max="9" width="28" customWidth="1"/><col min="10" max="10" width="10" customWidth="1"/><col min="11" max="13" width="20" customWidth="1"/></cols><sheetData>' . $sheetData . '</sheetData><mergeCells count="1"><mergeCell ref="A2:M2"/></mergeCells></worksheet>';

$contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';

$relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';

$workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Worksheet" sheetId="1" r:id="rId1"/></sheets></workbook>';

$workbookRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';

$coreXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>Eklektika</dc:creator><cp:lastModifiedBy>Eklektika</cp:lastModifiedBy></cp:coreProperties>';

$appXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>PHP</Application></Properties>';

$tmpFile = tempnam(sys_get_temp_dir(), 'komm_offer_');
$zip = new ZipArchive();
if ($tmpFile === false || $zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo 'Failed to create XLSX file.';
    exit;
}

$zip->addFromString('[Content_Types].xml', $contentTypesXml);
$zip->addFromString('_rels/.rels', $relsXml);
$zip->addFromString('xl/workbook.xml', $workbookXml);
$zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRelsXml);
$zip->addFromString('xl/styles.xml', $stylesXml);
$zip->addFromString('xl/worksheets/sheet1.xml', $worksheetXml);
$zip->addFromString('docProps/core.xml', $coreXml);
$zip->addFromString('docProps/app.xml', $appXml);
$zip->close();

$downloadFileName = 'kommercheskoe_predlozhenie - eklektika.xlsx';
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $downloadFileName . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: public');
readfile($tmpFile);
@unlink($tmpFile);
exit;  
  