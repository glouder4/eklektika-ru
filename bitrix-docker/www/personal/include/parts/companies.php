<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?php

use Bitrix\Main\Localization\Loc;
use intec\core\helpers\Html;
use intec\core\helpers\StringHelper;

/**
 * @var array $arTickets
 * @var CBitrixComponentTemplate $this
 * @var CBitrixComponent $component
 */

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    return null;
}
?>
<style>
    #personal-info--wrapper{
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
    }
    .sale-personal-section-claims {
        border: 1px solid #F2F2F2;

        background: #f8f9fa;
        border-radius: 12px;
        padding: 16px;
        margin: 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    .sale-personal-section-claims-header {
        padding: 0px;
        border-bottom: 1px solid #F2F2F2;
    }
    section-claims-header {
        margin-bottom: 12px;
        padding: 0 !important;
    }
    .sale-personal-section-claims-title {
        font-weight: 500;
        font-size: 16px;
        line-height: 18px;
        color: #404040;
    }
    .sale-personal-section-claims-title {
        font-size: 20px;
        font-weight: 400;
        color: #333333;
        margin: 0;
        position: relative;
        padding-bottom: 8px;
    }
    .sale-personal-section-claims-wrap {
        background: transparent;
        padding: 0 !important;
    }
    .sale-personal-section-claims-items {
        background: transparent;
    }
    .companies-empty {
        text-align: center;
        padding: 40px 20px;
        color: #666;
        font-size: 16px;
    }

    #personal-manager--wrapper {
        display: flex;
        flex-direction: column;
        gap: 24px;
        min-width: 148px;
    }
    .manager-card-fields + .manager-card-fields {
        padding-top: 12px;
        border-top: 1px solid #f2f2f2;
    }
    .manager-card-fields {
        display: flex;
        flex-direction: column;
    }
    .manager-personal-info {
        display: flex;
        flex-direction: row;
        gap: 20px;
    }
    .manager--avatar_field {
        width: 60px;
        height: 60px;
        border-radius: 50%;
    }
    .manager--avatar_field img {
        border-radius: 50%;
    }
    .manager--info {
        display: flex;
        flex-direction: column;
        gap: 10px;
        justify-content: center;
    }
    .manager--info>.field.post>span {
        font-family: Ubuntu;
        font-weight: 600;
        font-size: 14px;
        line-height: 16px;
        letter-spacing: 0;
        color: #858585;
    }
    .manager--info>.field.name>span {
        font-family: Ubuntu;
        font-weight: 600;
        font-size: 18px;
        line-height: 20px;
        letter-spacing: 0;
        color: black;
    }
    .manager-action-links--wrapper {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 30px;
    }
    .manager-action-links--wrapper>.link>a {
        display: flex;
        flex-direction: row;
        gap: 18px;
        font-family: Ubuntu;
        font-weight: 400;
        font-size: 16px;
        line-height: 100%;
        letter-spacing: 0;
        color: #858585;

        text-decoration: unset;
        border: 0;
    }
    .manager-action-links--wrapper>.link>a svg path{
        fill: #858585;
    }

    #holding-structure{
        margin-bottom: 24px;
        margin-top:0;

        width: 50%;
        padding: 12px;
    }

    .our-social_links {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-top: 30px;
    }
    .our-social_links>.title>span {
        font-family: Ubuntu;
        font-weight: 600;
        font-size: 16px;
        line-height: 100%;
        letter-spacing: 0;
        color: #222222;
    }
    .our-social_links .links {
        display: flex;
        flex-direction: row;
        gap: 23px;
    }

    .our-social_links .links .link{
        border: 0;
    }

    /* Древовидный список компаний */
    .companies-compact {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    /* Отступ между разными холдингами */
    .companies-compact--additional {
        margin-top: 20px;
        padding-top: 16px;
        border-top: 2px solid #e0e0e0;
    }

    .company-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 6px 10px;
        border-radius: 4px;
        background: white;
        transition: all 0.2s ease;
        position: relative;
        text-decoration: none;
        color: inherit;
    }

    .company-item:hover {
        background: #f9f9f9;
        text-decoration: none;
        color: inherit;
    }

    .company-item--head {
        background: #f5f5f5;
        font-weight: 600;
    }

    .company-item--child {
        margin-left: 20px;
        position: relative;
    }

    /* Древовидные ветки */
    .company-item--child::before {
        content: '';
        position: absolute;
        left: -20px;
        top: 0;
        bottom: 0;
        width: 1px;
        background: #ddd;
    }

    .company-item--child::after {
        content: '';
        position: absolute;
        left: -20px;
        top: 50%;
        width: 15px;
        height: 1px;
        background: #ddd;
    }

    /* Соединительная линия для последней дочерней компании */
    .company-item--child:last-child::before {
        bottom: 50%;
    }

    .company-item__content {
        display: flex;
        align-items: center;
        gap: 6px;
        flex: 1;
    }

    .company-item__name {
        font-weight: 500;
        font-size: 13px;
        color: #333;
    }

    .company-item--head .company-item__name {
        font-weight: 600;
    }

    .company-item__badges {
        display: flex;
        gap: 3px;
    }

    .badge {
        padding: 1px 4px;
        border-radius: 2px;
        font-size: 9px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.2px;
    }

    .badge--active {
        background: #e8f5e8;
        color: #2d5a2d;
    }

    .badge--inactive {
        background: #f5e8e8;
        color: #5a2d2d;
    }

    .badge--head {
        background: #e8f0f5;
        color: #2d4a5a;
    }


    .companies-empty {
        text-align: center;
        padding: 40px 20px;
        color: #666;
        font-size: 16px;
    }

    /* Адаптивность для блока компаний */
    @media (max-width: 768px) {
        .sale-personal-section-claims {
            padding: 12px;
            margin: 12px 0;
        }

        .sale-personal-section-claims-header {
            margin-bottom: 8px;
        }

        .sale-personal-section-claims-title {
            font-size: 18px;
            padding-bottom: 6px;
        }

        .company-item {
            padding: 5px 8px;
        }

        .companies-compact {
            gap: 6px;
        }

        .companies-compact--additional {
            margin-top: 16px;
            padding-top: 12px;
        }

        .company-item--child {
            margin-left: 15px;
        }

        .company-item--child::before {
            left: -15px;
        }

        .company-item--child::after {
            left: -15px;
            width: 12px;
        }

        .company-item__content {
            gap: 4px;
        }

        .company-item__name {
            font-size: 12px;
        }

        .badge {
            font-size: 8px;
            padding: 1px 3px;
        }

        .company-item__link {
            font-size: 11px;
        }
    }

    @media (max-width: 480px) {
        .sale-personal-section-claims {
            padding: 10px;
            margin: 8px 0;
        }

        .sale-personal-section-claims-header {
            margin-bottom: 6px;
        }

        .sale-personal-section-claims-title {
            font-size: 16px;
            padding-bottom: 4px;
        }

        .company-item {
            padding: 4px 6px;
        }

        .companies-compact {
            gap: 5px;
        }

        .companies-compact--additional {
            margin-top: 12px;
            padding-top: 10px;
        }

        .company-item--child {
            margin-left: 12px;
        }

        .company-item--child::before {
            left: -12px;
        }

        .company-item--child::after {
            left: -12px;
            width: 10px;
        }

        .company-item__name {
            font-size: 11px;
        }

        .company-item__link {
            font-size: 10px;
        }
    }
