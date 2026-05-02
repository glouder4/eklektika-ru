# Соответствие UF CRM (Bitrix24) ↔ сайт (компания + пользователь)

Сводка по **константам в коде** для входящего sync CRM→сайт, исходящей регистрации сайт→CRM и полям пользователя для ID контакта. Детали канала и endpoint см. в [`modules/eklektika.sync/docs/functional-contract.md`](../../modules/eklektika.sync/docs/functional-contract.md).

Источники в коде:

| Область | Класс | Путь |
|--------|--------|------|
| Входящий CRM→сайт (маппинг UF, подготовка `b_user`) | `OnlineService\Sync\FromCrm\CrmInboundUfMap` | `modules/eklektika.sync/lib/from-crm/CrmInboundUfMap.php` |
| Регистрация: поля для `crm.contact` / `crm.company` | `OnlineService\B24\Registration\Config\RegisterUserCompanyConfig` | `modules/eklektika.b24.registration/lib/Config/RegisterUserCompanyConfig.php` |
| Пользователь: UF с ID контакта B24 | `OnlineService\B24\UserSync\Config\UserSyncConfig` | `modules/eklektika.b24.usersync/lib/Config/UserSyncConfig.php` |

---

## (а) Контакт CRM (`crm.contact`) ↔ пользователь сайта (`b_user`)

Поля контакта с префиксом `UF_CRM_*`, которые во входящем payload преобразуются в поля пользователя (`CrmInboundUfMap::prepareUserUpdatePayload`). Прочие `UF_CRM_*` на пользователе удаляются перед `CUser::Update`.

| Смысл (по комментариям в коде) | UF контакта CRM (константа) | Код поля | Поле на сайте (`b_user`) |
|-------------------------------|-----------------------------|----------|---------------------------|
| «Рекламный агент» | `CONTACT_ADVERTISING_AGENT_UF` | `UF_CRM_1775034008956` | `UF_ADVERSTERING_AGENT` (int 0/1; опечатка в имени поля сайта сохранена) |
| «Руководитель компании» | `CONTACT_IS_DIRECTOR_UF` | `UF_CRM_1777068292434` | `UF_IS_DIRECTOR` (int 0/1) |

### Входящий `UPDATE_MANAGER` (карточка менеджера, ИБ 24)

Обрабатывается классом **`OnlineService\Site\Manager`** (`ACTION` маршрутизирует в `InboundGateway`). Связь с CRM — свойство элемента **`BITRIX24_ID`** (значение из полей **`BITRIX24_ID`** или **`ID`** в теле). Превью фото: относительный **`PERSONAL_PHOTO`** загружается с хоста CRM (**константа `URL_B24`**, прод по умолчанию совпадает с порталом Bitrix24).

| Поле входящего payload | Назначение |
|------------------------|------------|
| `BITRIX24_ID` / `ID` | Идентификатор менеджера в CRM → свойство **`BITRIX24_ID`** элемента |
| `NAME`, `LAST_NAME` | **`NAME`** элемента (конкатенация с пробелом) |
| `PHONE`, `EMAIL`, `POSITION` | Свойства элемента (`WORK_POSITION` для должности) |
| `PERSONAL_PHOTO` | Относительный URL файла на CRM → **`PREVIEW_PICTURE`** |
| `IS_PERSONAL_MANAGER` | Активность элемента: `false` — не создавать новый элемент; у существующего обновить поля и **`ACTIVE=N`**. Ключ отсутствует — считается «да» (обратная совместимость). |

---

Дополнительно (не таблица `CrmInboundUfMap`, но часть связки контакт ↔ пользователь):

| Назначение | Поле на сайте (`b_user`) | Константа |
|------------|--------------------------|-----------|
| ID контакта в Bitrix24 (входящие `UPDATE_CONTACT` и т.п.) | `UF_BITRIX24_ID` | `UserSyncConfig::USER_UF_CONTACT_B24_ID` |
| Легаси / резерв при поиске и дублирование при привязке | `UF_B24_USER_ID` | `UserSyncConfig::USER_UF_CONTACT_B24_ID_LEGACY` |

Тот же код `UF_CRM_1776075126830`, что у списка пользователей сайта на **компании** CRM: см. `CrmInboundUfMap::COMPANY_SITE_USER_IDS_UF` и `RegisterUserCompanyConfig::CRM_CONTACT_SITE_USER_ID_FIELD` — на контакте дублирует связь пользователя с сайтом.

