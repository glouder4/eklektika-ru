#!/bin/bash
# Деплой содержимого bitrix-docker/www на стенд (Bitrix VM под пользователем bitrix).
#
# Переменные окружения (опционально):
#   REPO, BRANCH, SITE_DIR — как в значениях по умолчанию ниже.
#   DEPLOY_RSYNC_IGNORE_ERRORS=1 — не прерывать деплой из‑за отдельных ошибок delete/chmod на чужих файлах
#     (код завершения rsync 23/24 всё равно считается предупреждением).
#
# Если постоянно «Permission denied» на delete/mkstemp: один раз выровняйте владельца каталога сайта
#   chown -R bitrix:bitrix "$SITE_DIR"
# (от root), либо оставьте DEPLOY_RSYNC_IGNORE_ERRORS=1 и добейте права позже.

set -euo pipefail

REPO="${REPO:-git@github.com:glouder4/eklektika-ru.git}"
BRANCH="${BRANCH:-main}"
SITE_DIR="${SITE_DIR:-/home/bitrix/ext_www/new.eklektika.ru}"
TMP_DIR="${TMP_DIR:-/tmp/deploy-$(date +%s)}"

echo "📥 Клонируем репозиторий..."
git clone --depth=1 --branch "$BRANCH" "$REPO" "$TMP_DIR" >/dev/null 2>&1

SOURCE_DIR="$TMP_DIR/bitrix-docker/www"

if [ ! -d "$SOURCE_DIR" ]; then
    echo "❌ Ошибка: каталог $SOURCE_DIR не найден!"
    rm -rf "$TMP_DIR"
    exit 1
fi

echo "🔄 Синхронизируем файлы..."

# -a без попыток выставить на приёмнике чужого owner/group (часто даёт Operation not permitted на chgrp).
# --inplace — не создаём .index.php.* рядом с целевым файлом (иначе mkstemp падает, если каталог не writable).
# Защита от удаления того, чего нет в git, но что должно жить на сервере (см. rsync(1) filter merge P).
RSYNC_ARGS=(
    -a
    -v
    --delete
    --checksum
    --no-owner
    --no-group
    --inplace
    --human-readable
)

if [ "${DEPLOY_RSYNC_IGNORE_ERRORS:-0}" = "1" ]; then
    RSYNC_ARGS+=(--ignore-errors)
fi

# Не затирать и не пытаться синхронизировать типичный мусор / сервер-only (приёмник).
RSYNC_ARGS+=(
    -f 'P .settings.php'
    -f 'P /upload/'
    -f 'P /local/logs/'
    -f 'P /local/cache/'
    -f 'P /bitrix/'
    -f 'P /eklektika_dump*.sql.gz'
    -f 'P /catalog_redirects_map.csv'
    -f 'P /log/'
)

# Исключения из источника (репозиторий): не копировать мусор в приёмник.
RSYNC_ARGS+=(
    --exclude='.settings.php'
    --exclude='upload/'
    --exclude='local/cache/'
    --exclude='local/logs/'
    --exclude='/bitrix'
    --exclude='assets/'
    --exclude='.git/'
    --exclude='.DS_Store'
    --exclude='Thumbs.db'
    --exclude='*.Zone.Identifier'
    --exclude='*:Zone.Identifier'
    --exclude='*.swp'
    --exclude='*.swo'
    --exclude='*~'
)

set +e
rsync "${RSYNC_ARGS[@]}" "$SOURCE_DIR/" "$SITE_DIR/"
rsync_ec=$?
set -e

rm -rf "$TMP_DIR"

# 0 — ок; 23 — частичная передача из‑за ошибок I/O/прав; 24 — пропали файлы на источнике.
if [ "$rsync_ec" -ne 0 ] && [ "$rsync_ec" -ne 23 ] && [ "$rsync_ec" -ne 24 ]; then
    echo "❌ rsync завершился с кодом $rsync_ec"
    exit "$rsync_ec"
fi

if [ "$rsync_ec" -ne 0 ]; then
    echo "⚠️  rsync завершился с кодом $rsync_ec (частичные ошибки). Проверьте права на каталоги/файлы вне владельца bitrix."
fi

# Права — только на то, что принадлежит bitrix (без sudo!)
chmod -R 775 "$SITE_DIR/upload" "$SITE_DIR/local/cache" 2>/dev/null || true

echo "✅ Деплой завершён."
