<?php

declare(strict_types=1);

namespace OnlineService\Feed\Yml;

use OnlineService\Feed\Config\FeedConfig;

final class YandexYmlFeedGenerator
{
    private string $siteBaseUrl;
    private FeedCatalogBatchLoader $batchLoader;
    private ?FeedGenerationProgress $progress;

    public function __construct(
        ?string $siteBaseUrl = null,
        ?FeedCatalogBatchLoader $batchLoader = null,
        ?FeedGenerationProgress $progress = null
    ) {
        $this->siteBaseUrl = rtrim($siteBaseUrl ?? $this->detectSiteBaseUrl(), '/');
        $this->batchLoader = $batchLoader ?? new FeedCatalogBatchLoader();
        $this->progress = $progress;
    }

    /**
     * Потоковая генерация в файл (константное потребление памяти).
     *
     * @return int количество офферов, записанных в фид
     */
    public function generateToFile(string $path): int
    {
        $this->batchLoader->assertModules();

        $this->report('Загрузка категорий...');
        $categories = $this->loadCategories();
        $this->report('Категорий: ' . count($categories));

        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Cannot open feed output file: ' . $path);
        }

        try {
            $this->writeHeader($handle, $categories);
            $this->report('Потоковая загрузка и сборка офферов...');
            $written = $this->streamOffers($handle);
            $this->writeFooter($handle);

            return $written;
        } finally {
            fclose($handle);
        }
    }

    public function generate(): string
    {
        $tmpPath = sys_get_temp_dir() . '/yandex-feed-' . getmypid() . '-' . bin2hex(random_bytes(4)) . '.xml';
        try {
            $this->generateToFile($tmpPath);
            $contents = file_get_contents($tmpPath);
            if ($contents === false) {
                throw new \RuntimeException('Cannot read generated feed from temporary file');
            }

            return $contents;
        } finally {
            @unlink($tmpPath);
        }
    }

    /**
     * @param resource $handle
     * @param array<int, string> $categories
     */
    private function writeHeader($handle, array $categories): void
    {
        $shopName = FeedConfig::SHOP_NAME;
        $shopCompany = FeedConfig::SHOP_COMPANY;
        $shopUrl = $this->siteBaseUrl . '/';
        $date = YmlXml::formatCatalogDate(new \DateTimeImmutable('now'));

        fwrite($handle, '<?xml version="1.0" encoding="utf-8"?>' . "\n");
        fwrite($handle, '<yml_catalog date="' . YmlXml::escape($date) . '">' . "\n");
        fwrite($handle, '  <shop>' . "\n");
        fwrite($handle, '    <name>' . YmlXml::escape($shopName) . '</name>' . "\n");
        fwrite($handle, '    <company>' . YmlXml::escape($shopCompany) . '</company>' . "\n");
        fwrite($handle, '    <url>' . YmlXml::escape($shopUrl) . '</url>' . "\n");
        fwrite($handle, '    <currencies>' . "\n");
        fwrite($handle, '      <currency id="' . YmlXml::escape(FeedConfig::DEFAULT_CURRENCY) . '" rate="1"/>' . "\n");
        fwrite($handle, '    </currencies>' . "\n");
        fwrite($handle, '    <categories>' . "\n");

        foreach ($categories as $categoryId => $categoryName) {
            fwrite(
                $handle,
                '      <category id="' . YmlXml::escape((string)$categoryId) . '">'
                . YmlXml::escape($categoryName) . '</category>' . "\n"
            );
        }

        fwrite($handle, '    </categories>' . "\n");
        fwrite($handle, '    <offers>' . "\n");
    }

    /**
     * @param resource $handle
     */
    private function writeFooter($handle): void
    {
        fwrite($handle, '    </offers>' . "\n");
        fwrite($handle, '  </shop>' . "\n");
        fwrite($handle, '</yml_catalog>' . "\n");
    }

    /**
     * @param resource $handle
     */
    private function streamOffers($handle): int
    {
        $written = 0;
        $chunkIndex = 0;
        $chunkSize = FeedConfig::OFFER_CHUNK_SIZE;

        $totalInCatalog = $this->batchLoader->iterateOfferChunks(
            $chunkSize,
            function (array $offers) use ($handle, &$written, &$chunkIndex): void {
                $chunkIndex++;
                $chunkWritten = $this->processOfferChunk($handle, $offers, $chunkIndex);
                $written += $chunkWritten;
            }
        );

        $this->report('Офферов в каталоге: ' . $totalInCatalog);
        $this->report('Офферов в фиде: ' . $written);

        return $written;
    }

    /**
     * @param resource $handle
     * @param list<array<string, mixed>> $offers
     */
    private function processOfferChunk($handle, array $offers, int $chunkIndex): int
    {
        if ($offers === []) {
            return 0;
        }

        $this->report('Чанк #' . $chunkIndex . ': ' . count($offers) . ' офферов...');

        $offerIds = [];
        foreach ($offers as $offer) {
            $offerId = (int)($offer['ID'] ?? 0);
            if ($offerId <= 0) {
                continue;
            }
            $offerIds[] = $offerId;
        }

        if ($offerIds === []) {
            return 0;
        }

        $offerProductLinkMap = $this->batchLoader->loadOfferProductLinkMap($offerIds);
        $linkedProductIds = [];
        foreach ($offers as &$offer) {
            $offerId = (int)($offer['ID'] ?? 0);
            if ($offerId <= 0) {
                continue;
            }
            $productId = (int)($offer['PROPERTY_CML2_LINK_VALUE'] ?? 0);
            if ($productId <= 0) {
                $productId = (int)($offerProductLinkMap[$offerId] ?? 0);
                if ($productId > 0) {
                    $offer['PROPERTY_CML2_LINK_VALUE'] = $productId;
                }
            }
            if ($productId > 0) {
                $linkedProductIds[] = $productId;
            }
        }
        unset($offer);
        $linkedProductIds = array_values(array_unique($linkedProductIds));

        $products = $this->batchLoader->loadProductsByIds($linkedProductIds);

        $allPriceProductIds = array_values(array_unique(array_merge($offerIds, $linkedProductIds)));
        $pricesByProductId = $this->batchLoader->loadPricesForProductIds($allPriceProductIds);
        $availabilityByOfferId = $this->batchLoader->loadCatalogProducts($offerIds);
        $offerFilePropertyMap = $this->batchLoader->loadFilePropertyMap(
            FeedConfig::OFFERS_IBLOCK_ID,
            $offerIds,
            ['MORE_PHOTO', 'PHOTOS']
        );

        $fileIds = [];
        foreach ($offers as $offer) {
            foreach (['PREVIEW_PICTURE', 'DETAIL_PICTURE'] as $field) {
                $fileId = (int)($offer[$field] ?? 0);
                if ($fileId > 0) {
                    $fileIds[] = $fileId;
                }
            }
        }
        foreach ($offerFilePropertyMap as $props) {
            foreach ($props as $codeFiles) {
                foreach ($codeFiles as $fileId) {
                    $fileIds[] = (int)$fileId;
                }
            }
        }

        $fileSrcMap = $this->batchLoader->resolveFileSrcMap($fileIds);

        $written = 0;
        $rejectStats = [];
        foreach ($offers as $offer) {
            $buildResult = $this->buildOfferXml(
                $offer,
                $products,
                $pricesByProductId,
                $availabilityByOfferId,
                $offerFilePropertyMap,
                $fileSrcMap
            );
            if ($buildResult['xml'] === '') {
                $reason = $buildResult['reason'] ?? 'unknown';
                $rejectStats[$reason] = ($rejectStats[$reason] ?? 0) + 1;
                continue;
            }

            fwrite($handle, $buildResult['xml']);
            $written++;
        }

        if ($chunkIndex === 1 && $written === 0 && $rejectStats !== []) {
            $this->report('Отказы чанка #1: ' . json_encode($rejectStats, JSON_UNESCAPED_UNICODE));
        }

        return $written;
    }

    /**
     * @param array<string, mixed> $offer
     * @param array<int, array<string, mixed>> $products
     * @param array<int, list<array<string, mixed>>> $pricesByProductId
     * @param array<int, array<string, mixed>> $availabilityByOfferId
     * @param array<int, array<string, list<int>>> $offerFilePropertyMap
     * @param array<int, string> $fileSrcMap
     * @return array{xml: string, reason: ?string}
     */
    private function buildOfferXml(
        array $offer,
        array $products,
        array $pricesByProductId,
        array $availabilityByOfferId,
        array $offerFilePropertyMap,
        array $fileSrcMap
    ): array {
        $offerId = (int)($offer['ID'] ?? 0);
        if ($offerId <= 0) {
            return ['xml' => '', 'reason' => 'invalid_offer_id'];
        }

        $productId = (int)($offer['PROPERTY_CML2_LINK_VALUE'] ?? 0);
        if ($productId <= 0 || !isset($products[$productId])) {
            return ['xml' => '', 'reason' => 'no_product'];
        }

        $product = $products[$productId];
        $categoryId = (int)($product['SECTION_ID'] ?? 0);
        if ($categoryId <= 0) {
            return ['xml' => '', 'reason' => 'no_category'];
        }

        $priceRows = $pricesByProductId[$offerId] ?? [];
        $priceRow = FeedOfferPriceResolver::resolve($priceRows);
        if ($priceRow === null || (float)($priceRow['MAIN'] ?? 0) <= 0) {
            $priceRow = FeedOfferPriceResolver::resolve($pricesByProductId[$productId] ?? []);
        }
        if ($priceRow === null) {
            return ['xml' => '', 'reason' => 'no_price'];
        }

        $mainPrice = (float)($priceRow['MAIN'] ?? 0);
        if ($mainPrice <= 0) {
            return ['xml' => '', 'reason' => 'zero_price'];
        }

        $oldPrice = (float)($priceRow['OLD'] ?? 0);
        $currency = (string)($priceRow['CURRENCY'] ?? FeedConfig::DEFAULT_CURRENCY);
        if ($currency === '') {
            $currency = FeedConfig::DEFAULT_CURRENCY;
        }

        $name = trim((string)($offer['NAME'] ?? ''));
        if ($name === '') {
            $name = (string)($product['NAME'] ?? '');
        }
        if ($name === '') {
            return ['xml' => '', 'reason' => 'no_name'];
        }

        $detailUrl = (string)($product['DETAIL_PAGE_URL'] ?? '');
        if ($detailUrl === '') {
            return ['xml' => '', 'reason' => 'no_url'];
        }

        $url = $this->toAbsoluteUrl($this->buildOfferUrl($detailUrl, $offerId));

        $offerPictureFileIds = [];
        foreach (['PREVIEW_PICTURE', 'DETAIL_PICTURE'] as $field) {
            $fileId = (int)($offer[$field] ?? 0);
            if ($fileId > 0) {
                $offerPictureFileIds[] = $fileId;
            }
        }
        foreach ($offerFilePropertyMap[$offerId] ?? [] as $codeFiles) {
            foreach ($codeFiles as $fileId) {
                $offerPictureFileIds[] = (int)$fileId;
            }
        }

        $pictures = $this->collectPictureSrcList(
            $offerPictureFileIds,
            (array)($product['PICTURES'] ?? []),
            $fileSrcMap
        );
        if ($pictures === []) {
            return ['xml' => '', 'reason' => 'no_picture'];
        }

        $catalogProduct = $availabilityByOfferId[$offerId] ?? null;
        $available = is_array($catalogProduct) && $this->batchLoader->isAvailable($catalogProduct);

        $vendor = (string)($product['BRAND'] ?? '');
        $vendorCode = FeedCatalogBatchLoader::extractPropertyDisplayValue(
            $offer['PROPERTY_' . FeedConfig::ARTICLE_PROPERTY_CODE . '_VALUE'] ?? ''
        );

        $params = [];
        $color = FeedCatalogBatchLoader::extractPropertyDisplayValue(
            $offer['PROPERTY_' . FeedConfig::COLOR_PROPERTY_CODE . '_VALUE'] ?? ''
        );
        if ($color === '') {
            $color = (string)($product['COLOR'] ?? '');
        }
        if ($color !== '') {
            $params['Цвет'] = $color;
        }

        $material = FeedCatalogBatchLoader::extractPropertyDisplayValue(
            $offer['PROPERTY_' . FeedConfig::MATERIAL_PROPERTY_CODE . '_VALUE'] ?? ''
        );
        if ($material === '') {
            $material = (string)($product['MATERIAL'] ?? '');
        }
        if ($material !== '') {
            $params['Материал'] = $material;
        }

        $oldPriceFormatted = null;
        if ($oldPrice > $mainPrice) {
            $discountPercent = (($oldPrice - $mainPrice) / $oldPrice) * 100;
            if ($discountPercent >= 5) {
                $oldPriceFormatted = YmlXml::formatPrice($oldPrice);
            }
        }

        return [
            'xml' => YmlXml::renderOffer(
                $offerId,
                $available,
                $name,
                $url,
                YmlXml::formatPrice($mainPrice),
                $currency,
                $categoryId,
                $pictures,
                $oldPriceFormatted,
                (string)($product['DESCRIPTION'] ?? ''),
                $vendor !== '' ? $vendor : null,
                $vendorCode !== '' ? $vendorCode : null,
                $params
            ),
            'reason' => null,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function loadCategories(): array
    {
        $categories = [];
        $sectionRes = \CIBlockSection::GetList(
            ['LEFT_MARGIN' => 'ASC'],
            [
                'IBLOCK_ID' => FeedConfig::CATALOG_IBLOCK_ID,
                'ACTIVE' => 'Y',
                'GLOBAL_ACTIVE' => 'Y',
            ],
            false,
            ['ID', 'NAME']
        );

        while ($section = $sectionRes->Fetch()) {
            $id = (int)($section['ID'] ?? 0);
            $name = trim((string)($section['NAME'] ?? ''));
            if ($id > 0 && $name !== '') {
                $categories[$id] = $name;
            }
        }

        return $categories;
    }

    /**
     * @param list<int> $fileIds
     * @param list<string> $productPictureSrcList
     * @param array<int, string> $fileSrcMap
     * @return list<string>
     */
    private function collectPictureSrcList(array $fileIds, array $productPictureSrcList, array $fileSrcMap): array
    {
        $pictures = [];

        foreach ($fileIds as $fileId) {
            $fileId = (int)$fileId;
            if ($fileId > 0 && isset($fileSrcMap[$fileId])) {
                $pictures[] = $this->toAbsoluteUrl($fileSrcMap[$fileId]);
            }
        }

        foreach ($productPictureSrcList as $src) {
            $pictures[] = $this->toAbsoluteUrl((string)$src);
        }

        $unique = [];
        foreach ($pictures as $picture) {
            $picture = trim($picture);
            if ($picture !== '' && !isset($unique[$picture])) {
                $unique[$picture] = true;
            }
        }

        return array_slice(array_keys($unique), 0, 5);
    }

    private function buildOfferUrl(string $detailPageUrl, int $offerId): string
    {
        if ($offerId <= 0) {
            return $detailPageUrl;
        }

        return rtrim($detailPageUrl, '/') . '/offer/' . $offerId . '/';
    }

    private function toAbsoluteUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            return $url;
        }

        return $this->siteBaseUrl . (strpos($url, '/') === 0 ? '' : '/') . $url;
    }

    private function detectSiteBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
        if ($host !== '') {
            return $scheme . '://' . $host;
        }

        if (defined('SITE_SERVER_NAME') && (string)SITE_SERVER_NAME !== '') {
            return $scheme . '://' . SITE_SERVER_NAME;
        }

        return 'https://eklektika.ru';
    }

    private function report(string $message): void
    {
        $this->progress?->step($message);
    }
}