### Входящий `UPDATE_CONTACT`: менеджеры (`UF_PERSONAL_MANAGER_*`)

В теле `ACTION=UPDATE_CONTACT` поля **`UF_PERSONAL_MANAGER_1`** и **`UF_PERSONAL_MANAGER_2`** содержат **опорное значение из CRM** (совпадает со значением свойства **`BITRIX24_ID`** элемента инфоблока карточек менеджера на сайте). Перед `CUser::Update` сайт подставляет **локальный ID элемента ИБ**, найденный по фильтру «ИБ + `PROPERTY_BITRIX24_ID` = значение из CRM».

| Константа | Значение | Назначение |
|-----------|----------|------------|
| `UserSyncConfig::MANAGER_CARD_IBLOCK_ID` | `24` | Инфоблок карточек менеджера |
| `UserSyncConfig::MANAGER_CARD_BITRIX24_PROPERTY_CODE` | `BITRIX24_ID` | Свойство элемента: внешний идентификатор для сопоставления с CRM |
| `UserSyncConfig::USER_UF_PERSONAL_MANAGER_1` | `UF_PERSONAL_MANAGER_1` | UF пользователя на сайте (после резолва — ID элемента ИБ) |
| `UserSyncConfig::USER_UF_PERSONAL_MANAGER_2` | `UF_PERSONAL_MANAGER_2` | То же для второго менеджера |

Если элемент по `BITRIX24_ID` не найден, соответствующий ключ **не передаётся** в `CUser::Update` (чтобы не записать «чужой» ID); при включённом trace фиксируется событие `personal_manager_card_not_found`.

Дополнительно: при наличии **`CONTACT_ID`** во входящем JSON он используется как **ID контакта CRM** для поиска пользователя сайта и имеет приоритет над полем **`ID`** (удобно для выгрузок n8n, где `ID` — служебный ключ строки).

### Входящий `UPDATE_CONTACT`: руководитель и связанные компании (`ASSOCIATED_WITH_ENTITY`)

Если во входящем теле есть **`UF_IS_DIRECTOR`** и массив **`ASSOCIATED_WITH_ENTITY`**, каждый элемент массива трактуется как **ID компании в Bitrix24** — им соответствует **`CODE`** элемента ИБ 23 (поиск в `Company::getCompanyByB24ID()` сначала по `CODE`; свойство **`OS_COMPANY_B24_ID`** на карточке хранит локальный ID элемента и для сопоставления с CRM не используется).

| Константа | Значение |
|-----------|----------|
| `UserSyncConfig::CONTACT_ASSOCIATED_COMPANY_B24_IDS_FIELD` | `ASSOCIATED_WITH_ENTITY` |

**Смысл поля:** в каких компаниях пользователь является **сотрудником**. Сайт **при необходимости добавляет** пользователя (тот же идентификатор, что для списков руководителей при необходимости — см. `UF_CRM_1776075126830`) в **`LEGAN_ENTITY_USERS`** каждой найденной компании; если пользователь **уже привязан**, свойство **не обновляется** (лишняя запись в ИБ не выполняется). **Снятие `UF_IS_DIRECTOR` не удаляет** пользователя из **`LEGAN_ENTITY_USERS`** по этому полю — исключение из руководителей обрабатывается отдельно в **`LEGAN_ENTITY_BOSS`** / **`OS_COMPANY_BOSS`** (`syncLeganAndOsCompanyBossForEmployeeFromCrm`).

До `CUser::Update` поле **`ASSOCIATED_WITH_ENTITY`** удаляется из payload.

Если элемент компании по переданному B24 ID не найден, запись в ИБ не меняется; при включённом trace возможна метка `associated_company_not_found`.

---

### `UPDATE_COMPANY`: идентификаторы и холдинг

