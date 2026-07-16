<?php

if (!function_exists('ogMetaDebugEnabled')) {
    function ogMetaDebugEnabled(): bool
    {
        return isset($_GET['og_debug']) && (string)$_GET['og_debug'] === '1';
    }
}

if (!function_exists('ogMetaDebugLog')) {
    function ogMetaDebugLog(string $stage, array $data = []): void
    {
        if (!ogMetaDebugEnabled()) {
            return;
        }

        if (!isset($GLOBALS['OG_META_DEBUG']) || !is_array($GLOBALS['OG_META_DEBUG'])) {
            $GLOBALS['OG_META_DEBUG'] = [];
        }

        $GLOBALS['OG_META_DEBUG'][] = [
            'stage' => $stage,
            'data' => $data,
        ];
    }
}

if (!function_exists('ogMetaDebugRenderHtml')) {
    function ogMetaDebugRenderHtml(): string
    {
        if (!ogMetaDebugEnabled() || empty($GLOBALS['OG_META_DEBUG'])) {
            return '';
        }

        if (!function_exists('pre')) {
            return '';
        }

        ob_start();
        pre([
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'globals' => [
                'CATALOG_CURRENT_OFFER_ID' => $GLOBALS['CATALOG_CURRENT_OFFER_ID'] ?? null,
                'CATALOG_CURRENT_ELEMENT_ID' => $GLOBALS['CATALOG_CURRENT_ELEMENT_ID'] ?? null,
                'CATALOG_CURRENT_SECTION_ID' => $GLOBALS['CATALOG_CURRENT_SECTION_ID'] ?? null,
            ],
            'steps' => $GLOBALS['OG_META_DEBUG'],
        ]);

        return (string)ob_get_clean();
    }
}

if (!function_exists('resolveOgMetaEntityIds')) {
    /**
     * @return array{iblockId: int, elementId: int, sectionId: int, offerId: int}
     */
    function resolveOgMetaEntityIds(\CMain $application): array
    {
        $iblockId = (int)$application->GetPageProperty('DWSTROY_OG_TWITTER_IBLOCK_ID');
        $elementId = (int)$application->GetPageProperty('DWSTROY_OG_TWITTER_ELEMENT_ID');
        $sectionId = (int)$application->GetPageProperty('DWSTROY_OG_TWITTER_SECTION_ID');
        $offerId = (int)($GLOBALS['CATALOG_CURRENT_OFFER_ID'] ?? 0);

        if ($elementId <= 0 && !empty($GLOBALS['CATALOG_CURRENT_ELEMENT_ID'])) {
            $elementId = (int)$GLOBALS['CATALOG_CURRENT_ELEMENT_ID'];
        }
        if ($sectionId <= 0 && !empty($GLOBALS['CATALOG_CURRENT_SECTION_ID'])) {
            $sectionId = (int)$GLOBALS['CATALOG_CURRENT_SECTION_ID'];
        }
        if ($offerId <= 0) {
            $path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
            if (preg_match('#/offer/(\d+)#', $path, $m)) {
                $offerId = (int)$m[1];
            }
        }

        return [
            'iblockId' => $iblockId,
            'elementId' => $elementId,
            'sectionId' => $sectionId,
            'offerId' => $offerId,
        ];
    }
}

if (!function_exists('resolveOgMetaParentProductIdByOffer')) {
    function resolveOgMetaParentProductIdByOffer(int $offerId, int $offersIblockId = 14): int
    {
        if ($offerId <= 0 || !\Bitrix\Main\Loader::includeModule('iblock')) {
            return 0;
        }

        $propRes = \CIBlockElement::GetProperty(
            $offersIblockId,
            $offerId,
            ['sort' => 'asc'],
            ['CODE' => 'CML2_LINK']
        );
        if ($prop = $propRes->Fetch()) {
            return (int)($prop['VALUE'] ?? 0);
        }

        return 0;
    }
}

if (!function_exists('resolveOgMetaElementPictureUrlById')) {
    function resolveOgMetaElementPictureUrlById(int $elementId, string $field = 'DETAIL_PICTURE'): string
    {
        if ($elementId <= 0 || !\Bitrix\Main\Loader::includeModule('iblock')) {
            return '';
        }

        $elementRes = \CIBlockElement::GetList(
            [],
            ['ID' => $elementId],
            false,
            false,
            ['DETAIL_PICTURE', 'PREVIEW_PICTURE']
        );
        if ($element = $elementRes->GetNext()) {
            $fileId = resolveOgMetaPictureIdFromElementRow($element, $field);
            if ($fileId > 0) {
                return resolveOgMetaPictureAbsoluteUrl((string)\CFile::GetPath($fileId));
            }
        }

        return '';
    }
}

