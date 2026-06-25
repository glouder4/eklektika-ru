<?php

declare(strict_types=1);

namespace OnlineService\Feed\Yml;

/**
 * Чистые хелперы форматирования YML/XML (без Bitrix).
 */
final class YmlXml
{
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    public static function formatPrice(float $price): string
    {
        if ($price <= 0) {
            return '';
        }

        if (abs($price - round($price)) < 0.00001) {
            return (string)(int)round($price);
        }

        return number_format($price, 2, '.', '');
    }

    public static function formatCatalogDate(\DateTimeInterface $dateTime): string
    {
        return $dateTime->format('Y-m-d H:i');
    }

    public static function boolString(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    /**
     * @param list<string> $pictures
     */
    public static function renderOffer(
        int $offerId,
        bool $available,
        string $name,
        string $url,
        string $price,
        string $currencyId,
        int $categoryId,
        array $pictures,
        ?string $oldPrice = null,
        ?string $description = null,
        ?string $vendor = null,
        ?string $vendorCode = null,
        array $params = []
    ): string {
        $xml = '    <offer id="' . self::escape((string)$offerId) . '" available="' . self::boolString($available) . '">' . "\n";
        $xml .= '      <url>' . self::escape($url) . '</url>' . "\n";
        $xml .= '      <price>' . self::escape($price) . '</price>' . "\n";

        if ($oldPrice !== null && $oldPrice !== '' && (float)$oldPrice > (float)$price) {
            $xml .= '      <oldprice>' . self::escape($oldPrice) . '</oldprice>' . "\n";
        }

        $xml .= '      <currencyId>' . self::escape($currencyId) . '</currencyId>' . "\n";
        $xml .= '      <categoryId>' . self::escape((string)$categoryId) . '</categoryId>' . "\n";

        foreach ($pictures as $picture) {
            $picture = trim($picture);
            if ($picture === '') {
                continue;
            }
            $xml .= '      <picture>' . self::escape($picture) . '</picture>' . "\n";
        }

        $xml .= '      <name>' . self::escape($name) . '</name>' . "\n";

        if ($vendor !== null && $vendor !== '') {
            $xml .= '      <vendor>' . self::escape($vendor) . '</vendor>' . "\n";
        }

        if ($vendorCode !== null && $vendorCode !== '') {
            $xml .= '      <vendorCode>' . self::escape($vendorCode) . '</vendorCode>' . "\n";
        }

        if ($description !== null && $description !== '') {
            $xml .= '      <description>' . self::escape($description) . '</description>' . "\n";
        }

        foreach ($params as $paramName => $paramValue) {
            $paramName = trim((string)$paramName);
            $paramValue = trim((string)$paramValue);
            if ($paramName === '' || $paramValue === '') {
                continue;
            }
            $xml .= '      <param name="' . self::escape($paramName) . '">' . self::escape($paramValue) . '</param>' . "\n";
        }

        $xml .= '    </offer>' . "\n";

        return $xml;
    }
}
