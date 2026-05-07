<?php
/**
 * Выпадающий список компаний холдинга для формы заказа.
 * Показывает головную компанию и ВСЕ дочерние. По умолчанию — компания, в которой пользователь является сотрудником.
 * Данные выбранной компании подставляются в off_company, off_phone, off_email, off_requisites.
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/components/online-service/order.form/order_form_phone_normalize.php';

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
            'ACTIVE' => 'Y',
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

    // Собираем дерево холдинга по связке OS_HOLDING_OF: показываем ТОЛЬКО ACTIVE элементы.
    // OS_HOLDING_OF = родительская компания (элемент ИБ). Нужно собрать все активные узлы дерева.
    $holdingRootIds = [];
    $parentPropCode = 'OS_HOLDING_OF';

    $getParentId = static function(int $companyId) use ($iblockId, $parentPropCode): int {
        if ($companyId <= 0) return 0;

        $rs = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, 'ID' => $companyId],
            false,
            false,
            ['ID', 'PROPERTY_' . $parentPropCode]
        );
        $row = $rs->GetNext();
        if (!$row) return 0;

        $parentId = (int)($row['PROPERTY_' . $parentPropCode . '_VALUE'] ?? 0);
        return $parentId > 0 ? $parentId : 0;
    };

    foreach ($userCompanies as $userCompany) {
        $startId = (int)($userCompany['ID'] ?? 0);
        if ($startId <= 0) continue;

        // Поднимаемся к корню холдинга по OS_HOLDING_OF (корень может быть неактивным — это не важно для определения дерева)
        $visited = [];
        $curId = $startId;
        for ($i = 0; $i < 30; $i++) {
            if (isset($visited[$curId])) break;
            $visited[$curId] = true;
            $parentId = $getParentId($curId);
            if ($parentId <= 0) break;
            $curId = $parentId;
        }
        if ($curId > 0) {
            $holdingRootIds[$curId] = true;
        }
    }

    // Собираем всех активных потомков итеративно (BFS) от корня: root + дети + дети детей...
    $companiesMap = []; // [id => ['ID'=>..., 'NAME'=>..., 'IS_USER'=>...]]
    $queue = array_keys($holdingRootIds);

    // Добавим активные корни (если корень неактивен — просто не добавится, но дети всё равно подтянутся ниже)
    if (!empty($queue)) {
        $rsRoots = CIBlockElement::GetList(
            ['NAME' => 'ASC'],
            ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'ID' => $queue],
            false,
            false,
            ['ID', 'NAME']
        );
        while ($el = $rsRoots->GetNext()) {
            $id = (int)$el['ID'];
            $companiesMap[$id] = [
                'ID' => $id,
                'NAME' => (string)$el['NAME'],
                'IS_USER' => in_array($id, $userCompanyIds),
            ];
        }
    }

    $seen = [];
    while (!empty($queue)) {
        $parentIds = [];
        foreach (array_splice($queue, 0, 50) as $pid) {
            $pid = (int)$pid;
            if ($pid <= 0 || isset($seen[$pid])) continue;
            $seen[$pid] = true;
            $parentIds[] = $pid;
        }
        if (empty($parentIds)) {
            continue;
        }

        $rsChildren = CIBlockElement::GetList(
            ['NAME' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                'PROPERTY_' . $parentPropCode => $parentIds,
            ],
            false,
            false,
            ['ID', 'NAME', 'PROPERTY_' . $parentPropCode]
        );
        while ($child = $rsChildren->GetNext()) {
            $childId = (int)$child['ID'];
            if ($childId <= 0) continue;

            if (!isset($companiesMap[$childId])) {
                $companiesMap[$childId] = [
                    'ID' => $childId,
                    'NAME' => (string)$child['NAME'],
                    'IS_USER' => in_array($childId, $userCompanyIds),
                ];
            }

            // Добавляем в очередь, чтобы собрать следующий уровень.
            $queue[] = $childId;
        }
    }

    // Финальный список компаний в селектор
    if (!empty($companiesMap)) {
        uasort($companiesMap, static fn($a, $b) => strcmp((string)$a['NAME'], (string)$b['NAME']));
        $orderCompanies = array_values($companiesMap);
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
            $phone = order_form_normalize_ru_company_phone(order_form_company_phone_raw_from_ib($raw));
            $req = [];
            $inn = '';
            if (!empty($raw['LEGAN_ENTITY_INN'])){
                $req[] = 'ИНН: ' . $raw['LEGAN_ENTITY_INN'];
                $inn = $raw['LEGAN_ENTITY_INN'];
            }
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
                'off_inn' => $inn,
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
