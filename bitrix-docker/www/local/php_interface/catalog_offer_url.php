<?php

/**
 * SEF URL торгового предложения: /catalog/.../element/offer/{ID}/
 */
function catalogBuildOfferDetailUrl(string $detailPageUrl, int $offerId): string
{
    $detailPageUrl = (string)$detailPageUrl;
    if ($offerId <= 0) {
        return $detailPageUrl;
    }

    return rtrim($detailPageUrl, '/') . '/offer/' . $offerId . '/';
}

/**
 * @param array<string, mixed> $item
 */
function catalogItemEnrichOfferDetailUrls(array &$item): void
{
    if (empty($item['OFFERS']) || !is_array($item['OFFERS'])) {
        return;
    }

    $parentDetailUrl = (string)($item['DETAIL_PAGE_URL'] ?? '');
    foreach ($item['OFFERS'] as $key => $offer) {
        if (!is_array($offer)) {
            continue;
        }
        $offerId = (int)($offer['ID'] ?? 0);
        $item['OFFERS'][$key]['DETAIL_PAGE_URL'] = catalogBuildOfferDetailUrl($parentDetailUrl, $offerId);
    }
}