if (!function_exists('resolveOgMetaEntityName')) {
    /**
     * Имя сущности для подстановки {=this.Name} в шаблонах og:* из .section.php.
     * Источники: DWSTROY_* page properties (catalog-section.php / element.php) или GLOBALS каталога.
     */
    function resolveOgMetaEntityName(\CMain $application): string
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/catalog_list_item_properties.php';

        $ids = resolveOgMetaEntityIds($application);
        $iblockId = $ids['iblockId'];
        $elementId = $ids['elementId'];
        $sectionId = $ids['sectionId'];
        $offerId = $ids['offerId'];

        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return catalogStripSupplierArticleLabelsFromText(trim((string)$application->GetTitle()));
        }

        if ($elementId > 0) {
            if ($iblockId > 0 && class_exists(\Bitrix\Iblock\InheritedProperty\ElementValues::class)) {
                $ipropValues = (new \Bitrix\Iblock\InheritedProperty\ElementValues($iblockId, $elementId))->getValues();
                $pageTitle = trim((string)($ipropValues['ELEMENT_PAGE_TITLE'] ?? ''));
                if ($pageTitle !== '') { 
                    if ($offerId > 0) {
                        return catalogApplyPublicArtikulToTitle($pageTitle, $offerId);
                    }

                    return catalogStripSupplierArticleLabelsFromText($pageTitle);
                }
            }

            $elementRes = \CIBlockElement::GetList(
                [],
                ['ID' => $elementId],
                false,
                false,
                ['NAME']
            );
            if ($element = $elementRes->GetNext()) {
                $name = trim((string)($element['NAME'] ?? ''));
                if ($name !== '') {
                    return $name;
                }
            }
        }

        if ($sectionId > 0) {
            if ($iblockId > 0 && class_exists(\Bitrix\Iblock\InheritedProperty\SectionValues::class)) {
                $ipropValues = (new \Bitrix\Iblock\InheritedProperty\SectionValues($iblockId, $sectionId))->getValues();
                $pageTitle = trim((string)($ipropValues['SECTION_PAGE_TITLE'] ?? ''));
                if ($pageTitle !== '') {
                    return $pageTitle;
                }
            }

            $sectionFilter = ['ID' => $sectionId];
            if ($iblockId > 0) {
                $sectionFilter['IBLOCK_ID'] = $iblockId;
            }

            $sectionRes = \CIBlockSection::GetList([], $sectionFilter, false, ['NAME']);
            if ($section = $sectionRes->GetNext()) {
                $name = trim((string)($section['NAME'] ?? ''));
                if ($name !== '') {
                    return $name;
                }
            }
        }

        return catalogStripSupplierArticleLabelsFromText(trim((string)$application->GetTitle()));
    }
}

if (!function_exists('normalizeOgMetaAbsoluteUrl')) {
    /**
     * Убирает стандартные порты :80 / :443 из абсолютного URL.
     */
    function normalizeOgMetaAbsoluteUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        return (string)preg_replace('#^(https?://[^/]+):(443|80)(?=/|$)#i', '$1', $url);
    }
}

if (!function_exists('resolveOgMetaPictureAbsoluteUrl')) {
    function resolveOgMetaPictureAbsoluteUrl(string $fileSrc): string
    {
        $fileSrc = trim($fileSrc);
        if ($fileSrc === '') {
            return '';
        }

        if (strpos($fileSrc, 'http://') === 0 || strpos($fileSrc, 'https://') === 0) {
            return normalizeOgMetaAbsoluteUrl($fileSrc);
        }

        return normalizeOgMetaAbsoluteUrl((string)\CHTTP::URN2URI($fileSrc));
    }
}

if (!function_exists('resolveOgMetaCanonicalUrl')) {
    /**
     * Абсолютный URL текущей страницы для og:url (как canonical / dwstroy.opengraph).
     */
    function resolveOgMetaCanonicalUrl(\CMain $application): string
    {
        if (isset($GLOBALS['CANONICAL_URL']) && is_string($GLOBALS['CANONICAL_URL']) && $GLOBALS['CANONICAL_URL'] !== '') {
            $path = $GLOBALS['CANONICAL_URL'];
        } elseif (!empty($_SERVER['REAL_FILE_PATH'])) {
            $realPath = (string)$_SERVER['REAL_FILE_PATH'];
            $path = preg_match('#/index\.php$#', $realPath)
                ? (string)preg_replace('#/index\.php$#', '/', $realPath)
                : $realPath;
        } else {
            $path = (string)$application->GetCurPage(false);
        }

        $path = ($path === '/index.php' || $path === '') ? '/' : $path;
        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return normalizeOgMetaAbsoluteUrl($path);
        }

        return normalizeOgMetaAbsoluteUrl((string)\CHTTP::URN2URI($path));
    }
}

