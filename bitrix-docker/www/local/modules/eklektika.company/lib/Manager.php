<?php
namespace OnlineService\Site;

/**
 * Карточка менеджера в инфоблоке: связь с CRM по свойству {@see self::PROPERTY_BITRIX24_ID}.
 * Номер ИБ и код свойства согласованы с {@see \OnlineService\B24\UserSync\Config\UserSyncConfig::MANAGER_CARD_IBLOCK_ID}.
 */
class Manager
{
    /** @see \OnlineService\B24\UserSync\Config\UserSyncConfig::MANAGER_CARD_IBLOCK_ID */
    private const IBLOCK_ID = 24;

    /** @see \OnlineService\B24\UserSync\Config\UserSyncConfig::MANAGER_CARD_BITRIX24_PROPERTY_CODE */
    private const PROPERTY_BITRIX24_ID = 'BITRIX24_ID';

    /**
     * Входящий `UPDATE_MANAGER`: создать/обновить элемент; фото с портала CRM (`URL_B24` + относительный путь).
     *
     * @param array<string, mixed> $fields плоский payload после {@see \OnlineService\Sync\FromCrm\InboundGateway::normalizeInboundEnvelope()}
     */
    public function update(array $fields): bool
    {
        if (!\CModule::IncludeModule('iblock')) {
            return false;
        }

        $b24RefRaw = $fields['BITRIX24_ID'] ?? $fields['ID'] ?? null;
        if ($b24RefRaw === null || $b24RefRaw === '') {
            return false;
        }
        $b24Ref = \trim((string) $b24RefRaw);
        if ($b24Ref === '') {
            return false;
        }

        $isPersonalManager = $this->isPersonalManagerPayloadEnabled($fields);
        $elementId = $this->findElementByBitrix24Reference($b24Ref);

        if (!$isPersonalManager) {
            if ($elementId <= 0) {
                return true;
            }

            return $this->persistManagerFields($elementId, $fields, $b24Ref, 'N');
        }

        if ($elementId <= 0) {
            return $this->createManagerElement($fields, $b24Ref);
        }

        return $this->persistManagerFields($elementId, $fields, $b24Ref, 'Y');
    }

    /**
     * Нет ключа — как раньше (считаем активным); иначе явный флаг CRM.
     *
     * @param array<string, mixed> $fields
     */
    private function isPersonalManagerPayloadEnabled(array $fields): bool
    {
        if (!\array_key_exists('IS_PERSONAL_MANAGER', $fields)) {
            return true;
        }

        return $this->crmTruthy($fields['IS_PERSONAL_MANAGER']);
    }

    private function crmTruthy(mixed $v): bool
    {
        return $v === true || $v === 1 || $v === '1' || $v === 'Y' || $v === 'y'
            || $v === 'Да' || $v === 'да' || $v === 'on' || $v === 'ON';
    }

    private function buildDisplayName(array $fields): string
    {
        $name = \trim((string)($fields['NAME'] ?? ''));
        if ($name !== '') {
            // В inbound payload от n8n/CRM поле NAME может уже содержать полное ФИО.
            return $name;
        }

        return \trim((string)($fields['LAST_NAME'] ?? ''));
    }