- **`OS_COMPANY_B24_ID`** во входящем JSON — ID компании в CRM для поиска строки в ИБ 23; в **свойстве** элемента `OS_COMPANY_B24_ID` на сайте сохраняется **локальный numeric ID** элемента (не число CRM).
- **`OS_HEAD_COMPANY_B24_ID`** — без преобразования, значение из CRM (как приходит).
- **`OS_HOLDING_OF`** — если передано числом как у `OS_COMPANY_B24_ID` головной компании в CRM, резолвится в **ID элемента** головной компании на сайте (`Company::getCompanyByB24ID`).
- **`DISCOUNT_GROUP`** (`null` или пусто во входящем JSON): в параметры попадает пустое **`OS_COMPANY_DISCOUNT_VALUE`** — у сотрудников компании снимаются **скидочные группы** из маппинга (`Company::applyB24CompanyGroupsToUser`); для **головной** компании холдинга то же распространяется на пользователей **дочерних** компаний в том же объёме, что и выставление скидки. Списковое UF **`UF_CRM_1777030197`** в том же теле **не отменяет** этот сброс; **`UF_CRM_1776075126830`** мержится в состав пользователей для скидки до записи ИБ (вместе с **`LEGAN_ENTITY_USERS`**). Признак головной компании учитывается и по **`OS_COMPANY_IS_HEAD_OF_HOLDING`**, и по **`LEGAN_ENTITY_IS_HEAD_COMPANY`**; наследование скидки на дочерние подбирает карточки **строго по `OS_HOLDING_OF` = ID элемента головной компании** (без опоры только на `LEGAN_ENTITY_ID_OF_HEAD_COMPANY`; неактивные дочерние элементы тоже включаются в пересчёт групп). При назначении скидки головной компанией пользователи **дочерних** карточек (в т.ч. только в **`LEGAN_ENTITY_USERS`**) получают ту же скидочную группу без ограничения по признаку директора для контекста «дочерняя карточка».

Поиск компании по CRM по-прежнему опирается на **`CODE`** элемента (= внешний id) и при необходимости на свойство с **легаси**-хранением CRM id; исходящие вызовы CRM для профиля реквизитов берут id через **`CODE`** (см. `Company::resolveOutboundBitrix24CompanyId`).

---

## (б) Компания CRM (`crm.company`) ↔ карточка компании на сайте (ИБ / свойства)

Константы из `CrmInboundUfMap` для `UPDATE_COMPANY` и реквизитов. Свойства элемента инфоблока: префиксы `LEGAN_*`, `OS_*` — как в комментариях к классу.

| Смысл | Константа | Код UF CRM | Назначение на сайте (константы свойств / поведение) |
|-------|-----------|------------|-----------------------------------------------------|
| Рекламный агент (компания) | `COMPANY_IS_ADVERTISING_AGENT_UF` | `UF_CRM_1774915252680` | Обработчики UPDATE / реквизиты (см. код модуля sync) |
| Головная компания холдинга | `COMPANY_IS_HEAD_OF_HOLDING_UF` | `UF_CRM_1775030726726` | — |
| Холдинг (связь с головной компанией); при расхождении с порталом проверить полный код в CRM | `COMPANY_HOLDING_UF` | `UF_CRM_1775032393` | — |
| Скидка компании | `COMPANY_DISCOUNT_UF` | `UF_CRM_1777030197` | Источник для `OS_COMPANY_DISCOUNT_VALUE` |
| Фирмы холдинга (множественное) | `COMPANY_HOLDING_COMPANIES_UF` | `UF_CRM_1777030108` | — |
| ID элемента ИБ карточки компании | `COMPANY_SITE_IBLOCK_ELEMENT_ID_UF` | `UF_CRM_1774915439581` | Связь CRM ↔ карточка на сайте |
| ID пользователей сайта (`b_user.ID`) в payload | `COMPANY_SITE_USER_IDS_UF` | `UF_CRM_1776075126830` | Совпадает с `RegisterUserCompanyConfig::CRM_CONTACT_SITE_USER_ID_FIELD` на контакте |
| Основной телефон | `COMPANY_CRM_MAIN_PHONE_UF` | `UF_CRM_1777069666894` | `LEGAN_MAIN_PHONE` |
| Мобильный телефон | `COMPANY_CRM_MOBILE_PHONE_UF` | `UF_CRM_1777069676348` | `LEGAN_MOBILE_PHONE` |
| Город | `COMPANY_CRM_CITY_UF` | `UF_CRM_1775034571084` | `OS_COMPANY_CITY` и зеркало `LEGAN_ENTITY_CITY` |
| Веб-сайт | `COMPANY_CRM_WEB_SITE_UF` | `UF_CRM_1777119084064` | `OS_COMPANY_WEB_SITE` и зеркало `LEGAN_ENTITY_WWW` |
| Сфера деятельности | `COMPANY_CRM_ACTIVITY_UF` | `UF_CRM_1777119807943` | `OS_COMPANY_ACTIVITY` и зеркало `LEGAN_ENTITY_ACTIVITY` |
| Юридический адрес | `COMPANY_CRM_JUR_ADDRESS_UF` | `UF_CRM_1777120939583` | `OS_COMPANY_JUR_ADDRESS` и зеркало `LEGAN_ENTITY_ADRESS` |