if (!function_exists('resolveOgMetaPictureIdFromSectionRow')) {
    function resolveOgMetaPictureIdFromSectionRow(array $row, string $field): int
    {
        $detailPictureId = (int)($row['DETAIL_PICTURE'] ?? 0);
        $previewPictureId = (int)($row['PICTURE'] ?? 0);

        if ($field === 'DETAIL_PICTURE') {
            return $detailPictureId > 0 ? $detailPictureId : $previewPictureId;
        }

        return $previewPictureId > 0 ? $previewPictureId : $detailPictureId;
    }
}

if (!function_exists('resolveOgMetaSectionPictureUrlById')) {
    function resolveOgMetaSectionPictureUrlById(int $sectionId, int $iblockId, string $field = 'DETAIL_PICTURE'): string
    {
        if ($sectionId <= 0 || !\Bitrix\Main\Loader::includeModule('iblock')) {
            return '';
        }

        $sectionFilter = ['ID' => $sectionId];
        if ($iblockId > 0) {
            $sectionFilter['IBLOCK_ID'] = $iblockId;
        }

        $sectionRes = \CIBlockSection::GetList([], $sectionFilter, false, ['ID', 'IBLOCK_ID', 'DETAIL_PICTURE', 'PICTURE', 'IBLOCK_SECTION_ID']);
        $section = $sectionRes->GetNext();
        if (!$section) {
            ogMetaDebugLog('section_not_found', ['sectionId' => $sectionId, 'filter' => $sectionFilter]);
            return '';
        }

        $fileId = resolveOgMetaPictureIdFromSectionRow($section, $field);
        ogMetaDebugLog('section_picture_row', [
            'sectionId' => $sectionId,
            'iblockId' => (int)($section['IBLOCK_ID'] ?? 0),
            'field' => $field,
            'detail_picture' => (int)($section['DETAIL_PICTURE'] ?? 0),
            'picture' => (int)($section['PICTURE'] ?? 0),
            'fileId' => $fileId,
        ]);

        if ($fileId > 0) {
            $url = resolveOgMetaPictureAbsoluteUrl((string)\CFile::GetPath($fileId));
            if ($url !== '') {
                return $url;
            }
        }

        $parentSectionId = (int)($section['IBLOCK_SECTION_ID'] ?? 0);
        if ($parentSectionId > 0) {
            return resolveOgMetaSectionPictureUrlById(
                $parentSectionId,
                (int)($section['IBLOCK_ID'] ?? $iblockId),
                $field
            );
        }

        return '';
    }
}

if (!function_exists('resolveOgMetaDwstroyLangCandidates')) {
    /**
     * @return list<string>
     */
    function resolveOgMetaDwstroyLangCandidates(\CMain $application): array
    {
        return array_values(array_unique(array_filter([
            trim((string)$application->GetPageProperty('DWSTROY_OG_TWITTER_TAB_CODE')),
            'catalog',
            defined('LANGUAGE_ID') ? (string)LANGUAGE_ID : '',
            preg_replace('/:(443|80)$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')),
        ])));
    }
}

if (!function_exists('resolveOgMetaPropertyToDwstroySuffix')) {
    function resolveOgMetaPropertyToDwstroySuffix(string $property): ?string
    {
        static $map = [
            'og:title' => 'TITLE',
            'og:description' => 'DESCRIPTION',
            'og:type' => 'TYPE',
            'og:image' => 'IMAGE',
            'og:image:secure_url' => 'IMAGE_SECURE_URL',
            'og:image:type' => 'IMAGE_TYPE',
            'og:image:width' => 'IMAGE_WIDTH',
            'og:image:height' => 'IMAGE_HEIGHT',
            'og:image:alt' => 'IMAGE_ALT',
            'og:locale' => 'LOCALE',
            'og:site_name' => 'SITE_NAME',
        ];

        return $map[$property] ?? null;
    }
}

