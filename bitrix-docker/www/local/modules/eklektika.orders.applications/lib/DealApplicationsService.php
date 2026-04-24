<?php

namespace OnlineService\Orders\Applications;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Sale;
use CIBlockElement;
use CModule;
use CPrice;
use OnlineService\Orders\Applications\Config\DealApplicationsConfig;
use OnlineService\B24\RestClient;

/**
 * Строки заявок из сделки Bitrix24 в каталог и корзину заказа.
 */
final class DealApplicationsService
{
    public static function getApplication(int $dealId, int $orderId): void
    {
        if (!defined('EKLEKTIKA_DEAL_APPLICATIONS_DEBUG_LOG') || !EKLEKTIKA_DEAL_APPLICATIONS_DEBUG_LOG) {
            return;
        }

        if (!self::requireCommerceModules()) {
            return;
        }

        $responseArray = self::fetchDealProductRowsResponse($dealId);
        if ($responseArray === null || !isset($responseArray['result'])) {
            return;
        }

        self::debugLog('/get-items-log.txt', date('Y-m-d H:i:s') . print_r($responseArray['result'], true));
    }

    public static function addApplication(int $dealId, int $orderId): void
    {
        if (!self::requireCommerceModules()) {
            return;
        }

        $responseArray = self::fetchDealProductRowsResponse($dealId);
        if ($responseArray === null || empty($responseArray['result']) || !is_array($responseArray['result'])) {
            return;
        }

        $itemForOrder = [];

        foreach ($responseArray['result'] as $orderItem) {
            if (
                !empty($orderItem['UF_APPLICATION_PARENT_PRODUCT_ROW_ID'])
                && (int) $orderItem['UF_APPLICATION_PARENT_PRODUCT_ROW_ID'] !== 0
            ) {
                $key = array_search(
                    $orderItem['UF_APPLICATION_PARENT_PRODUCT_ROW_ID'],
                    array_column($responseArray['result'], 'ID')
                );
                if ($key === false) {
                    continue;
                }
                $appParentName = $responseArray['result'][$key]['PRODUCT_NAME'] ?? '';

                $itemForOrder[] = [
                    'ARTICLE' => $orderItem['PROPERTY_ARTIKUL_BITRIKS'],
                    'NAME' => $orderItem['PRODUCT_NAME'],
                    'PRICE' => $orderItem['PRICE'],
                    'APPLICATION_PARENT_PRODUCT_NAME' => $appParentName,
                ];
            }
        }

        if ($itemForOrder !== []) {
            self::ensureCatalogItems(DealApplicationsConfig::PRODUCT_IBLOCK_ID, $itemForOrder);
            self::updateOrderBasket(DealApplicationsConfig::PRODUCT_IBLOCK_ID, $itemForOrder, $orderId);
        }

        self::debugLog('/log-update-order-check-script.txt', print_r($responseArray, true));
    }

    private static function requireCommerceModules(): bool
    {
        return CModule::IncludeModule('iblock') && CModule::IncludeModule('sale');
    }

    /**
     * @return array<string, mixed>|null полный декодированный ответ REST или null при ошибке
     */
    private static function fetchDealProductRowsResponse(int $dealId): ?array
    {
        $full = RestClient::callKitRestGet(DealApplicationsConfig::KIT_METHOD_PRODUCT_ROWS . (int) $dealId);

        if (isset($full['success']) && (int) $full['success'] === 0) {
            return null;
        }

        return $full;
    }

    private static function ensureCatalogItems(int $iblockId, array $products): void
    {
        global $USER;

        foreach ($products as $product) {
            $arFields = [];
            $arSelect = ['ID', 'NAME', 'PRICE_1', 'PROPERTY_CML2_ARTICLE'];
            $arFilter = [
                'IBLOCK_ID' => (int) $iblockId,
                'NAME' => $product['NAME'],
                'PROPERTY_CML2_ARTICLE' => $product['ARTICLE'],
            ];

            $res = CIBlockElement::GetList([], $arFilter, false, ['nPageSize' => 50], $arSelect);

            while ($ob = $res->GetNextElement()) {
                $arFields = $ob->GetFields();
            }

            if ($arFields === []) {
                $el = new CIBlockElement();
                $PROP = [];
                $PROP[DealApplicationsConfig::PROPERTY_ARTICLE_ID] = $product['ARTICLE'];
                $arLoadProductArray = [
                    'MODIFIED_BY' => $USER->GetID(),
                    'IBLOCK_SECTION_ID' => false,
                    'IBLOCK_ID' => $iblockId,
                    'PROPERTY_VALUES' => $PROP,
                    'NAME' => $product['NAME'],
                    'ACTIVE' => 'Y',
                    'QUANTITY' => 0,
                ];

                $productId = $el->Add($arLoadProductArray);
                if ($productId) {
                    self::setPriceForProduct((int) $productId, $product['PRICE']);
                } else {
                    echo 'Error: ' . $el->LAST_ERROR;
                }
            }
        }
    }

