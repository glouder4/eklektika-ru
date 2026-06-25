<?php

declare(strict_types=1);

namespace OnlineService\Feed\Yml;

use OnlineService\Feed\Config\FeedConfig;

/**
 * Расчёт витринной пары цен для фида (CLI/cron, без скидки компании).
 */
final class FeedOfferPriceResolver
{
    /**
     * @param list<array{PRICE: float|string, CURRENCY?: string, CATALOG_GROUP_ID?: int, PRICE_TYPE_ID?: int}> $priceRows
     * @return array{MAIN: float, OLD: float|null, DISCOUNT: float, CURRENCY: string}|null
     */
    public static function resolve(
        array $priceRows,
        ?int $mainPriceTypeId = FeedConfig::ADVERTISING_PRICE_TYPE_ID,
        ?int $oldPriceTypeId = FeedConfig::BASE_PRICE_TYPE_ID
    ): ?array {
        if ($priceRows === []) {
            return null;
        }

        $mainPriceData = null;
        $oldPriceData = null;
        $advertisingPriceData = null;
        $fallbackPriceData = null;

        foreach ($priceRows as $row) {
            $typeId = (int)($row['CATALOG_GROUP_ID'] ?? $row['PRICE_TYPE_ID'] ?? 0);
            if ($mainPriceTypeId !== null && $typeId === $mainPriceTypeId) {
                $mainPriceData = $row;
            }
            if ($oldPriceTypeId !== null && $typeId === $oldPriceTypeId) {
                $oldPriceData = $row;
            }
            if ($typeId === FeedConfig::ADVERTISING_PRICE_TYPE_ID) {
                $advertisingPriceData = $row;
            }
            if ($typeId === FeedConfig::BASE_PRICE_FALLBACK_TYPE_ID) {
                $fallbackPriceData = $row;
            }
        }

        if ($mainPriceTypeId === null || $oldPriceTypeId === null) {
            $priceValues = array_map(static fn(array $p): float => (float)$p['PRICE'], $priceRows);
            $mainPriceData = ['PRICE' => min($priceValues), 'CURRENCY' => $priceRows[0]['CURRENCY'] ?? 'RUB'];
            $oldPriceData = ['PRICE' => max($priceValues)];
        }

        $mainPrice = $mainPriceData !== null ? (float)$mainPriceData['PRICE'] : null;
        $oldPrice = $oldPriceData !== null ? (float)$oldPriceData['PRICE'] : null;
        $currency = (string)(($mainPriceData ?? $oldPriceData)['CURRENCY'] ?? 'RUB');

        if (
            $mainPrice !== null
            && $mainPriceTypeId !== null
            && (int)$mainPriceTypeId === FeedConfig::PURCHASE_PRICE_TYPE_ID
            && $advertisingPriceData !== null
            && $fallbackPriceData !== null
        ) {
            $adVal = (float)$advertisingPriceData['PRICE'];
            $fbVal = (float)$fallbackPriceData['PRICE'];
            if ($adVal > $fbVal) {
                $oldPrice = $mainPrice;
            }
        }

        if ($mainPrice === null || $mainPrice <= 0) {
            return null;
        }

        $discount = 0.0;
        if ($oldPrice !== null && $oldPrice > 0 && $oldPrice > $mainPrice) {
            $discount = round((($oldPrice - $mainPrice) / $oldPrice) * 100, 1);
        }

        return [
            'MAIN' => $mainPrice,
            'OLD' => $oldPrice,
            'DISCOUNT' => $discount,
            'CURRENCY' => $currency,
        ];
    }
}
