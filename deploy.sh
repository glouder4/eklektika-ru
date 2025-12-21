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

# Проверяем, что мы в git репозитории
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    echo "⚠️ Внимание: не обнаружен git репозиторий. Будут скопированы все файлы из $SOURCE_DIR"
    USE_GIT_FILES=false
else
    echo "✅ Git репозиторий обнаружен. Будут скопированы только файлы, отслеживаемые git."
    USE_GIT_FILES=true
fi

# Спрашиваем, нужно ли включать папку upload
read -p "Включить папку upload в деплой? (y/n, по умолчанию n): " INCLUDE_UPLOAD
INCLUDE_UPLOAD=${INCLUDE_UPLOAD:-n}

# Определяем, это удаленный сервер или локальный путь
if [[ "$TARGET_DIR" == *"@"* ]]; then
    # Удаленный сервер через SSH
    REMOTE_HOST=$(echo "$TARGET_DIR" | cut -d: -f1)
    REMOTE_PATH=$(echo "$TARGET_DIR" | cut -d: -f2-)
    
    echo "Деплой на удаленный сервер: $REMOTE_HOST:$REMOTE_PATH"
    
    # Формируем список исключений для rsync
    RSYNC_EXCLUDES=(
        --exclude='.git'
        --exclude='bitrix/'
        --exclude='bitrixcache'
        --exclude='bitrixmanagedcache'
        --exclude='bitrixstackcache'
        --exclude='temp'
        --exclude='.DS_Store'
    )
    
    # Добавляем upload в исключения, если не нужно его включать
    if [[ ! "$INCLUDE_UPLOAD" =~ ^[Yy]$ ]]; then
        RSYNC_EXCLUDES+=(--exclude='upload')
        echo "⚠️ Папка upload будет исключена из деплоя"
    else
        echo "✅ Папка upload будет включена в деплой"
    fi
    
    # Копируем файлы через rsync
    if [ "$USE_GIT_FILES" = true ]; then
        # Деплоим только файлы, отслеживаемые git
        echo "📦 Получаю список файлов из git..."
        cd "$(git rev-parse --show-toplevel)"
        
        # Получаем список файлов из git, исключая нужные папки
        git ls-files "$SOURCE_DIR/" | grep -v "^$SOURCE_DIR/\.git" | \
        grep -v "^$SOURCE_DIR/bitrix/" | \
        grep -v "^$SOURCE_DIR/bitrixcache" | \
        grep -v "^$SOURCE_DIR/bitrixmanagedcache" | \
        grep -v "^$SOURCE_DIR/bitrixstackcache" | \
        grep -v "^$SOURCE_DIR/temp" | \
        $(if [[ ! "$INCLUDE_UPLOAD" =~ ^[Yy]$ ]]; then echo "grep -v \"^$SOURCE_DIR/upload\""; else echo "cat"; fi) | \
        grep -v "\.DS_Store$" > /tmp/deploy_files.txt
        
        echo "📋 Найдено $(wc -l < /tmp/deploy_files.txt) файлов для деплоя"
        
        # Копируем файлы из списка
        rsync -avz \
            --files-from=/tmp/deploy_files.txt \
            --relative \
            "$(git rev-parse --show-toplevel)/" \
            "$TARGET_DIR/"
        
        rm -f /tmp/deploy_files.txt
    else
        # Деплоим все файлы (старый способ)
        rsync -avz --delete \
            "${RSYNC_EXCLUDES[@]}" \
            "$SOURCE_DIR/" "$TARGET_DIR/"
    fi
    
    # Устанавливаем права на удаленном сервере
    echo "Устанавливаю права на удаленном сервере..."
    ssh "$REMOTE_HOST" "chown -R bitrix:bitrix $REMOTE_PATH && find $REMOTE_PATH -type d -exec chmod 755 {} \; && find $REMOTE_PATH -type f -exec chmod 644 {} \;"
else
    # Локальный путь
    # Создаем резервную копию (опционально)
    if [ -d "$TARGET_DIR" ] && [ "$(ls -A $TARGET_DIR)" ]; then
        BACKUP_DIR="${TARGET_DIR}_backup_$(date +%Y%m%d_%H%M%S)"
        echo "Создаю резервную копию в $BACKUP_DIR..."
        cp -r "$TARGET_DIR" "$BACKUP_DIR"
    fi
    
    # Формируем список исключений для rsync
    RSYNC_EXCLUDES=(
        --exclude='.git'
        --exclude='bitrix/'
        --exclude='bitrixcache'
        --exclude='bitrixmanagedcache'
        --exclude='bitrixstackcache'
        --exclude='temp'
        --exclude='.DS_Store'
    )
    
    # Добавляем upload в исключения, если не нужно его включать
    if [[ ! "$INCLUDE_UPLOAD" =~ ^[Yy]$ ]]; then
        RSYNC_EXCLUDES+=(--exclude='upload')
        echo "⚠️ Папка upload будет исключена из деплоя"
    else
        echo "✅ Папка upload будет включена в деплой"
    fi
    
    # Копируем файлы с сохранением прав
    echo "Копирую файлы..."
    if [ "$USE_GIT_FILES" = true ]; then
        # Деплоим только файлы, отслеживаемые git
        echo "📦 Получаю список файлов из git..."
        cd "$(git rev-parse --show-toplevel)"
        
        # Получаем список файлов из git, исключая нужные папки
        git ls-files "$SOURCE_DIR/" | grep -v "^$SOURCE_DIR/\.git" | \
        grep -v "^$SOURCE_DIR/bitrix/" | \
        grep -v "^$SOURCE_DIR/bitrixcache" | \
        grep -v "^$SOURCE_DIR/bitrixmanagedcache" | \
        grep -v "^$SOURCE_DIR/bitrixstackcache" | \
        grep -v "^$SOURCE_DIR/temp" | \
        $(if [[ ! "$INCLUDE_UPLOAD" =~ ^[Yy]$ ]]; then echo "grep -v \"^$SOURCE_DIR/upload\""; else echo "cat"; fi) | \
        grep -v "\.DS_Store$" > /tmp/deploy_files.txt
        
        echo "📋 Найдено $(wc -l < /tmp/deploy_files.txt) файлов для деплоя"
        
        # Копируем файлы из списка
        rsync -av \
            --files-from=/tmp/deploy_files.txt \
            --relative \
            "$(git rev-parse --show-toplevel)/" \
            "$TARGET_DIR/"
        
        rm -f /tmp/deploy_files.txt
    else
        # Деплоим все файлы (старый способ)
        rsync -av --delete \
            "${RSYNC_EXCLUDES[@]}" \
            "$SOURCE_DIR/" "$TARGET_DIR/"
    fi
fi

echo "Деплой завершен успешно!"
echo "Файлы скопированы в: $TARGET_DIR"