</style>

<div class="sale-personal-section-claims">
    <div class="sale-personal-section-claims-header">
        <div class="sale-personal-section-claims-title">
            Компании
        </div>
    </div>
    <div class="sale-personal-section-claims-wrap">
        <div class="sale-personal-section-claims-items">
            <?php
            global $USER;

            $headYesEnumIds = [2074, 31520];
            if (\Bitrix\Main\Loader::includeModule('eklektika.company')) {
                $headYesEnumIds = \OnlineService\Site\Config\CompanyModuleConfig::getHeadOfHoldingCrmListYesValueIds();
            }

            /** @see \OnlineService\Site\Company::isTruthyHeadOfHoldingLeafValue */
            $companiesPhpIsHeadOfHolding = static function (array $props) use ($headYesEnumIds): bool {
                $p = $props['LEGAN_ENTITY_IS_HEAD_COMPANY'] ?? null;
                if (!\is_array($p)) {
                    return false;
                }
                $xml = \strtoupper(\trim((string)($p['VALUE_XML_ID'] ?? '')));
                if ($xml === 'YES') {
                    return true;
                }
                $v = $p['VALUE'] ?? null;
                if (\is_array($v)) {
                    $v = \reset($v);
                }
                if ($v === null || $v === '' || $v === false) {
                    return false;
                }
                if ($v === true || $v === 1 || $v === '1') {
                    return true;
                }
                if (\is_string($v)) {
                    $s = \strtoupper(\trim($v));
                    if (\in_array($s, ['Y', 'YES', 'TRUE', '1', 'ДА', '31520'], true)) {
                        return true;
                    }
                    if (\in_array($s, ['N', 'NO', 'FALSE', '0', 'НЕТ'], true)) {
                        return false;
                    }
                }
                $i = (int)(string)$v;

                return $i !== 0 && \in_array($i, $headYesEnumIds, true);
            };

            $currentUserId = (int)$USER->GetID();
            $rsDirectorUser = \CUser::GetList(
                ['ID' => 'ASC'],
                'asc',
                ['ID' => $currentUserId],
                ['SELECT' => ['UF_IS_DIRECTOR']]
            );
            $arDirectorUser = $rsDirectorUser->Fetch();
            $isDirectorUser = false;
            if (\is_array($arDirectorUser)) {
                $dv = $arDirectorUser['UF_IS_DIRECTOR'] ?? null;
                if ($dv === true || $dv === 1 || $dv === '1') {
                    $isDirectorUser = true;
                } elseif (\is_string($dv)) {
                    $isDirectorUser = \in_array(\strtoupper(\trim($dv)), ['Y', 'YES', 'TRUE', '1'], true);
                } elseif ($dv !== null && $dv !== '' && $dv !== false) {
                    $isDirectorUser = (bool)(int)$dv;
                }
            }

            $companyIblockId = 23;
            if (\Bitrix\Main\Loader::includeModule('eklektika.company')) {
                $companyIblockId = (int)\OnlineService\Site\Config\CompanyModuleConfig::COMPANY_IBLOCK_ID;
            }

            $bossCompanyIds = [];
            $rsBossCompanies = \CIBlockElement::GetList(
                [],
                ['IBLOCK_ID' => $companyIblockId, 'PROPERTY_LEGAN_ENTITY_BOSS' => $currentUserId],
                false,
                false,
                ['ID']
            );
            while ($bossRow = $rsBossCompanies->Fetch()) {
                $bossCompanyIds[(int)$bossRow['ID']] = true;
            }

            // Получаем ВСЕ компании пользователя (как сотрудник или руководитель)
            $rsCompanies = \CIBlockElement::GetList(
                [],
                [
                    'IBLOCK_ID' => 23,
                    [
                        'LOGIC' => 'OR',
                        'PROPERTY_LEGAN_ENTITY_USERS' => $USER->GetID(),
                        'PROPERTY_LEGAN_ENTITY_BOSS' => $USER->GetID()
                    ]
                ],
                false,
                false,
                ['ID', 'PROPERTY_LEGAN_ENTITY_IS_HEAD_COMPANY', 'PROPERTY_LEGAN_ENTITY_ID_OF_HEAD_COMPANY']
            );

            $userCompanies = [];
            while ($company = $rsCompanies->GetNext()) {
                $userCompanies[] = $company;
            }

            /** ID элементов ИБ, где пользователь явно в LEGAN_ENTITY_USERS или LEGAN_ENTITY_BOSS (не «чужая» голова из дерева холдинга). */
            $userDirectCompanyIds = [];
            foreach ($userCompanies as $uc) {
                $userDirectCompanyIds[(int)$uc['ID']] = true;
            }

            // Группируем компании по холдингам
            $holdingsData = [];
            $processedHoldings = []; // Чтобы избежать дублирования холдингов

            foreach ($userCompanies as $userCompany) {
                $holdingKey = null;
                $headCompany = null;
                $childCompanies = [];

                // Проверяем, является ли компания головной холдинга
                if (!empty($userCompany['PROPERTY_LEGAN_ENTITY_IS_HEAD_COMPANY_VALUE']) &&
                    ($userCompany['PROPERTY_LEGAN_ENTITY_IS_HEAD_COMPANY_VALUE'] === 'Y' ||
                        $userCompany['PROPERTY_LEGAN_ENTITY_IS_HEAD_COMPANY_VALUE'] === 'Да')) {

                    // Это головная компания холдинга
                    $holdingKey = 'head_' . $userCompany['ID'];

                    // Проверяем, не обработали ли мы уже этот холдинг
                    if (in_array($holdingKey, $processedHoldings)) {
                        continue;
                    }

                    $headCompany = $userCompany;

                    // Получаем все дочерние компании этого холдинга
                    $rsHoldingCompanies = CIBlockElement::GetList(
                        [],
                        [
                            'IBLOCK_ID' => 23,
                            'PROPERTY_LEGAN_ENTITY_ID_OF_HEAD_COMPANY' => $userCompany['ID']
                        ],
                        false,
                        false,
                        ['ID']
                    );

                    while ($holdingCompany = $rsHoldingCompanies->GetNext()) {
                        $childCompanies[] = $holdingCompany['ID'];
                    }

                } else if (!empty($userCompany['PROPERTY_LEGAN_ENTITY_ID_OF_HEAD_COMPANY_VALUE'])) {

                    // Это дочерняя компания холдинга
                    $holdingId = $userCompany['PROPERTY_LEGAN_ENTITY_ID_OF_HEAD_COMPANY_VALUE'];
                    $holdingKey = 'head_' . $holdingId;

                    // Проверяем, не обработали ли мы уже этот холдинг
                    if (in_array($holdingKey, $processedHoldings)) {
                        continue;
                    }

                    // Получаем головную компанию
                    $rsHeadCompany = CIBlockElement::GetById($holdingId);
                    if ($headCompanyData = $rsHeadCompany->GetNext()) {
                        $headCompany = $headCompanyData;
                    }

                    // Получаем все дочерние компании этого холдинга
                    $rsHoldingCompanies = CIBlockElement::GetList(
                        [],
                        [
                            'IBLOCK_ID' => 23,
                            'PROPERTY_LEGAN_ENTITY_ID_OF_HEAD_COMPANY' => $holdingId
                        ],
                        false,
                        false,
                        ['ID']
                    );

                    while ($holdingCompany = $rsHoldingCompanies->GetNext()) {
                        $childCompanies[] = $holdingCompany['ID'];
                    }

                } else {
                    // Компания без холдинга - отдельное дерево
                    $holdingKey = 'standalone_' . $userCompany['ID'];

                    // Проверяем, не обработали ли мы уже эту компанию
                    if (in_array($holdingKey, $processedHoldings)) {
                        continue;
                    }

                    $headCompany = $userCompany;
                }

                // Добавляем холдинг в список
                if ($headCompany) {
                    $holdingsData[] = [
                        'head_company' => $headCompany,
                        'child_companies' => $childCompanies
                    ];
                    $processedHoldings[] = $holdingKey;
                }
            }

            $companyAclService = \class_exists(\OnlineService\Site\Company::class)
                ? new \OnlineService\Site\Company()
                : null;

            ?>

            <?php if (!empty($holdingsData)): ?>
                <?php foreach ($holdingsData as $holdingIndex => $companiesData): ?>
                    <?php
                    $headCompanyData = $companiesData['head_company'];
                    $headId = (int)$headCompanyData['ID'];
                    $childCompanyIds = $companiesData['child_companies'] ?? [];

                    $dirNoLinks = false;
                    $dirFullTree = false;
                    $dirRestrictedId = null;

                    if ($isDirectorUser) {
                        $bossIdInHolding = null;
                        if (!empty($bossCompanyIds[$headId])) {
                            $bossIdInHolding = $headId;
                        } else {
                            foreach ($childCompanyIds as $_cid) {
                                $_cid = (int)$_cid;
                                if (!empty($bossCompanyIds[$_cid])) {
                                    $bossIdInHolding = $_cid;
                                    break;
                                }
                            }
                        }
                        if ($bossIdInHolding === null) {
                            $dirNoLinks = true;
                        } else {
                            $rsBossEl = CIBlockElement::GetById($bossIdInHolding);
                            if ($bossEl = $rsBossEl->GetNextElement()) {
                                $bossPropsForFlag = $bossEl->GetProperties();
                                if ($companiesPhpIsHeadOfHolding($bossPropsForFlag)) {
                                    $dirFullTree = true;
                                } else {
                                    $dirRestrictedId = $bossIdInHolding;
                                }
                            } else {
                                $dirNoLinks = true;
                            }
                        }
                    }
                    ?>
                    <div class="companies-compact <?= $holdingIndex > 0 ? 'companies-compact--additional' : '' ?>">
                        <!-- Головная компания -->
                        <?php
                        $rsHeadCompany = CIBlockElement::GetById($headCompanyData['ID']);
                        if ($headCompanyElement = $rsHeadCompany->GetNextElement()) {
                            $headCompanyProps = $headCompanyElement->GetProperties();
                            $headCompanyFields = $headCompanyElement->GetFields();

                            $isMarketingAgent = $headCompanyProps['OS_IS_MARKETING_AGENT']['VALUE_XML_ID'] ?? '';
                            $isHeadOfHolding = $headCompanyProps['LEGAN_ENTITY_IS_HEAD_COMPANY']['VALUE_XML_ID'] ?? '';
                            $companyName = $headCompanyFields['NAME'];
                            $detailUrl = $headCompanyFields['DETAIL_PAGE_URL'];

                            $userIsMemberOfChildInHolding = false;
                            foreach ($userCompanies as $uc) {
                                $ucId = (int) ($uc['ID'] ?? 0);
                                if ($ucId <= 0 || $ucId === $headId || empty($userDirectCompanyIds[$ucId])) {
                                    continue;
                                }
                                $ucHeadId = (int) ($uc['PROPERTY_LEGAN_ENTITY_ID_OF_HEAD_COMPANY_VALUE'] ?? 0);
                                if ($ucHeadId === $headId) {
                                    $userIsMemberOfChildInHolding = true;
                                    break;
                                }
                            }

                            $headHasProfileAccess = $userIsMemberOfChildInHolding;
                            if ($companyAclService !== null) {
                                $headHasProfileAccess = $headHasProfileAccess
                                    || !empty($companyAclService->checkProfileViewPermission($headId, $currentUserId)['has_access']);
                            }
                            $headUseLink = ($isMarketingAgent == 'YES') && $headHasProfileAccess;
                            ?>
                            <?php if ($headUseLink) { ?>
                            <a href="<?=$detailUrl?>" class="company-item company-item--head">
                            <?php } else { ?>
                            <div class="company-item company-item--head">
                            <?php } ?>
                                <div class="company-item__content">
                                    <div class="company-item__name"><?=$companyName?></div>
                                    <div class="company-item__badges">
                            <span class="badge badge--<?=($isMarketingAgent == 'YES') ? 'active' : 'inactive'?>">
                                <?=($isMarketingAgent == 'YES') ? 'Активно' : 'На модерации'?>
                            </span>
                                        <?if($isHeadOfHolding == 'YES'):?>
                                            <span class="badge badge--head">Головная</span>
                                        <?endif;?>
                                    </div>
                                </div>
                            <?php if ($headUseLink) { ?>
                            </a>
                            <?php } else { ?>
                            </div>
                            <?php } ?>
                        <?php } ?>

                        <!-- Дочерние компании -->
                        <?php if (!empty($companiesData['child_companies'])): ?>
                            <?php foreach ($companiesData['child_companies'] as $childId): ?>
                                <?php
                                $rsChildCompany = CIBlockElement::GetById($childId);
                                if ($childElement = $rsChildCompany->GetNextElement()) {
                                    $childProps = $childElement->GetProperties();
                                    $childFields = $childElement->GetFields();

                                    $isMarketingAgent = $childProps['OS_IS_MARKETING_AGENT']['VALUE_XML_ID'] ?? '';
                                    $companyName = $childFields['NAME'];
                                    $detailUrl = $childFields['DETAIL_PAGE_URL'];
                                    $childIdInt = (int)$childId;

                                    $childUseLink = false;
                                    if (!$isDirectorUser) {
                                        $childUseLink = ($isMarketingAgent == 'YES');
                                    } elseif ($dirNoLinks) {
                                        $childUseLink = false;
                                    } elseif ($dirFullTree) {
                                        $childUseLink = ($isMarketingAgent == 'YES');
                                    } else {
                                        $childUseLink = ($childIdInt === (int)$dirRestrictedId);
                                    }
                                    ?>
                                    <?php if ($childUseLink) { ?>
                                        <a href="<?=$detailUrl?>" class="company-item company-item--child">
                                    <?php } else { ?>
                                        <div class="company-item company-item--child">
                                    <?php } ?>
                                            <div class="company-item__content">
                                                <div class="company-item__name"><?=$companyName?></div>
                                                <div class="company-item__badges">
                                        <span class="badge badge--<?=($isMarketingAgent == 'YES') ? 'active' : 'inactive'?>">
                                            <?=($isMarketingAgent == 'YES') ? 'Активно' : 'На модерации'?>
                                        </span>
                                                </div>
                                            </div>
                                    <?php if ($childUseLink) { ?>
                                        </a>
                                    <?php } else { ?>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="companies-empty">
                    <p>Компании не найдены</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>