    /**
     * Поиск по свойству BITRIX24_ID (строка и числовая форма), затем legacy {@see XML_ID}.
     */
    private function findElementByBitrix24Reference(string $ref): int
    {
        $ref = \trim($ref);
        if ($ref === '') {
            return 0;
        }

        $tries = [$ref];
        $asInt = (string)(int)$ref;
        if ($asInt === $ref || $asInt !== '0') {
            $tries[] = $asInt;
        }
        $tries = \array_values(\array_unique($tries));

        foreach ($tries as $try) {
            if ($try === '') {
                continue;
            }
            $rs = \CIBlockElement::GetList(
                ['ID' => 'ASC'],
                [
                    'IBLOCK_ID' => self::IBLOCK_ID,
                    'PROPERTY_' . self::PROPERTY_BITRIX24_ID => $try,
                ],
                false,
                ['nTopCount' => 1],
                ['ID']
            );
            if ($row = $rs->GetNext()) {
                $id = (int)($row['ID'] ?? 0);

                return $id > 0 ? $id : 0;
            }
        }

        $rs = \CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID' => self::IBLOCK_ID,
                'XML_ID' => $ref,
            ],
            false,
            ['nTopCount' => 1],
            ['ID']
        );
        if ($row = $rs->GetNext()) {
            $id = (int)($row['ID'] ?? 0);

            return $id > 0 ? $id : 0;
        }

        return 0;
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function createManagerElement(array $fields, string $b24Ref): bool
    {
        $name = $this->buildDisplayName($fields);
        if ($name === '') {
            $name = $b24Ref;
        }

        $el = new \CIBlockElement();
        $arFields = [
            'IBLOCK_ID' => self::IBLOCK_ID,
            'NAME' => $name,
            'ACTIVE' => 'Y',
            'XML_ID' => $b24Ref,
            'PROPERTY_VALUES' => [
                self::PROPERTY_BITRIX24_ID => $b24Ref,
                'PHONE' => (string)($fields['PHONE'] ?? ''),
                'EMAIL' => (string)($fields['EMAIL'] ?? ''),
                'WORK_POSITION' => (string)($fields['POSITION'] ?? ''),
            ],
        ];

        $photoArray = null;
        if (!empty($fields['PERSONAL_PHOTO'])) {
            $photoArray = $this->downloadPhoto((string)$fields['PERSONAL_PHOTO']);
            if ($photoArray) {
                $arFields['PREVIEW_PICTURE'] = $photoArray;
            }
        }

        $elementId = $el->Add($arFields);

        if ($photoArray && isset($photoArray['tmp_name']) && \is_string($photoArray['tmp_name']) && \file_exists($photoArray['tmp_name'])) {
            \unlink($photoArray['tmp_name']);
        }

        return $elementId && (int)$elementId > 0;
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function persistManagerFields(int $elementId, array $fields, string $b24Ref, string $activeYn): bool
    {
        $name = $this->buildDisplayName($fields);
        if ($name === '') {
            $name = $b24Ref;
        }

        $el = new \CIBlockElement();
        $updateMain = [
            'NAME' => $name,
            'ACTIVE' => $activeYn === 'Y' ? 'Y' : 'N',
        ];

        $photoArray = null;
        if (!empty($fields['PERSONAL_PHOTO'])) {
            $photoArray = $this->downloadPhoto((string)$fields['PERSONAL_PHOTO']);
            if ($photoArray) {
                $updateMain['PREVIEW_PICTURE'] = $photoArray;
            }
        }

        $ok = (bool)$el->Update($elementId, $updateMain);

        if ($photoArray && isset($photoArray['tmp_name']) && \is_string($photoArray['tmp_name']) && \file_exists($photoArray['tmp_name'])) {
            \unlink($photoArray['tmp_name']);
        }

        if (!$ok) {
            return false;
        }

        \CIBlockElement::SetPropertyValues($elementId, self::IBLOCK_ID, $b24Ref, self::PROPERTY_BITRIX24_ID);
        \CIBlockElement::SetPropertyValues($elementId, self::IBLOCK_ID, (string)($fields['PHONE'] ?? ''), 'PHONE');
        \CIBlockElement::SetPropertyValues($elementId, self::IBLOCK_ID, (string)($fields['EMAIL'] ?? ''), 'EMAIL');
        \CIBlockElement::SetPropertyValues($elementId, self::IBLOCK_ID, (string)($fields['POSITION'] ?? ''), 'WORK_POSITION');

        return true;
    }

    /**
     * База как у {@see URL_B24} в `php_interface/init.php` (прод: https://bitrix.eklektika.ru/).
     */
    private function managerPhotoBaseUrl(): string
    {
        if (\defined('URL_B24')) {
            return \rtrim((string)\constant('URL_B24'), '/');
        }

        return 'https://bitrix.eklektika.ru';
    }

    /**
     * Относительный путь `/upload/...` или полный URL — для скачивания превью.
     */
    private function buildManagerPhotoAbsoluteUrl(string $photoInput): string
    {
        $s = \trim($photoInput);
        if ($s === '') {
            return '';
        }
        if (\preg_match('#^https?://#i', $s)) {
            return $s;
        }

        return $this->managerPhotoBaseUrl() . '/' . \ltrim($s, '/');
    }

    /**
     * Скачивание файла превью по URL (абсолютный или собранный из базы CRM).
     *
     * @return array<string, mixed>|false
     */
    private function downloadPhoto(string $photoUrl)
    {
        $tempFile = null;
        try {
            $absolute = $this->buildManagerPhotoAbsoluteUrl($photoUrl);
            if ($absolute === '') {
                return false;
            }
            if (!\preg_match('#^(https?://[^/]+)(/.*)?$#i', $absolute, $mm)) {
                return false;
            }
            $origin = $mm[1];
            $pathOnly = isset($mm[2]) ? (string)$mm[2] : '/';
            $segments = \explode('/', \trim($pathOnly, '/'));
            $fileName = \array_pop($segments);
            if ($fileName === null || $fileName === '') {
                return false;
            }
            $segments[] = \rawurlencode($fileName);
            $encodedPhotoUrl = $origin . '/' . \implode('/', $segments);

            $httpClient = new \Bitrix\Main\Web\HttpClient();
            $httpClient->setTimeout(30);

            $fileExtension = \pathinfo($fileName, \PATHINFO_EXTENSION);
            $tempFile = \tempnam(\sys_get_temp_dir(), 'mgr_ph_') . '.' . ($fileExtension !== '' ? $fileExtension : 'bin');
            $downloadResult = $httpClient->download($encodedPhotoUrl, $tempFile);

            if ($downloadResult && \file_exists($tempFile)) {
                $mimeType = $this->getMimeType($tempFile, $fileExtension);

                return [
                    'name' => 'manager_photo_' . \time() . '.' . ($fileExtension !== '' ? $fileExtension : 'bin'),
                    'size' => \filesize($tempFile),
                    'tmp_name' => $tempFile,
                    'type' => $mimeType,
                    'old_file' => '',
                    'del' => '',
                    'MODULE_ID' => 'iblock',
                ];
            }
        } catch (\Throwable $e) {
            // ignore
        }
        if ($tempFile !== null && \is_string($tempFile) && \file_exists($tempFile)) {
            \unlink($tempFile);
        }

        return false;
    }

    /**
     * @return string
     */
    private function getMimeType(string $filePath, string $extension): string
    {
        if (\function_exists('finfo_open')) {
            $finfo = \finfo_open(\FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mimeType = \finfo_file($finfo, $filePath);
                \finfo_close($finfo);
                if (\is_string($mimeType) && $mimeType !== '') {
                    return $mimeType;
                }
            }
        }

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
        ];
        $ext = \strtolower($extension);

        return $mimeTypes[$ext] ?? 'application/octet-stream';
    }
}
