# CRM: взаимная синхронизация UF телефонов и multifield `PHONE`

## Проблема

На портале Bitrix24 у компании дублируются телефоны:

| Хранилище | Поля |
|-----------|------|
| **Multifield** (вкладка «Телефон» в карточке) | `PHONE[]` с `VALUE_TYPE` = `WORK` / `MOBILE` |
| **Пользовательские поля** | `UF_CRM_1777069666894` — рабочий, `UF_CRM_1777069676348` — мобильный |

Bitrix **не синхронизирует** их сам: изменили UF — multifield может остаться старым; изменили только «Мобильный» в таблице multifield — UF рабочего не обновится.

Интеграция **сайт ↔ CRM** (`Company::buildBitrix24CompanyFieldsFromSiteData`, регистрация `buildB24CrmWorkPhoneAndEmailFields`) при **создании/обновлении с сайта** уже пишет **и** `PHONE[]`, **и** UF. Разрыв возникает при **редактировании в карточке CRM** и при сборке payload в n8n (`company/update-event`).

## Где чинить

| Слой | Можно? |
|------|--------|
| Карточка CRM (штатно) | Нет, без автоматизации |
| **n8n** на `bitrix.eklektika.ru` (событие компании) | **Да** — рекомендуется |
| Сайт `inbound_crm.php` | Только CRM → сайт; не меняет карточку B24 |
| PHP на портале B24 (обработчик `OnAfterCrmCompanyUpdate`) | Да, если есть доступ к коду коробки CRM |

## Рекомендуемый поток n8n (`company/update-event`)

После получения события / перед `crm.company.update` и перед POST на сайт:

1. **`crm.company.get`** по `COMPANY_ID` → полная строка `PHONE`, UF.
2. Узел **Code** — нормализация (см. ниже).
3. Если массив `fields` для update не пуст и отличается от get — **`crm.company.update`** с merge multifield (удалить старые `PHONE` по `ID` + новые `VALUE`, как в `Company::mergeCompanyProfileMultifieldsReplacingExisting`).
4. В body на сайт класть уже **согласованные** `PHONE[]` (массив) и UF.

### Константы UF

```text
UF_WORK  = UF_CRM_1777069666894
UF_MOBILE = UF_CRM_1777069676348
```

### Правила слияния (взаимное обновление)

Для каждого типа (`WORK`, `MOBILE`):

1. Взять значение из **UF** (если не пусто после trim).
2. Взять значение из **PHONE[]** с соответствующим `VALUE_TYPE` (если несколько — первый непустой).
3. Если заполнены **оба** и **различаются** — приоритет у поля, которое **пришло в событии изменения** (сравнить с `data.FIELDS` / `document_id` события); если неизвестно — приоритет **UF** (удобно для кастомных полей на форме).
4. Записать согласованное значение **и** в итоговый `PHONE[]`, **и** в UF для `crm.company.update`.

Для `crm.company.update` поле `PHONE` передавать **массивом**:

```json
"PHONE": [
  { "VALUE": "+7 937 164-30-52", "VALUE_TYPE": "WORK" },
  { "VALUE": "+2 (222) 222-22-22", "VALUE_TYPE": "MOBILE" }
]
```

Не один объект в `CRM_MULTIFIELDS.PHONE`.

## Пример Code node (n8n, JavaScript)

Вход: `company` — результат `crm.company.get`, `eventFields` — поля из вебхука события (опционально).

```javascript
const UF_WORK = 'UF_CRM_1777069666894';
const UF_MOBILE = 'UF_CRM_1777069676348';

function trim(v) {
  return v == null ? '' : String(v).trim();
}

function phoneRows(company) {
  const raw = company?.PHONE;
  if (!raw) return [];
  if (Array.isArray(raw)) return raw.filter((r) => r && trim(r.VALUE));
  if (raw.VALUE != null) return [raw];
  return Object.values(raw).filter((r) => r && trim(r.VALUE));
}

function pickByType(rows, type) {
  const t = type.toUpperCase();
  for (const r of rows) {
    const vt = trim(r.VALUE_TYPE || 'WORK').toUpperCase();
    if ((t === 'MOBILE' && vt === 'MOBILE') || (t === 'WORK' && vt !== 'MOBILE')) {
      return trim(r.VALUE);
    }
  }
  return '';
}

function eventTouched(eventFields, keys) {
  if (!eventFields || typeof eventFields !== 'object') return false;
  return keys.some((k) => Object.prototype.hasOwnProperty.call(eventFields, k));
}

const company = $input.first().json.result ?? $input.first().json;
const eventFields = $('Webhook').first().json.body?.FIELDS ?? $('Webhook').first().json.body ?? {};

const rows = phoneRows(company);
let work = pickByType(rows, 'WORK');
let mobile = pickByType(rows, 'MOBILE');

const ufWork = trim(company[UF_WORK]);
const ufMobile = trim(company[UF_MOBILE]);

const evWork = eventTouched(eventFields, [UF_WORK, 'PHONE']);
const evMobile = eventTouched(eventFields, [UF_MOBILE, 'PHONE']);

// WORK
if (ufWork && work && ufWork !== work) {
  work = eventTouched(eventFields, [UF_WORK]) ? ufWork : work;
} else if (ufWork && !work) {
  work = ufWork;
} else if (work && !ufWork) {
  // keep work from multifield
}

// MOBILE
if (ufMobile && mobile && ufMobile !== mobile) {
  mobile = eventTouched(eventFields, [UF_MOBILE]) ? ufMobile : mobile;
} else if (ufMobile && !mobile) {
  mobile = ufMobile;
} else if (mobile && !ufMobile) {
  // keep mobile from multifield
}

const phoneOut = [];
if (work) phoneOut.push({ VALUE: work, VALUE_TYPE: 'WORK' });
if (mobile) phoneOut.push({ VALUE: mobile, VALUE_TYPE: 'MOBILE' });

const fields = {
  [UF_WORK]: work,
  [UF_MOBILE]: mobile,
};

// merge PHONE: delete existing + new (IDs from company.PHONE)
const phoneUpdate = [];
for (const r of phoneRows(company)) {
  const id = parseInt(r.ID || r.id, 10);
  if (id > 0) phoneUpdate.push({ ID: id, DELETE: 'Y' });
}
for (const r of phoneOut) phoneUpdate.push(r);

if (phoneUpdate.length) fields.PHONE = phoneUpdate;

return [{ json: { fields, phoneOut, CRM_MULTIFIELDS: { PHONE: phoneOut } } }];
```

Далее: `crm.company.update` с `fields`, в POST на сайт — `PHONE: phoneOut` и UF.

## Сайт (inbound)

`Company::expandInboundCrmMultifieldsEnvelope` + `mergeInboundCompanyPhoneUfsIntoPhoneMultifield` дополняют неполный multifield из UF при **CRM → сайт**. Это **не** заменяет шаг выше на портале B24.

## Ссылки в коде сайта

- Исходящий sync (сайт → CRM): `Company::buildBitrix24CompanyFieldsFromSiteData`
- Регистрация: `CrmRegistrationOrchestrator::buildB24CrmWorkPhoneAndEmailFields`
- Inbound: `Company::mapCrmCompanyPayloadUfToSiteProperties`
- Константы: `CrmInboundUfMap::COMPANY_CRM_MAIN_PHONE_UF`, `COMPANY_CRM_MOBILE_PHONE_UF`
