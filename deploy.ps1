# Скрипт деплоя для Windows/WSL
# Копирует только содержимое bitrix-docker/www в указанную директорию

param(
    [string]$TargetDir = "/var/www/html"
)

$SourceDir = "bitrix-docker/www"

if (-not (Test-Path $SourceDir)) {
    Write-Host "Ошибка: директория $SourceDir не найдена!" -ForegroundColor Red
    exit 1
}

Write-Host "Начинаю деплой из $SourceDir в $TargetDir..." -ForegroundColor Green

# Создаем резервную копию (опционально)
if ((Test-Path $TargetDir) -and ((Get-ChildItem $TargetDir).Count -gt 0)) {
    $BackupDir = "${TargetDir}_backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    Write-Host "Создаю резервную копию в $BackupDir..." -ForegroundColor Yellow
    Copy-Item -Path $TargetDir -Destination $BackupDir -Recurse
}

# Копируем файлы
Write-Host "Копирую файлы..." -ForegroundColor Green

# Исключаем ненужные директории
$ExcludeDirs = @('.git', 'bitrixcache', 'bitrixmanagedcache', 'bitrixstackcache', 'upload', 'temp', '.DS_Store')

Get-ChildItem -Path $SourceDir -Recurse | Where-Object {
    $relativePath = $_.FullName.Replace((Resolve-Path $SourceDir).Path + '\', '')
    $shouldExclude = $false
    foreach ($exclude in $ExcludeDirs) {
        if ($relativePath -like "$exclude*") {
            $shouldExclude = $true
            break
        }
    }
    -not $shouldExclude
} | Copy-Item -Destination {
    $_.FullName.Replace((Resolve-Path $SourceDir).Path, $TargetDir)
} -Force

Write-Host "Деплой завершен успешно!" -ForegroundColor Green
Write-Host "Файлы скопированы в: $TargetDir" -ForegroundColor Green

