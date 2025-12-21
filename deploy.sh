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
    
    # Определяем ветку для деплоя
    CURRENT_BRANCH=$(git branch --show-current)
    echo "📍 Текущая ветка: $CURRENT_BRANCH"
    
    # Для тестового сервера используем ветку test-server-branch
    read -p "Из какой ветки деплоить? (по умолчанию test-server-branch): " DEPLOY_BRANCH
    DEPLOY_BRANCH=${DEPLOY_BRANCH:-test-server-branch}
    
    # Проверяем, существует ли ветка
    if ! git rev-parse --verify "$DEPLOY_BRANCH" > /dev/null 2>&1; then
        echo "❌ Ошибка: ветка '$DEPLOY_BRANCH' не найдена!"
        echo "Доступные ветки:"
        git branch -a | grep -v HEAD
        exit 1
    fi
    
    echo "🚀 Деплой из ветки: $DEPLOY_BRANCH"
    
    # Если текущая ветка отличается от целевой, предупреждаем
    if [ "$CURRENT_BRANCH" != "$DEPLOY_BRANCH" ]; then
        echo "⚠️ Внимание: вы находитесь в ветке '$CURRENT_BRANCH', но деплой будет из '$DEPLOY_BRANCH'"
    fi
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
        REPO_ROOT="$(git rev-parse --show-toplevel)"
        cd "$REPO_ROOT"
        
        # Получаем список файлов из указанной ветки git, исключая нужные папки
        git ls-tree -r --name-only "$DEPLOY_BRANCH" -- "$SOURCE_DIR/" | grep -v "^$SOURCE_DIR/\.git" | \
        grep -v "^$SOURCE_DIR/bitrix/" | \
        grep -v "^$SOURCE_DIR/bitrixcache" | \
        grep -v "^$SOURCE_DIR/bitrixmanagedcache" | \
        grep -v "^$SOURCE_DIR/bitrixstackcache" | \
        grep -v "^$SOURCE_DIR/temp" | \
        $(if [[ ! "$INCLUDE_UPLOAD" =~ ^[Yy]$ ]]; then echo "grep -v \"^$SOURCE_DIR/upload\""; else echo "cat"; fi) | \
        grep -v "\.DS_Store$" > /tmp/deploy_files.txt
        
        FILE_COUNT=$(wc -l < /tmp/deploy_files.txt | tr -d ' ')
        echo "📋 Найдено $FILE_COUNT файлов для деплоя"
        
        # Проверяем, что список не пустой
        if [ "$FILE_COUNT" -eq 0 ]; then
            echo "❌ Ошибка: список файлов для деплоя пуст!"
            rm -f /tmp/deploy_files.txt
            exit 1
        fi
        
        # Создаем временную директорию для файлов из указанной ветки
        TEMP_DIR=$(mktemp -d)
        echo "📦 Извлекаю файлы из ветки '$DEPLOY_BRANCH' во временную директорию..."
        
        # Извлекаем файлы из указанной ветки
        EXTRACTED_COUNT=0
        FAILED_COUNT=0
        TOTAL_FILES=$(wc -l < /tmp/deploy_files.txt | tr -d ' ')
        echo "📦 Извлекаю файлы из ветки '$DEPLOY_BRANCH' ($TOTAL_FILES файлов)..."
        
        # Временно отключаем set -e для цикла, чтобы не прерывать при ошибках отдельных файлов
        set +e
        while IFS= read -r file || [ -n "$file" ]; do
            if [ -z "$file" ]; then
                continue
            fi
            
            if git cat-file -e "$DEPLOY_BRANCH:$file" 2>/dev/null; then
                # Создаем директорию если нужно
                FILE_DIR="$TEMP_DIR/$(dirname "$file")"
                mkdir -p "$FILE_DIR" 2>/dev/null || true
                
                # Извлекаем файл из ветки
                if git show "$DEPLOY_BRANCH:$file" > "$TEMP_DIR/$file" 2>/dev/null; then
                    EXTRACTED_COUNT=$((EXTRACTED_COUNT + 1))
                    # Показываем прогресс каждые 50 файлов
                    if [ $((EXTRACTED_COUNT % 50)) -eq 0 ]; then
                        echo "  Извлечено $EXTRACTED_COUNT/$TOTAL_FILES файлов..."
                    fi
                else
                    FAILED_COUNT=$((FAILED_COUNT + 1))
                    echo "⚠️ Не удалось извлечь файл: $file" >&2
                fi
            else
                FAILED_COUNT=$((FAILED_COUNT + 1))
                echo "⚠️ Файл не найден в ветке '$DEPLOY_BRANCH': $file" >&2
            fi
        done < /tmp/deploy_files.txt
        set -e  # Включаем обратно
        
        echo "📦 Извлечено $EXTRACTED_COUNT файлов из ветки '$DEPLOY_BRANCH'"
        if [ "$FAILED_COUNT" -gt 0 ]; then
            echo "⚠️ Не удалось извлечь $FAILED_COUNT файлов"
        fi
        
        if [ "$EXTRACTED_COUNT" -eq 0 ]; then
            echo "❌ Ошибка: не удалось извлечь ни одного файла!"
            rm -rf "$TEMP_DIR"
            rm -f /tmp/deploy_files.txt
            exit 1
        fi
        
        # Копируем файлы из временной директории
        # Копируем содержимое $SOURCE_DIR из временной директории в целевую
        echo "📤 Копирую файлы на сервер..."
        # Убираем --no-implied-dirs, чтобы rsync создавал нужные директории
        rsync -avz \
            "$TEMP_DIR/$SOURCE_DIR/" \
            "$TARGET_DIR/" 2>&1 | tee /tmp/rsync_output.log | grep -vE "(failed to set)" || true
        
        RSYNC_EXIT_CODE=${PIPESTATUS[0]}
        
        # Показываем детали ошибок если есть
        if [ "$RSYNC_EXIT_CODE" -ne 0 ]; then
            echo ""
            echo "📋 Детали ошибок rsync:"
            grep -E "(rsync error|failed|No such file|Permission denied)" /tmp/rsync_output.log | head -20 || true
        fi
        
        # Удаляем временные файлы
        rm -rf "$TEMP_DIR"
        rm -f /tmp/rsync_output.log
        
        # Проверяем результат
        if [ "$RSYNC_EXIT_CODE" -eq 0 ]; then
            echo "✅ Файлы успешно скопированы"
        elif [ "$RSYNC_EXIT_CODE" -eq 23 ]; then
            echo "⚠️ Внимание: некоторые файлы не были переданы (код 23)."
            echo "Это может быть нормально, если некоторые файлы отсутствуют или были удалены."
        else
            echo "⚠️ Внимание: ошибка при копировании (код $RSYNC_EXIT_CODE). Проверьте ошибки выше."
        fi
        
        # Если upload включен, копируем его из файловой системы (он исключен из git)
        if [[ "$INCLUDE_UPLOAD" =~ ^[Yy]$ ]] && [ -d "$REPO_ROOT/$SOURCE_DIR/upload" ]; then
            echo "📦 Копирую папку upload из файловой системы (исключена из git)..."
            rsync -avz \
                "$REPO_ROOT/$SOURCE_DIR/upload/" \
                "$TARGET_DIR/$SOURCE_DIR/upload/" 2>&1 | grep -v "failed to set" || true
            echo "✅ Папка upload скопирована"
        fi
        
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
        REPO_ROOT="$(git rev-parse --show-toplevel)"
        cd "$REPO_ROOT"
        
        # Получаем список файлов из указанной ветки git, исключая нужные папки
        git ls-tree -r --name-only "$DEPLOY_BRANCH" -- "$SOURCE_DIR/" | grep -v "^$SOURCE_DIR/\.git" | \
        grep -v "^$SOURCE_DIR/bitrix/" | \
        grep -v "^$SOURCE_DIR/bitrixcache" | \
        grep -v "^$SOURCE_DIR/bitrixmanagedcache" | \
        grep -v "^$SOURCE_DIR/bitrixstackcache" | \
        grep -v "^$SOURCE_DIR/temp" | \
        $(if [[ ! "$INCLUDE_UPLOAD" =~ ^[Yy]$ ]]; then echo "grep -v \"^$SOURCE_DIR/upload\""; else echo "cat"; fi) | \
        grep -v "\.DS_Store$" > /tmp/deploy_files.txt
        
        FILE_COUNT=$(wc -l < /tmp/deploy_files.txt | tr -d ' ')
        echo "📋 Найдено $FILE_COUNT файлов для деплоя"
        
        # Проверяем, что список не пустой
        if [ "$FILE_COUNT" -eq 0 ]; then
            echo "❌ Ошибка: список файлов для деплоя пуст!"
            rm -f /tmp/deploy_files.txt
            exit 1
        fi
        
        # Создаем временную директорию для файлов из указанной ветки
        TEMP_DIR=$(mktemp -d)
        echo "📦 Извлекаю файлы из ветки '$DEPLOY_BRANCH' во временную директорию..."
        
        # Извлекаем файлы из указанной ветки
        EXTRACTED_COUNT=0
        FAILED_COUNT=0
        TOTAL_FILES=$(wc -l < /tmp/deploy_files.txt | tr -d ' ')
        echo "📦 Извлекаю файлы из ветки '$DEPLOY_BRANCH' ($TOTAL_FILES файлов)..."
        
        # Временно отключаем set -e для цикла, чтобы не прерывать при ошибках отдельных файлов
        set +e
        while IFS= read -r file || [ -n "$file" ]; do
            if [ -z "$file" ]; then
                continue
            fi
            
            if git cat-file -e "$DEPLOY_BRANCH:$file" 2>/dev/null; then
                # Создаем директорию если нужно
                FILE_DIR="$TEMP_DIR/$(dirname "$file")"
                mkdir -p "$FILE_DIR" 2>/dev/null || true
                
                # Извлекаем файл из ветки
                if git show "$DEPLOY_BRANCH:$file" > "$TEMP_DIR/$file" 2>/dev/null; then
                    EXTRACTED_COUNT=$((EXTRACTED_COUNT + 1))
                    # Показываем прогресс каждые 50 файлов
                    if [ $((EXTRACTED_COUNT % 50)) -eq 0 ]; then
                        echo "  Извлечено $EXTRACTED_COUNT/$TOTAL_FILES файлов..."
                    fi
                else
                    FAILED_COUNT=$((FAILED_COUNT + 1))
                    echo "⚠️ Не удалось извлечь файл: $file" >&2
                fi
            else
                FAILED_COUNT=$((FAILED_COUNT + 1))
                echo "⚠️ Файл не найден в ветке '$DEPLOY_BRANCH': $file" >&2
            fi
        done < /tmp/deploy_files.txt
        set -e  # Включаем обратно
        
        echo "📦 Извлечено $EXTRACTED_COUNT файлов из ветки '$DEPLOY_BRANCH'"
        if [ "$FAILED_COUNT" -gt 0 ]; then
            echo "⚠️ Не удалось извлечь $FAILED_COUNT файлов"
        fi
        
        if [ "$EXTRACTED_COUNT" -eq 0 ]; then
            echo "❌ Ошибка: не удалось извлечь ни одного файла!"
            rm -rf "$TEMP_DIR"
            rm -f /tmp/deploy_files.txt
            exit 1
        fi
        
        # Копируем файлы из временной директории
        # Копируем содержимое $SOURCE_DIR из временной директории в целевую
        echo "📤 Копирую файлы..."
        # Убираем --no-implied-dirs, чтобы rsync создавал нужные директории
        rsync -av \
            "$TEMP_DIR/$SOURCE_DIR/" \
            "$TARGET_DIR/" 2>&1 | tee /tmp/rsync_output.log | grep -vE "(failed to set)" || true
        
        RSYNC_EXIT_CODE=${PIPESTATUS[0]}
        
        # Показываем детали ошибок если есть
        if [ "$RSYNC_EXIT_CODE" -ne 0 ]; then
            echo ""
            echo "📋 Детали ошибок rsync:"
            grep -E "(rsync error|failed|No such file|Permission denied)" /tmp/rsync_output.log | head -20 || true
        fi
        
        # Удаляем временные файлы
        rm -rf "$TEMP_DIR"
        rm -f /tmp/rsync_output.log
        
        # Проверяем результат
        if [ "$RSYNC_EXIT_CODE" -eq 0 ]; then
            echo "✅ Файлы успешно скопированы"
        elif [ "$RSYNC_EXIT_CODE" -eq 23 ]; then
            echo "⚠️ Внимание: некоторые файлы не были переданы (код 23)."
            echo "Это может быть нормально, если некоторые файлы отсутствуют или были удалены."
        else
            echo "⚠️ Внимание: ошибка при копировании (код $RSYNC_EXIT_CODE). Проверьте ошибки выше."
        fi
        
        # Если upload включен, копируем его из файловой системы (он исключен из git)
        if [[ "$INCLUDE_UPLOAD" =~ ^[Yy]$ ]] && [ -d "$REPO_ROOT/$SOURCE_DIR/upload" ]; then
            echo "📦 Копирую папку upload из файловой системы (исключена из git)..."
            rsync -av \
                "$REPO_ROOT/$SOURCE_DIR/upload/" \
                "$TARGET_DIR/$SOURCE_DIR/upload/" 2>&1 | grep -v "failed to set" || true
            echo "✅ Папка upload скопирована"
        fi
        
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
