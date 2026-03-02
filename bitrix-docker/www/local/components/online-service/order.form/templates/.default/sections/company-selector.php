<?php
/**
 * Выпадающий список компаний холдинга для формы заказа.
 * Показывает головную компанию и ВСЕ дочерние. По умолчанию — компания, в которой пользователь является сотрудником.
 * Данные выбранной компании подставляются в off_company, off_phone, off_email, off_requisites.
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $USER;

$orderCompanies = [];
$defaultCompanyId = null;
$companyDataForForm = null;

if ($USER->IsAuthorized() && \Bitrix\Main\Loader::includeModule('iblock')) {
    $iblockId = 23;

    // Компании, в которых пользователь является сотрудником или руководителем
    $rsCompanies = CIBlockElement::GetList(
        [],
        [
            'IBLOCK_ID' => $iblockId,
            [
                'LOGIC' => 'OR',
                'PROPERTY_LEGAN_ENTITY_USERS' => $USER->GetID(),
                'PROPERTY_LEGAN_ENTITY_BOSS' => $USER->GetID()
            ]
        ],
        false,
        false,
        ['ID', 'NAME', 'PROPERTY_LEGAN_ENTITY_IS_HEAD_COMPANY', 'PROPERTY_LEGAN_ENTITY_ID_OF_HEAD_COMPANY']
    );

    $userCompanyIds = [];
    $userCompanies = [];
    while ($company = $rsCompanies->GetNext()) {
        $userCompanyIds[] = $company['ID'];
        $userCompanies[$company['ID']] = $company;
    }

    // Собираем холдинги: головная + все дочерние
    $processedHeads = [];

    foreach ($userCompanies as $userCompany) {
        $headCompany = null;
        $childIds = [];

        if (!empty($userCompany['PROPERTY_LEGAN_ENTITY_IS_HEAD_COMPANY_VALUE'])
            && ($userCompany['PROPERTY_LEGAN_ENTITY_IS_HEAD_COMPANY_VALUE'] === 'Y'
                || $userCompany['PROPERTY_LEGAN_ENTITY_IS_HEAD_COMPANY_VALUE'] === 'Да')) {

            $headId = $userCompany['ID'];
            if (in_array($headId, $processedHeads)) continue;
            $processedHeads[] = $headId;

            $headCompany = $userCompany;
            $rsChildren = CIBlockElement::GetList(
                [],
                ['IBLOCK_ID' => $iblockId, 'PROPERTY_LEGAN_ENTITY_ID_OF_HEAD_COMPANY' => $headId],
                false,
                false,
                ['ID', 'NAME']
            );
            while ($child = $rsChildren->GetNext()) {
                $childIds[] = $child['ID'];
            }
        } elseif (!empty($userCompany['PROPERTY_LEGAN_ENTITY_ID_OF_HEAD_COMPANY_VALUE'])) {
            $headId = (int)$userCompany['PROPERTY_LEGAN_ENTITY_ID_OF_HEAD_COMPANY_VALUE'];
            if (in_array($headId, $processedHeads)) continue;
            $processedHeads[] = $headId;

            $rsHead = CIBlockElement::GetById($headId);
            if ($headData = $rsHead->GetNext()) {
                $headCompany = $headData;
            }
            $rsChildren = CIBlockElement::GetList(
                [],
                ['IBLOCK_ID' => $iblockId, 'PROPERTY_LEGAN_ENTITY_ID_OF_HEAD_COMPANY' => $headId],
                false,
                false,
                ['ID', 'NAME']
            );
            while ($child = $rsChildren->GetNext()) {
                $childIds[] = $child['ID'];
            }
        } else {
            $headId = $userCompany['ID'];
            if (in_array($headId, $processedHeads)) continue;
            $processedHeads[] = $headId;
            $headCompany = $userCompany;
        }

        if (!$headCompany) continue;

        // Головная компания
        $orderCompanies[] = [
            'ID' => $headCompany['ID'],
            'NAME' => $headCompany['NAME'],
            'IS_USER' => in_array($headCompany['ID'], $userCompanyIds),
        ];

        // Все дочерние
        foreach ($childIds as $childId) {
            $rsChild = CIBlockElement::GetById($childId);
            if ($childEl = $rsChild->GetNext()) {
                $orderCompanies[] = [
                    'ID' => $childEl['ID'],
                    'NAME' => $childEl['NAME'],
                    'IS_USER' => in_array($childEl['ID'], $userCompanyIds),
                ];
            }
        }
    }

    // По умолчанию — первая компания, в которой пользователь
    foreach ($orderCompanies as $c) {
        if ($c['IS_USER']) {
            $defaultCompanyId = $c['ID'];
            break;
        }
    }
    if (!$defaultCompanyId && !empty($orderCompanies)) {
        $defaultCompanyId = $orderCompanies[0]['ID'];
    }

    if ($defaultCompanyId && class_exists('OnlineService\Site\Company')) {
        $company = new \OnlineService\Site\Company();
        $raw = $company->getCompany($defaultCompanyId);
        if (!empty($raw)) {
            $phone = trim((string)($raw['LEGAN_ENTITY_PHONE'] ?? ''));
            if ($phone && !preg_match('/^(\+7|7|8)/', $phone)) $phone = '+7' . $phone;
            $req = [];
            if (!empty($raw['LEGAN_ENTITY_INN'])) $req[] = 'ИНН: ' . $raw['LEGAN_ENTITY_INN'];
            if (!empty($raw['LEGAN_ENTITY_NAME'])) $req[] = $raw['LEGAN_ENTITY_NAME'];
            if (!empty($raw['LEGAN_ENTITY_ADRESS'])) $req[] = $raw['LEGAN_ENTITY_ADRESS'];
            if (!empty($raw['LEGAN_ENTITY_CITY'])) $req[] = 'г. ' . $raw['LEGAN_ENTITY_CITY'];
            if (!empty($raw['LEGAN_ENTITY_FILE'])) {
                $f = CFile::GetFileArray($raw['LEGAN_ENTITY_FILE']);
                if ($f) $req[] = 'Файл реквизитов: ' . $f['ORIGINAL_NAME'] . ' (' . $f['SRC'] . ')';
            }
            $reqFileId = null;
            $reqFileName = '';
            if (!empty($raw['LEGAN_ENTITY_FILE'])) {
                $f2 = CFile::GetFileArray($raw['LEGAN_ENTITY_FILE']);
                if ($f2) {
                    $reqFileId = (int)$raw['LEGAN_ENTITY_FILE'];
                    $reqFileName = $f2['ORIGINAL_NAME'] ?? '';
                }
            }
            $companyDataForForm = [
                'off_company' => trim((string)($raw['LEGAN_ENTITY_NAME'] ?? '')),
                'off_phone' => $phone,
                'off_email' => trim((string)($raw['LEGAN_ENTITY_EMAIL'] ?? '')),
                'off_requisites' => implode("\n", $req),
                'requisites_file_id' => $reqFileId,
                'requisites_file_name' => $reqFileName,
            ];
        }
    }
}
?>
<?php if (!empty($orderCompanies)): ?>
<?php if (!empty($companyDataForForm['requisites_file_id'])): ?>
<script>document.addEventListener('DOMContentLoaded',function(){var e=document.getElementById('order_company_requisites_file_id');if(e)e.value='<?=(int)$companyDataForForm['requisites_file_id']?>';});</script>
<?php endif; ?>
<div class="row order-company-selector">
    <div class="col-md-4">
        <label for="order_company">От имени компании</label>
    </div>
    <div class="col-md-8">
        <select name="order_company" id="order_company" class="form-control" data-company-data-url="/local/components/online-service/order.form/get-company-data.php">
            <?php foreach ($orderCompanies as $c): ?>
            <option value="<?= (int)$c['ID'] ?>"<?= ($defaultCompanyId == $c['ID']) ? ' selected' : '' ?>>
                <?= htmlspecialchars($c['NAME']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<?php endif; ?>