    private static function setPriceForProduct(int $productId, $price): void
    {
        $arFields = [
            'PRODUCT_ID' => $productId,
            'CATALOG_GROUP_ID' => DealApplicationsConfig::PRICE_TYPE_ID,
            'PRICE' => $price,
            'CURRENCY' => 'RUB',
        ];
        $res = CPrice::GetList(
            [],
            [
                'PRODUCT_ID' => $productId,
                'CATALOG_GROUP_ID' => DealApplicationsConfig::PRICE_TYPE_ID,
            ]
        );
        if ($arr = $res->Fetch()) {
            CPrice::Update($arr['ID'], $arFields);
        } else {
            CPrice::Add($arFields);
        }
    }

    private static function updateOrderBasket(int $iblockId, array $products, int $orderId): void
    {
        Loader::includeModule('sale');

        $order = Sale\Order::load($orderId);
        $basket = $order->getBasket();
        $basket->refreshData(['PRICE']);
        $quantity = 1;

        foreach ($products as $product) {
            $name = $product['NAME'] . ' (' . $product['APPLICATION_PARENT_PRODUCT_NAME'] . ')';
            $arSelect = ['ID', 'NAME', 'PRICE_1', 'PROPERTY_CML2_ARTICLE'];
            $arFilter = [
                'IBLOCK_ID' => (int) $iblockId,
                'NAME' => $product['NAME'],
                'PROPERTY_CML2_ARTICLE' => $product['ARTICLE'],
                'PROPERTY_PARENT_NAME' => $product['APPLICATION_PARENT_PRODUCT_NAME'],
            ];

            $res = CIBlockElement::GetList([], $arFilter, false, ['nPageSize' => 50], $arSelect);

            $arFields = [];
            while ($ob = $res->GetNextElement()) {
                $arFields = $ob->GetFields();
            }

            if (empty($arFields['ID'])) {
                continue;
            }

            $basketInfo = [];
            foreach ($basket as $basketItem) {
                $basketInfo[] = $basketItem->getField('NAME');
            }

            $findItemInOrder = array_search($name, $basketInfo, true);

            if ($findItemInOrder === false) {
                $item = $basket->createItem('catalog', $arFields['ID']);
                $item->setFields([
                    'NAME' => $name,
                    'QUANTITY' => $quantity,
                    'CURRENCY' => CurrencyManager::getBaseCurrency(),
                    'LID' => Context::getCurrent()->getSite(),
                    'PRICE' => $arFields['PRICE_1'],
                    'CUSTOM_PRICE' => 'Y',
                    'IGNORE_CALLBACK_FUNC' => 'Y',
                    'PRODUCT_PROVIDER_CLASS' => '',
                ]);
            }

            $basket->refresh();
            $basket->save();
            $refreshRes = $basket->refreshData(['PRICE', 'COUPONS']);
            if (!$refreshRes->isSuccess()) {
                // наследие: ранее обращение к несуществующей переменной; оставляем без побочных эффектов
            }
        }

        $discount = $order->getDiscount();
        $registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
        $discountCouponsClass = $registry->getDiscountCouponClassName();
        $discountCouponsClass::clearApply(true);
        $discountCouponsClass::useSavedCouponsForApply(true);

        $calcRes = $discount->calculate();
        $order->applyDiscount($calcRes->getData());
        $order->save();
    }

    private static function debugLog(string $relativePath, string $content): void
    {
        if (!defined('EKLEKTIKA_DEAL_APPLICATIONS_DEBUG_LOG') || !EKLEKTIKA_DEAL_APPLICATIONS_DEBUG_LOG) {
            return;
        }
        $root = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');
        if ($root === '') {
            return;
        }
        @file_put_contents($root . $relativePath, $content);
    }
}
