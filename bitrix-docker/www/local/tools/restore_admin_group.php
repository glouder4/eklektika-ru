<?php
/**
 * Одноразовое/сервисное: вернуть пользователю группу администраторов (ID = 1),
 * включить активность (ACTIVE=Y) и снять блокировку входа (BLOCKED=N).
 *
 * Запуск из корня сайта (DOCUMENT_ROOT = корень Битрикс):
 *   php local/tools/restore_admin_group.php
 *   php local/tools/restore_admin_group.php other@mail.ru
 *
 * После использования на проде файл лучше удалить или ограничить доступ.
 */
declare(strict_types=1);

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
if ($_SERVER['DOCUMENT_ROOT'] === false) {
    fwrite(STDERR, "Cannot resolve DOCUMENT_ROOT\n");
    exit(1);
}

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('CHK_EVENT', false);
define('BX_NO_ACCELERATOR_RESET', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!\CModule::IncludeModule('main')) {
    fwrite(STDERR, "Module main not loaded\n");
    exit(1);
}

$loginOrEmail = $argv[1] ?? 'admin';
$adminGroupId = 1;

/**
 * @return array<string, mixed>|null
 */
$findUser = static function (string $needle): ?array {
    $needle = trim($needle);
    if ($needle === '') {
        return null;
    }
    $select = ['FIELDS' => ['ID', 'LOGIN', 'EMAIL', 'NAME', 'LAST_NAME']];
    // В Bitrix OR-фильтр: вложенные массивы. Плоский '=LOGIN' рядом с LOGIC даёт неверную выборку / пустой результат.
    $filters = [
        [
            'LOGIC' => 'OR',
            ['=LOGIN' => $needle],
            ['=EMAIL' => $needle],
        ],
        [
            'LOGIC' => 'OR',
            ['LOGIN' => $needle],
            ['EMAIL' => $needle],
        ],
        ['LOGIN_EQUAL' => $needle],
        ['EMAIL' => $needle],
    ];
    foreach ($filters as $filter) {
        $rs = \CUser::GetList('id', 'asc', $filter, $select);
        if ($u = $rs->Fetch()) {
            return $u;
        }
    }

    return null;
};

$user = $findUser($loginOrEmail);
if (!$user) {
    echo "Пользователь не найден по LOGIN/EMAIL: {$loginOrEmail}\n";
    echo "Подсказка: укажите точный логин или e-mail, например: php local/tools/restore_admin_group.php user@example.com\n";
    echo "Первые 15 пользователей по ID (чтобы подсмотреть логин в этой базе):\n";
    $rsHint = \CUser::GetList('id', 'asc', ['>ID' => 0], [
        'FIELDS' => ['ID', 'LOGIN', 'EMAIL'],
        'NAV_PARAMS' => ['nTopCount' => 15],
    ]);
    while ($row = $rsHint->Fetch()) {
        echo "  ID={$row['ID']}, LOGIN={$row['LOGIN']}, EMAIL={$row['EMAIL']}\n";
    }
    exit(1);
}

$userId = (int)$user['ID'];
$groups = \CUser::GetUserGroup($userId);
if (!is_array($groups)) {
    $groups = $groups !== null && $groups !== '' && $groups !== false
        ? [(int)$groups]
        : [];
} else {
    $groups = array_map('intval', $groups);
}

if (in_array($adminGroupId, $groups, true)) {
    echo "Уже в группе {$adminGroupId}: user_id={$userId}, LOGIN={$user['LOGIN']}, EMAIL={$user['EMAIL']}\n";
    echo 'Текущие группы: ' . implode(',', $groups) . "\n";
} else {
    $groups[] = $adminGroupId;
    $groups = array_values(array_unique($groups));
    \CUser::SetUserGroup($userId, $groups);
    echo "Группа {$adminGroupId} добавлена: user_id={$userId}, LOGIN={$user['LOGIN']}, EMAIL={$user['EMAIL']}\n";
    echo 'Текущие группы: ' . implode(',', $groups) . "\n";
}

$cUser = new \CUser();
$activateFields = [
    'ACTIVE' => 'Y',
    'BLOCKED' => 'N',
];
$updated = $cUser->Update($userId, $activateFields);
if (!$updated) {
    $err = $cUser->LAST_ERROR !== '' ? $cUser->LAST_ERROR : 'unknown';
    echo "Предупреждение: ACTIVE/BLOCKED не применились: {$err}\n";
} else {
    echo "Активность и блокировка: ACTIVE=Y, BLOCKED=N применены для user_id={$userId}\n";
}
