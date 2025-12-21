#!/bin/bash

# Скрипт деплоя для сервера
# Копирует только содержимое bitrix-docker/www в указанную директорию

set -e

# Целевая директория на сервере (измените на свою)
TARGET_DIR="${1:-/var/www/html}"

# Проверка наличия исходной директории
SOURCE_DIR="bitrix-docker/www"

if [ ! -d "$SOURCE_DIR" ]; then
    echo "Ошибка: директория $SOURCE_DIR не найдена!"
    exit 1
fi

echo "Начинаю деплой из $SOURCE_DIR в $TARGET_DIR..."

# Создаем резервную копию (опционально)
if [ -d "$TARGET_DIR" ] && [ "$(ls -A $TARGET_DIR)" ]; then
    BACKUP_DIR="${TARGET_DIR}_backup_$(date +%Y%m%d_%H%M%S)"
    echo "Создаю резервную копию в $BACKUP_DIR..."
    cp -r "$TARGET_DIR" "$BACKUP_DIR"
fi

# Копируем файлы с сохранением прав
echo "Копирую файлы..."
rsync -av --delete \
    --exclude='.git' \
    --exclude='bitrixcache' \
    --exclude='bitrixmanagedcache' \
    --exclude='bitrixstackcache' \
    --exclude='upload' \
    --exclude='temp' \
    --exclude='.DS_Store' \
    "$SOURCE_DIR/" "$TARGET_DIR/"

echo "Деплой завершен успешно!"
echo "Файлы скопированы в: $TARGET_DIR"

