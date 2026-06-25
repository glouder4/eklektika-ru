<?php

namespace OnlineService\Site;

use Bitrix\Main\Loader;
use CompositeProperties\Iblock\ElementPublicSnapshotFileStore;
use CompositeProperties\Iblock\IblockEntityValueRepository;
use OnlineService\Site\Config\ManagerCompositeConfig;

/**
 * Синхронизация repeater sotsialnaya_set (tip_sotsseti + ssylka) из UPDATE_MANAGER.
 */
final class ManagerCompositeSocialLinksSync
{
    private const VALUE_SCHEMA_VERSION = 1;

    private ManagerCompositeFieldResolver $fieldResolver;

    public function __construct(?ManagerCompositeFieldResolver $fieldResolver = null)
    {
        $this->fieldResolver = $fieldResolver ?? new ManagerCompositeFieldResolver();
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function syncForManagerElement(int $elementId, int $iblockId, array $fields): bool
    {
        if ($elementId <= 0 || $iblockId <= 0) {
            return false;
        }

        $meta = $this->fieldResolver->resolveSocialRepeaterMeta();
        if ($meta === null) {
            return true;
        }

        $incomingByType = $this->extractIncomingLinksByType($fields);
        if ($incomingByType === null) {
            return true;
        }

        if (!Loader::includeModule('amiiyaproduction.compositeproperties')) {
            return false;
        }

        $repo = new IblockEntityValueRepository();
        $fieldId = (int)$meta['field_id'];
        $previousMap = $repo->loadValuesMap(
            $iblockId,
            IblockEntityValueRepository::ENTITY_ELEMENT,
            $elementId,
            [$fieldId]
        );

        $items = $this->mergeRepeaterItems(
            $this->decodeRepeaterItems((string)($previousMap[$fieldId] ?? '')),
            $incomingByType
        );

        $payload = [
            'value_schema_version' => self::VALUE_SCHEMA_VERSION,
            'items' => $items,
        ];
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded)) {
            return false;
        }

        $repo->upsertValues(
            $iblockId,
            IblockEntityValueRepository::ENTITY_ELEMENT,
            $elementId,
            [$fieldId => $encoded]
        );

        if (class_exists(ElementPublicSnapshotFileStore::class)) {
            ElementPublicSnapshotFileStore::rebuildBestEffort(
                $_SERVER['DOCUMENT_ROOT'] ?? '',
                $iblockId,
                $elementId
            );
        }

        return true;
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, string>|null social type => url; null если нет управляемых ключей
     */
    private function extractIncomingLinksByType(array $fields): ?array
    {
        $map = ManagerCompositeConfig::getPayloadLinkMap();
        $result = [];
        $hasManagedKey = false;

        foreach ($map as $payloadKey => $socialType) {
            if (!\array_key_exists($payloadKey, $fields)) {
                continue;
            }
            $hasManagedKey = true;
            $result[(string)$socialType] = \trim((string)$fields[$payloadKey]);
        }

        return $hasManagedKey ? $result : null;
    }

    /**
     * @param list<array<string, mixed>> $existingItems
     * @param array<string, string> $incomingByType
     * @return list<array<string, string>>
     */
    private function mergeRepeaterItems(array $existingItems, array $incomingByType): array
    {
        $managedTypes = ManagerCompositeConfig::getManagedSocialTypes();
        $typeCode = ManagerCompositeConfig::INNER_TYPE_CODE;
        $linkCode = ManagerCompositeConfig::INNER_LINK_CODE;

        $existingByType = [];
        $otherRows = [];

        foreach ($existingItems as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $type = $this->normalizeSocialType((string)($row[$typeCode] ?? ''));
            if ($type !== '' && \in_array($type, $managedTypes, true)) {
                $existingByType[$type] = $this->normalizeRepeaterRow($row, $typeCode, $linkCode);
                continue;
            }

            $normalized = $this->normalizeRepeaterRow($row, $typeCode, $linkCode);
            if ($normalized !== []) {
                $otherRows[] = $normalized;
            }
        }

        $result = $otherRows;

        foreach ($managedTypes as $type) {
            if (\array_key_exists($type, $incomingByType)) {
                $url = $incomingByType[$type];
                if ($url === '') {
                    continue;
                }
                $result[] = [
                    $typeCode => $type,
                    $linkCode => $url,
                ];
                continue;
            }

            if (isset($existingByType[$type])) {
                $result[] = $existingByType[$type];
            }
        }

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function decodeRepeaterItems(string $json): array
    {
        $json = \trim($json);
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!\is_array($decoded) || !isset($decoded['items']) || !\is_array($decoded['items'])) {
            return [];
        }

        return \array_values($decoded['items']);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, string>
     */
    private function normalizeRepeaterRow(array $row, string $typeCode, string $linkCode): array
    {
        $normalized = [];

        $type = $this->normalizeSocialType((string)($row[$typeCode] ?? ''));
        if ($type !== '') {
            $normalized[$typeCode] = $type;
        }

        $link = \trim((string)($row[$linkCode] ?? ''));
        if ($link !== '') {
            $normalized[$linkCode] = $link;
        }

        return $normalized;
    }

    private function normalizeSocialType(string $raw): string
    {
        return \strtoupper(\trim($raw));
    }
}
