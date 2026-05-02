# n8n: мост Bitrix24 → сайт для `UPDATE_COMPANY`

Событие на портале B24 → workflow n8n → **HTTP POST** на входящий канал сайта.

## Endpoint

- Путь: `OnlineService\Sync\Config\CrmInboundEndpoint::HTTP_PATH` → фактически  
  `{schema}://{host}/local/modules/eklektika.sync/public/inbound_crm.php`
- Заголовок: `X-Sync-Token` или параметр `sync_token` — как у вашего `InboundSecurity` / конфиг секрета.
- Тело: поддерживается **`Content-Type: application/json`** — скрипт `inbound_crm.php` читает `php://input` и мержит декод с query-параметрами (`sync_token` из URL перекрывает одноимённые ключи из JSON).
- Альтернатива: `application/x-www-form-urlencoded` без JSON.

## Конверт `ACTION` + `FIELDS`

Допустимо присылать данные отдельно от действия:

1. Объект: `{ "ACTION": "UPDATE_COMPANY", "FIELDS": { ...поля компании... } }` — после нормализации поля из `FIELDS` оказываются на одном уровне с `ACTION`.
2. Массив из одной записи: `[ { "ACTION": "...", "FIELDS": { ... } } ]` — сначала разворачивается один элемент, затем применяется п.1.

Реализация: `InboundGateway::normalizeInboundEnvelope`.

## Минимально обязательное

| Поле | Значение |
|------|----------|
| `ACTION` | `UPDATE_COMPANY` |
| `OS_COMPANY_B24_ID` | ID компании в CRM (строка/число), без него `Company::updateCompanyElement` вернёт `false`. |

## Нормализация из события CRM в контракт сайта

Класс `Company` при обновлении подмешивает в свойства ИБ 23 только ключи из внутреннего списка `$codeProps` (см. `modules/eklektika.company/lib/Company.php`). Поле **`TITLE`** из CRM (часто внутри `FIELDS`) **автоматически подставляется** в `OS_COMPANY_NAME`, если последнее пусто или отсутствует.

Рекомендуемые дополнительные преобразования в n8n (узел Set / Code), если данные неполные:

| Из B24 / ваш черновик JSON | В тело запроса на сайт |
|----------------------------|-------------------------|
| `TITLE` | можно не дублировать вручную — см. выше; при желании явно задайте `OS_COMPANY_NAME` |
| только `LEGAN_ENTITY_IS_HEAD_COMPANY` | → продублировать в `OS_COMPANY_IS_HEAD_OF_HOLDING` (boolean / `{VALUE}` по контракту свойства) |
| только `LEGAN_ENTITY_ID_OF_HEAD_COMPANY` | → продублировать в `OS_HOLDING_OF` (число ID элемента головной компании на сайте или логика из CRM) |
| `LEGAN_ENTITY_USERS` | **ID пользователей сайта (`b_user.ID`)**, как в UF `UF_CRM_1776075126830`. Резолв в `updateCompanyElement`: при отсутствии **`CONTACT_IDS`** числа трактуются как готовые `b_user.ID`, если такая учётка существует. Рекомендуется дублировать **`OS_COMPANY_USERS`** тем же списком. |
| `OS_IS_MARKETING_AGENT` | свойство ИБ — список; входящие **`true`** / «да» по тем же правилам, что UF CRM, приводятся к enum **`2076`** (`['VALUE' => 2076]` на этапе записи). Либо передайте уже id enum / `{VALUE}` |
| `LEGAN_ENTITY_IS_HEAD_COMPANY` / `OS_COMPANY_IS_HEAD_OF_HOLDING` | список; **`true`** → enum **`2074`**. Если задана только одна из пары OS/LEGAN — вторая копируется до merge (как для активности) |

После слияния вызывается `mirrorOsCompanyFieldsToLeganEntity`: он **дополняет LEGAN из OS**, но **не наоборот**. Для пар **`OS_COMPANY_ACTIVITY` ↔ `LEGAN_ENTITY_ACTIVITY`** и **`OS_COMPANY_IS_HEAD_OF_HOLDING` ↔ `LEGAN_ENTITY_IS_HEAD_COMPANY`** сайт сам дублирует отсутствующую сторону из payload, чтобы mirror не затёр пришедшее только LEGAN значением OS из ИБ.

### UF CRM компании → поля сайта (фрагмент)

Обрабатывается в `Company::mapCrmCompanyPayloadUfToSiteProperties`:  
`UF_CRM_1774915252680` (`COMPANY_IS_ADVERTISING_AGENT_UF`) → `OS_IS_MARKETING_AGENT`;  
`UF_CRM_1775030726726` — головная компания → `OS_COMPANY_IS_HEAD_OF_HOLDING` (в т.ч. **boolean** из REST);  
`UF_CRM_1777119807943` — сфера деятельности → `OS_COMPANY_ACTIVITY` (+ зеркало `LEGAN_ENTITY_ACTIVITY` через merge/sync выше).