Значения списка **`UF_CRM_1777030197`** (enum в CRM) → **`b_group.ID`** на сайте задаются в `CompanyModuleConfig::COMPANY_STATUS_GROUP_ID_MAP` и возвращаются через `CompanyModuleConfig::getCompanyStatusGroupIdMap()`:

| Значение UF (enum CRM) | Подпись в CRM | ID группы сайта (`b_group`) |
|------------------------|----------------|----------------------------|
| 26 | 20% | 9 |
| 27 | 25% | 10 |
| 28 | 30% | 11 |
| 29 | 32% | 12 |
| 30 | 35% | 13 |
| 31 | 37% | 14 |
| 32 | 38% | 15 |
| 33 | 40% | 16 |

---

## (в) Регистрация сайт → CRM (`RegisterUserCompanyConfig`)

Поля, используемые при создании сущностей в CRM из сценария регистрации (значения — коды UF в портале на момент фиксации в коде).

| Назначение | Константа | Код UF CRM |
|------------|-----------|------------|
| Ответственный (числовой ID в B24) | `ASSIGNED_BY_ID` | *(не UF; ID пользователя CRM)* `3036` |
| Город контакта | `CRM_CONTACT_CITY_FIELD` | `UF_CRM_3804624445810` |
| Примечание / пометка регистрации | `CRM_CONTACT_NOTE_FIELD` | `UF_CRM_1701839165901` |
| ID пользователя сайта на контакте | `CRM_CONTACT_SITE_USER_ID_FIELD` | `UF_CRM_1776075126830` |
| Рекламный агент на контакте | `CRM_CONTACT_AD_AGENT_FIELD` | `UF_CRM_1698752707853` |
| Руководитель компании на контакте (то же UF, что `CrmInboundUfMap::CONTACT_IS_DIRECTOR_UF`) | `CRM_CONTACT_IS_DIRECTOR_FIELD` | `UF_CRM_1777068292434` |
| Сфера деятельности компании | `CRM_COMPANY_SPHERE_FIELD` | `UF_CRM_1777119807943` |
| Юридический адрес компании | `CRM_COMPANY_JUR_ADDRESS_FIELD` | `UF_CRM_1777120939583` |
| Город компании | `CRM_COMPANY_CITY_FIELD` | `UF_CRM_1618551330657` |
| Веб-сайт компании | `CRM_COMPANY_WEB_FIELD` | `UF_CRM_1777119084064` |
| Основной телефон компании | `CRM_COMPANY_MAIN_PHONE_FIELD` | `UF_CRM_1777069666894` |
| Мобильный телефон компании | `CRM_COMPANY_MOBILE_PHONE_FIELD` | `UF_CRM_1777069676348` |
| Файл реквизитов (`getRequisitesFileField()`) | `CRM_REQUISITES_FILE_FIELD` | `UF_CRM_1775033868000` |

Строки по умолчанию для пометок: `REGISTRATION_NOTE_DEFAULT`, `REGISTRATION_NOTE_AD_AGENT` — текстовые константы, не UF.

---

## Расхождение: город компании (два разных UF в коде)

| Контекст | Константа | Код UF |
|----------|-----------|--------|
| Входящий sync компании CRM→сайт (`CrmInboundUfMap`) | `COMPANY_CRM_CITY_UF` | `UF_CRM_1775034571084` |
| Исходящая регистрация сайт→CRM (`RegisterUserCompanyConfig`) | `CRM_COMPANY_CITY_FIELD` | `UF_CRM_1618551330657` |

Это **разные идентификаторы**. В документе зафиксировано фактическое состояние кода; намерение (одно поле vs два на портале, устаревшая константа, разные сценарии) нужно **подтвердить с конфигурацией Bitrix24** и при необходимости выровнять код или документировать бизнес-правило.

---

## Связанные документы

- [`modules/eklektika.sync/docs/functional-contract.md`](../../modules/eklektika.sync/docs/functional-contract.md) — контракт входящего sync и ссылка на этот справочник.