if (!function_exists('resolveOgMetaDwstroyPropertyCodes')) {
    /**
     * @param array{iblockId: int, elementId: int, sectionId: int, offerId: int} $ids
     * @return list<string>
     */
    function resolveOgMetaDwstroyPropertyCodes(array $ids, string $suffix): array
    {
        $codes = [];

        if ($ids['elementId'] > 0) {
            $codes[] = 'ELEMENT_OG_' . $suffix;
        }
        if ($ids['sectionId'] > 0) {
            $codes[] = 'SECTION_OG_' . $suffix;
        }

        $codes[] = 'OG_' . $suffix;

        return array_values(array_unique($codes));
    }
}

if (!function_exists('resolveOgMetaDwstroyPropertyValueFromEntity')) {
    /**
     * @param list<string> $codeCandidates
     */
    function resolveOgMetaDwstroyPropertyValueFromEntity(object $iprop, array $codeCandidates): string
    {
        foreach ($codeCandidates as $code) {
            $value = trim(html_entity_decode((string)$iprop->getValue($code), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}

if (!function_exists('resolveOgMetaDwstroyPropertyValue')) {
    /**
     * Шаблон OG из админки раздела/товара (dwstroy.opengraph IPROPERTY), вкладка catalog.
     */
    function resolveOgMetaDwstroyPropertyValue(\CMain $application, string $property): string
    {
        if (!\Bitrix\Main\Loader::includeModule('dwstroy.opengraph')) {
            return '';
        }

        $suffix = resolveOgMetaPropertyToDwstroySuffix($property);
        if ($suffix === null) {
            return '';
        }

        $ids = resolveOgMetaEntityIds($application);
        if ($ids['iblockId'] <= 0) {
            return '';
        }

        $codeCandidates = resolveOgMetaDwstroyPropertyCodes($ids, $suffix);

        foreach (resolveOgMetaDwstroyLangCandidates($application) as $langId) {
            if ($ids['elementId'] > 0) {
                $iprop = new \Dwstroy\OpenGraph\InheritedProperty\ElementValues(
                    $ids['iblockId'],
                    $ids['elementId'],
                    $langId
                );
                $value = resolveOgMetaDwstroyPropertyValueFromEntity($iprop, $codeCandidates);
                if ($value !== '') {
                    ogMetaDebugLog('dwstroy_element_og', ['property' => $property, 'langId' => $langId, 'value' => $value]);
                    return $value;
                }
            }

            if ($ids['sectionId'] > 0) {
                $iprop = new \Dwstroy\OpenGraph\InheritedProperty\SectionValues(
                    $ids['iblockId'],
                    $ids['sectionId'],
                    $langId
                );
                $value = resolveOgMetaDwstroyPropertyValueFromEntity($iprop, $codeCandidates);
                if ($value !== '') {
                    ogMetaDebugLog('dwstroy_section_og', ['property' => $property, 'langId' => $langId, 'value' => $value]);
                    return $value;
                }
            }

            if (class_exists(\Dwstroy\OpenGraph\InheritedProperty\IblockValues::class)) {
                $iprop = new \Dwstroy\OpenGraph\InheritedProperty\IblockValues($ids['iblockId'], $langId);
                $value = resolveOgMetaDwstroyPropertyValueFromEntity($iprop, $codeCandidates);
                if ($value !== '') {
                    ogMetaDebugLog('dwstroy_iblock_og', ['property' => $property, 'langId' => $langId, 'value' => $value]);
                    return $value;
                }
            }
        }

        return '';
    }
}

if (!function_exists('resolveOgMetaDwstroyImageUrl')) {
    function resolveOgMetaDwstroyImageUrl(\CMain $application): string
    {
        $value = resolveOgMetaDwstroyPropertyValue($application, 'og:image');
        if ($value !== '' && strpos($value, '{=') === false) {
            return resolveOgMetaPictureAbsoluteUrl($value);
        }

        return '';
    }
}

if (!function_exists('resolveOgMetaPictureIdFromElementRow')) {
    function resolveOgMetaPictureIdFromElementRow(array $row, string $field): int
    {
        $detailPictureId = (int)($row['DETAIL_PICTURE'] ?? 0);
        $previewPictureId = (int)($row['PREVIEW_PICTURE'] ?? 0);

        if ($field === 'DETAIL_PICTURE') {
            return $detailPictureId > 0 ? $detailPictureId : $previewPictureId;
        }

        return $previewPictureId > 0 ? $previewPictureId : $detailPictureId;
    }
}

if (!function_exists('isOgMetaImageProperty')) {
    function isOgMetaImageProperty(string $property): bool
    {
        return in_array($property, ['og:image', 'og:image:secure_url', 'twitter:image:src'], true);
    }
}

if (!function_exists('resolveOgMetaOfferGalleryPictureUrl')) {
    function resolveOgMetaOfferGalleryPictureUrl(int $offerId): string
    {
        if ($offerId <= 0 || !\Bitrix\Main\Loader::includeModule('iblock')) {
            return '';
        }

        $offerRes = \CIBlockElement::GetList(
            [],
            ['ID' => $offerId],
            false,
            false,
            ['IBLOCK_ID']
        );
        $offer = $offerRes->GetNext();
        if (!$offer) {
            return '';
        }

        $propRes = \CIBlockElement::GetProperty(
            (int)$offer['IBLOCK_ID'],
            $offerId,
            ['sort' => 'asc'],
            ['CODE' => 'MORE_PHOTO']
        );
        while ($prop = $propRes->Fetch()) {
            $value = $prop['VALUE'] ?? null;
            if (is_array($value)) {
                $value = reset($value);
            }
            $fileId = (int)$value;
            if ($fileId > 0) {
                return resolveOgMetaPictureAbsoluteUrl((string)\CFile::GetPath($fileId));
            }
        }

        return '';
    }
}

if (!function_exists('resolveOgMetaEntityPictureUrl')) {
    /**
     * URL картинки оффера/элемента/раздела для og:image — подстановка {=DETAIL_PICTURE} / {=PREVIEW_PICTURE}.
     */
    function resolveOgMetaEntityPictureUrl(\CMain $application, string $field): string
    {
        $field = strtoupper(trim($field));
        if (!in_array($field, ['DETAIL_PICTURE', 'PREVIEW_PICTURE'], true)) {
            return '';
        }

        $ids = resolveOgMetaEntityIds($application);
        ogMetaDebugLog('resolveOgMetaEntityPictureUrl_ids', array_merge($ids, ['field' => $field]));
        $iblockId = $ids['iblockId'];
        $elementId = $ids['elementId'];
        $sectionId = $ids['sectionId'];
        $offerId = $ids['offerId'];

        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return '';
        }

        if ($offerId > 0) {
            $offerRes = \CIBlockElement::GetList(
                [],
                ['ID' => $offerId],
                false,
                false,
                ['DETAIL_PICTURE', 'PREVIEW_PICTURE']
            );
            $offer = $offerRes->GetNext();
            if ($offer) {
                $fileId = resolveOgMetaPictureIdFromElementRow($offer, $field);
                ogMetaDebugLog('offer_picture_row', [
                    'offerId' => $offerId,
                    'field' => $field,
                    'detail_picture' => (int)($offer['DETAIL_PICTURE'] ?? 0),
                    'preview_picture' => (int)($offer['PREVIEW_PICTURE'] ?? 0),
                    'fileId' => $fileId,
                ]);
                if ($fileId > 0) {
                    $url = resolveOgMetaPictureAbsoluteUrl((string)\CFile::GetPath($fileId));
                    ogMetaDebugLog('offer_picture_url', ['url' => $url]);
                    if ($url !== '') {
                        return $url;
                    }
                }
            } else {
                ogMetaDebugLog('offer_not_found', ['offerId' => $offerId]);
            }

            $galleryUrl = resolveOgMetaOfferGalleryPictureUrl($offerId);
            ogMetaDebugLog('offer_gallery_url', ['url' => $galleryUrl]);
            if ($galleryUrl !== '') {
                return $galleryUrl;
            }

            $parentId = resolveOgMetaParentProductIdByOffer($offerId);
            ogMetaDebugLog('offer_parent_id', ['parentId' => $parentId]);
            if ($parentId > 0) {
                $parentUrl = resolveOgMetaElementPictureUrlById($parentId, $field);
                ogMetaDebugLog('parent_picture_url', ['url' => $parentUrl]);
                if ($parentUrl !== '') {
                    return $parentUrl;
                }
            }
        }

        if ($elementId > 0) {
            $elementRes = \CIBlockElement::GetList(
                [],
                ['ID' => $elementId],
                false,
                false,
                ['DETAIL_PICTURE', 'PREVIEW_PICTURE']
            );
            if ($element = $elementRes->GetNext()) {
                $fileId = resolveOgMetaPictureIdFromElementRow($element, $field);
                if ($fileId > 0) {
                    return resolveOgMetaPictureAbsoluteUrl((string)\CFile::GetPath($fileId));
                }
            }
        }

        if ($sectionId > 0) {
            $sectionUrl = resolveOgMetaSectionPictureUrlById($sectionId, $iblockId, $field);
            if ($sectionUrl !== '') {
                return $sectionUrl;
            }
        }

        return '';
    }
}

if (!function_exists('applyOgMetaTemplate')) {
    function applyOgMetaTemplate(string $template, string $entityName): string
    {
        if ($template === '' || $entityName === '') {
            return $template;
        }

        return str_replace(
            ['{=this.Name}', '{=this.name}', '#NAME#'],
            $entityName,
            $template
        );
    }
}

if (!function_exists('applyOgMetaImageTemplates')) {
    function applyOgMetaImageTemplates(string $template, \CMain $application): string
    {
        if ($template === '' || strpos($template, '{=') === false) {
            return $template;
        }

        foreach (['DETAIL_PICTURE', 'PREVIEW_PICTURE'] as $field) {
            $placeholder = '{=' . $field . '}';
            if (strpos($template, $placeholder) === false) {
                continue;
            }

            $url = resolveOgMetaEntityPictureUrl($application, $field);
            if ($url !== '') {
                $template = str_replace($placeholder, $url, $template);
            }
        }

        return $template;
    }
}

if (!function_exists('resolveOgMetaTemplateViaIblockEngine')) {
    function resolveOgMetaTemplateViaIblockEngine(\CMain $application, string $template): string
    {
        if ($template === '' || strpos($template, '{=') === false) {
            return $template;
        }

        if (!\Bitrix\Main\Loader::includeModule('iblock') || !class_exists(\Bitrix\Iblock\Template\Engine::class)) {
            return $template;
        }

        $ids = resolveOgMetaEntityIds($application);

        $engineTemplate = str_replace(
            [
                '{=DETAIL_PICTURE}',
                '{=PREVIEW_PICTURE}',
            ],
            [
                '{=this.detail_picture}',
                '{=this.preview_picture}',
            ],
            $template
        );

        // У разделов в Bitrix поле картинки — picture, не preview_picture.
        if ($ids['sectionId'] > 0 && $ids['elementId'] <= 0 && $ids['offerId'] <= 0) {
            $engineTemplate = str_replace(
                ['{=this.preview_picture}', '{=this.detail_picture}'],
                ['{=this.picture}', '{=this.picture}'],
                $engineTemplate
            );
        }

        $entities = [];

        if ($ids['offerId'] > 0) {
            $entities[] = new \Bitrix\Iblock\Template\Entity\Element($ids['offerId']);
        }
        if ($ids['elementId'] > 0 && $ids['elementId'] !== $ids['offerId']) {
            $entities[] = new \Bitrix\Iblock\Template\Entity\Element($ids['elementId']);
        }
        if ($ids['sectionId'] > 0) {
            $entities[] = new \Bitrix\Iblock\Template\Entity\Section($ids['sectionId']);
        }

        foreach ($entities as $entity) {
            $resolved = trim((string)\Bitrix\Iblock\Template\Engine::process($entity, $engineTemplate));
            if ($resolved === '' || strpos($resolved, '{=') !== false) {
                continue;
            }

            if (strpos($resolved, '/') !== false || strpos($resolved, 'http') === 0) {
                return resolveOgMetaPictureAbsoluteUrl($resolved);
            }

            return $resolved;
        }

        return $template;
    }
}

if (!function_exists('normalizeOgMetaFieldTemplates')) {
    function normalizeOgMetaFieldTemplates(string $value, \CMain $application): string
    {
        if ($value === '') {
            return '';
        }

        if (strpos($value, '{=this.') !== false || strpos($value, '#NAME#') !== false) {
            $value = applyOgMetaTemplate($value, resolveOgMetaEntityName($application));
        }

        if (strpos($value, '{=') !== false) {
            $value = resolveOgMetaTemplateViaIblockEngine($application, $value);
        }

        if (strpos($value, '{=') !== false) {
            $value = applyOgMetaImageTemplates($value, $application);
        }

        if (strpos($value, '{=') !== false) {
            $value = resolveOgMetaTemplateViaIblockEngine($application, $value);
        }

        return trim($value);
    }
}

if (!function_exists('resolveOgMetaSectionDefaultImageUrl')) {
    /**
     * Заглушка og:image для разделов каталога без картинки.
     */
    function resolveOgMetaSectionDefaultImageUrl(\CMain $application): string
    {
        $ids = resolveOgMetaEntityIds($application);
        if ($ids['sectionId'] <= 0 || $ids['elementId'] > 0 || $ids['offerId'] > 0) {
            return '';
        }

        return resolveOgMetaPictureAbsoluteUrl('/assets/images/akcii/logo7.png');
    }
}

if (!function_exists('resolveOgMetaDirectImageUrl')) {
    function resolveOgMetaDirectImageUrl(\CMain $application): string
    {
        $dwstroyUrl = resolveOgMetaDwstroyImageUrl($application);
        if ($dwstroyUrl !== '') {
            return $dwstroyUrl;
        }

        $url = resolveOgMetaEntityPictureUrl($application, 'DETAIL_PICTURE');
        if ($url !== '') {
            return $url;
        }

        $url = resolveOgMetaEntityPictureUrl($application, 'PREVIEW_PICTURE');
        if ($url !== '') {
            return $url;
        }

        return resolveOgMetaSectionDefaultImageUrl($application);
    }
}

if (!function_exists('resolveOgMetaPropertyValue')) {
    /**
     * Значение og/twitter-свойства:
     * SetPageProperty → dwstroy IPROPERTY раздела/товара → GetDirProperty (.section.php) → подстановка шаблонов.
     */
    function resolveOgMetaPropertyValue(\CMain $application, string $property): string
    {
        $pageValue = trim((string)$application->GetPageProperty($property, ''));
        $dwstroyValue = resolveOgMetaDwstroyPropertyValue($application, $property);
        $dirValue = trim((string)$application->GetDirProperty($property));

        if ($pageValue !== '') {
            $value = $pageValue;
        } elseif ($dwstroyValue !== '') {
            $value = $dwstroyValue;
        } else {
            $value = $dirValue;
        }

        if ($value === '' && isOgMetaImageProperty($property)) {
            $value = '{=DETAIL_PICTURE}';
        }

        if ($value === '' && $property === 'og:url') {
            $value = resolveOgMetaCanonicalUrl($application);
        }

        if ($value === '') {
            ogMetaDebugLog('resolveOgMetaPropertyValue_empty', [
                'property' => $property,
                'pageValue' => $pageValue,
                'dwstroyValue' => $dwstroyValue,
                'dirValue' => $dirValue,
            ]);
            return '';
        }

        $normalized = normalizeOgMetaFieldTemplates($value, $application);

        if (isOgMetaImageProperty($property) && ($normalized === '' || strpos($normalized, '{=') !== false)) {
            $normalized = resolveOgMetaDirectImageUrl($application);
        }

        if ($property === 'og:url' && $normalized !== '') {
            $normalized = normalizeOgMetaAbsoluteUrl($normalized);
        }

        if ($property === 'og:image:secure_url' && $normalized !== '' && strpos($normalized, 'https://') !== 0) {
            $normalized = preg_replace('#^http://#', 'https://', $normalized) ?? $normalized;
        }

        if ($property === 'og:image') {
            ogMetaDebugLog('resolveOgMetaPropertyValue_og_image', [
                'pageValue' => $pageValue,
                'dwstroyValue' => $dwstroyValue,
                'dirValue' => $dirValue,
                'template' => $value,
                'normalized' => $normalized,
                'ids' => resolveOgMetaEntityIds($application),
            ]);
        }

        return $normalized;
    }
}

if (!function_exists('ensureOpenGraphImageMetaTag')) {
    function ensureOpenGraphImageMetaTag(string $content): string
    {
        $hasTag = (bool)preg_match('/<meta\s+property="og:image"\s+/i', $content);
        global $APPLICATION;
        $imageUrl = resolveOgMetaDirectImageUrl($APPLICATION);

        ogMetaDebugLog('ensureOpenGraphImageMetaTag', [
            'hasTagBefore' => $hasTag,
            'imageUrl' => $imageUrl,
            'hasHeadClose' => (bool)preg_match('/<\/head>/i', $content),
        ]);

        if ($hasTag) {
            return $content;
        }

        if ($imageUrl === '') {
            return $content;
        }

        $tag = '<meta property="og:image" content="' . htmlspecialcharsbx($imageUrl) . '" />' . "\n";
        if (preg_match('/<\/head>/i', $content)) {
            return (string)preg_replace('/<\/head>/i', $tag . '</head>', $content, 1);
        }

        return $content;
    }
}

if (!function_exists('normalizeOgMetaTextValue')) {
    function normalizeOgMetaTextValue(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/["\'\x{00AB}\x{00BB}\x{201C}\x{201D}\x{2018}\x{2019}]/u', '', $value);

        return (string)preg_replace('/\s+/u', ' ', trim($value));
    }
}
if (!function_exists('stripDuplicateOpenGraphMetaTags')) {
    /**
     * Подставляет шаблоны в og/twitter meta и убирает только дубли.
     */
    function stripDuplicateOpenGraphMetaTags(string $content): string
    {
        global $APPLICATION;

        $pattern = '/<meta\s+property="((?:og|twitter):[^"]+)"\s+content="([^"]*)"\s*\/?>\s*/i';
        if (!preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return $content;
        }

        $tags = [];
        foreach ($matches[0] as $i => $fullMatch) {
            $property = (string)$matches[1][$i][0];
            $rawContent = html_entity_decode((string)$matches[2][$i][0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $resolvedContent = $rawContent;

            if (strpos($rawContent, '{=') !== false || strpos($rawContent, '#NAME#') !== false) {
                $resolvedContent = normalizeOgMetaFieldTemplates($rawContent, $APPLICATION);
                if ($resolvedContent === '' || strpos($resolvedContent, '{=') !== false) {
                    $resolvedContent = resolveOgMetaPropertyValue($APPLICATION, $property);
                }
            }

            if (
                ($resolvedContent === '' || strpos($resolvedContent, '{=') !== false)
                && isOgMetaImageProperty($property)
            ) {
                $directUrl = resolveOgMetaDirectImageUrl($APPLICATION);
                if ($directUrl !== '') {
                    $resolvedContent = $directUrl;
                }
            }

            $tags[] = [
                'full' => $fullMatch[0],
                'offset' => (int)$fullMatch[1],
                'property' => $property,
                'content' => $resolvedContent,
                'hasMask' => ($resolvedContent === '' || strpos($resolvedContent, '{=') !== false || strpos($resolvedContent, '#NAME#') !== false),
            ];
        }

        $replacements = [];
        foreach ($tags as $tag) {
            if (!$tag['hasMask']) {
                $replacements[$tag['offset']] = [
                    'full' => $tag['full'],
                    'resolved' => '<meta property="' . htmlspecialcharsbx($tag['property']) . '" content="' . htmlspecialcharsbx($tag['content']) . '" />' . "\n",
                ];
            }
        }

        krsort($replacements, SORT_NUMERIC);
        foreach ($replacements as $offset => $data) {
            $content = substr_replace($content, $data['resolved'], $offset, strlen($data['full']));
        }

        if (!preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return $content;
        }

        $tags = [];
        foreach ($matches[0] as $i => $fullMatch) {
            $decodedContent = html_entity_decode((string)$matches[2][$i][0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $tags[] = [
                'full' => $fullMatch[0],
                'offset' => (int)$fullMatch[1],
                'property' => (string)$matches[1][$i][0],
                'hasMask' => (strpos($decodedContent, '{=') !== false || strpos($decodedContent, '#NAME#') !== false),
            ];
        }

        $removeOffsets = [];
        $byProperty = [];
        foreach ($tags as $idx => $tag) {
            $byProperty[$tag['property']][] = $idx;
        }

        foreach ($byProperty as $indices) {
            if (count($indices) <= 1) {
                continue;
            }

            $keepIdx = null;
            foreach ($indices as $idx) {
                if (!$tags[$idx]['hasMask']) {
                    $keepIdx = $idx;
                    break;
                }
            }
            if ($keepIdx === null) {
                $keepIdx = $indices[count($indices) - 1];
            }

            foreach ($indices as $idx) {
                if ($idx !== $keepIdx) {
                    $removeOffsets[$tags[$idx]['offset']] = $tags[$idx]['full'];
                }
            }
        }

        if ($removeOffsets === []) {
            return $content;
        }

        krsort($removeOffsets, SORT_NUMERIC);
        foreach ($removeOffsets as $offset => $full) {
            $content = substr_replace($content, '', $offset, strlen($full));
        }

        return $content;
    }
}

if (!function_exists('disableDwstroyOpenGraphFrontendMeta')) {
    function disableDwstroyOpenGraphFrontendMeta(): void
    {
        if (!\Bitrix\Main\Loader::includeModule('dwstroy.opengraph')) {
            return;
        }

        \Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnEpilog',
            'dwstroy.opengraph',
            '\Dwstroy\OpenGraph\COpenGraph',
            'OnEpilog'
        );
    }
}