### `ACTIVE` элемента компании и учёток из `OS_COMPANY_USERS` / `LEGAN_ENTITY_USERS`

Если в запросе **нет** явного поля `ACTIVE`, но есть **`OS_IS_MARKETING_AGENT`** (в т.ч. после маппинга UF рекламного агента), то **`ACTIVE`** элемента ИБ выводится из признака агента: «да» / enum **`2076`** → **`Y`**, явное «нет» / другой id списка → **`N`**. Явный **`ACTIVE`** в JSON всегда имеет приоритет.

После успешного `Update` / `Add`: при **`ACTIVE=Y`** для пользователей из обоих списков вызывается активация (`b_user.ACTIVE=Y`); при **`ACTIVE=N`**, если в этом же запросе переданы **`ACTIVE`** или **`OS_IS_MARKETING_AGENT`**, для тех же пользователей выставляется **`b_user.ACTIVE=N`** (частичные апдейты без этих ключей не трогают флаг учёток).

### Скидка: `DISCOUNT_GROUP` и группы сайта

Поле **`DISCOUNT_GROUP`** (число — значение списка CRM **`UF_CRM_1777030197`**, напр. `28` = 30%) копируется в **`OS_COMPANY_DISCOUNT_VALUE`**, если последний ещё не задан. Значение **`null`** или пустое означает явный **сброс скидки**: в **`OS_COMPANY_DISCOUNT_VALUE`** попадает пустая строка, у пользователей компании снимаются скидочные группы сайта из маппинга; для головной компании холдинга сброс дополнительно обходит пользователей дочерних компаний (аналогично распространению положительной скидки). Если в том же запросе приходит списковое UF **`UF_CRM_1777030197`**, оно **не перезаписывает** сброс: приоритет у **`DISCOUNT_GROUP`**. Пользователи из **`UF_CRM_1776075126830`** участвуют в снятии/назначении групп в том же порядке, что и **`LEGAN_ENTITY_USERS`** (merge в `params` до обработки скидки). Дальше для ненулевого значения сработает маппинг `CompanyModuleConfig::getCompanyStatusGroupIdMap()` (например **`28 → 11`** в `b_group` для 30%) в `resolveUpdatedCompanyDiscountTargetGroupId` / `applyB24CompanyGroupsToUser` — выставляется не более одной скидочной группы плюс группа маркетингового агента при необходимости. Полная таблица enum CRM → ID группы сайта: [`crm-uf-site-mapping.md`](crm-uf-site-mapping.md) (блок про скидку).

### Файл реквизитов (`LEGAN_ENTITY_FILE`)

Строка **`/upload/...`** на портале CRM или **полный HTTPS URL** с тем же host, что у **`URL_B24`**, и с тем же путём `/upload/...`: перед записью в свойство типа «файл» выполняется скачивание (`URL_B24` + путь, сегменты пути кодируются для кириллицы в имени файла), в ИБ сохраняется **ID файла** (`OS_REQUSITES_FILE` / зеркало `LEGAN_ENTITY_FILE`).

## Карта зеркала OS → LEGAN (фрагмент)

См. `Company::mirrorOsCompanyFieldsToLeganEntity`: например  
`OS_COMPANY_JUR_ADDRESS` → `LEGAN_ENTITY_ADRESS`,  
`OS_COMPANY_ACTIVITY` → `LEGAN_ENTITY_ACTIVITY`,  
`OS_COMPANY_IS_HEAD_OF_HOLDING` → `LEGAN_ENTITY_IS_HEAD_COMPANY`.

Поля **`LEGAN_ENTITY_ADRESS`**, **`LEGAN_ENTITY_ACTIVITY`**, **`LEGAN_ENTITY_INN`**, **`LEGAN_ENTITY_WWW`**, **`LEGAN_ENTITY_CITY`**, **`LEGAN_ENTITY_EMAIL`**, **`LEGAN_MAIN_PHONE`**, **`LEGAN_MOBILE_PHONE`**, **`LEGAN_ENTITY_BOSS`** входят в `$codeProps` и могут приходить с CRM **напрямую**, если имена совпадают.

## Ответ сайта

Успех: JSON с `success: 1`, внутри `data.company_id` — ID элемента ИБ 23 (см. `InboundGateway` / `Company::updateCompanyElement`).

## Ссылки

- Вход: `modules/eklektika.sync/lib/from-crm/InboundGateway.php`
- Обработчик компании: `modules/eklektika.company/lib/Company.php` → `updateCompanyElement`
- Карта UF CRM ↔ сайт: `docs/reference/crm-uf-site-mapping.md`
