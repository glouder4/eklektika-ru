<?php

declare(strict_types=1);

namespace OnlineService\Feed\Config;

final class FeedConfig
{
    public const CATALOG_IBLOCK_ID = 13;
    public const OFFERS_IBLOCK_ID = 14;

    public const BRAND_PROPERTY_CODE = 'BRENDY_DLYA_WEB';
    public const ARTICLE_PROPERTY_CODE = 'CML2_ARTICLE';
    public const COLOR_PROPERTY_CODE = 'TSVET';
    public const MATERIAL_PROPERTY_CODE = 'MATERIAL';

    public const SHOP_NAME = 'Эклектика';
    public const SHOP_COMPANY = 'Эклектика';
    public const DEFAULT_CURRENCY = 'RUB';

    /** Синхронизировано с CatalogPricingConfig (eklektika.catalog.pricing) для CLI без этого модуля. */
    public const ADVERTISING_PRICE_TYPE_ID = 3;
    public const PURCHASE_PRICE_TYPE_ID = 3;
    public const BASE_PRICE_TYPE_ID = 2;
    public const BASE_PRICE_FALLBACK_TYPE_ID = 1;

    public const HTTP_PATH = '/local/modules/eklektika.feed/public/yandex-yml.php';
    public const PUBLIC_PATH = '/feed/yandex.yml';
    public const PUBLIC_ENTRY_PATH = '/feed/yandex.yml/index.php';
    public const REGENERATE_HTTP_PATH = '/local/modules/eklektika.feed/public/regenerate_yandex_yml.php';

    /** Относительно DOCUMENT_ROOT; не в web-root upload — только отдача через endpoint. */
    public const CACHE_RELATIVE_PATH = '/local/cache/eklektika.feed/yandex.yml';

    public const CACHE_DIR_RELATIVE_PATH = '/local/cache/eklektika.feed';

    /** Размер чанка офферов при потоковой генерации (экономия памяти). */
    public const OFFER_CHUNK_SIZE = 500;
}
