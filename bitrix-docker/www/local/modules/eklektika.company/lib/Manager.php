<?php
namespace OnlineService\Site;

class Manager{
    private int $iblock_id = 53;

    /**
     * Создает новый элемент менеджера в инфоблоке
     * @param array $fields - данные из B24
     * @return int|false - ID созданного элемента или false в случае ошибки
     */
    public function create($fields){
        $b24Id = $fields['ID'];
        
        if (empty($b24Id)) {
            return false;
        }
        
        // Подключаем модуль инфоблоков
        if (!\CModule::IncludeModule('iblock')) {
            return false;
        }
        
        $el = new \CIBlockElement;
        
        $arFields = [
            'IBLOCK_ID' => $this->iblock_id,
            'NAME' => trim($fields['NAME'] . ' ' . $fields['LAST_NAME']),
            'XML_ID' => $b24Id,
            'ACTIVE' => 'Y',
            'PROPERTY_VALUES' => [
                'PHONE' => $fields['PHONE'] ?? '',
                'EMAIL' => $fields['EMAIL'] ?? '',
                'WORK_POSITION' => $fields['POSITION'] ?? ''
            ]
        ];
        
        // Если есть фото, добавляем его
        $photoArray = null;
        if (!empty($fields['PERSONAL_PHOTO'])) {
            $photoArray = $this->downloadPhoto($fields['PERSONAL_PHOTO']);
            if ($photoArray) {
                $arFields['PREVIEW_PICTURE'] = $photoArray;
            }
        }
        
        $elementId = $el->Add($arFields);
        
        // Удаляем временный файл после обработки
        if ($photoArray && isset($photoArray['tmp_name']) && file_exists($photoArray['tmp_name'])) {
            unlink($photoArray['tmp_name']);
        }
        
        if ($elementId) {
            return $elementId;
        } else {
            return false;
        }
    }

    /**
     * Обновляет или создает элемент менеджера
     * @param array $fields - данные из B24
     * @return bool - результат операции
     */
    public function update($fields){
        $b24Id = $fields['ID'];
        
        if (empty($b24Id)) {
            return false;
        }

        $updatableFields = [
            'NAME' => trim($fields['NAME'] . ' ' . $fields['LAST_NAME']),
            'PHONE' => $fields['PHONE'] ?? '',
            'EMAIL' => $fields['EMAIL'] ?? '',
            'WORK_POSITION' => $fields['POSITION'] ?? '',
            'PERSONAL_PHOTO' => $fields['PERSONAL_PHOTO'] ?? ''
        ];
        
        // Подключаем модуль инфоблоков
        if (!\CModule::IncludeModule('iblock')) {
            return false;
        }
        
        // Ищем элемент по внешнему коду (XML_ID)
        $arFilter = [
            'IBLOCK_ID' => $this->iblock_id,
            'XML_ID' => $b24Id
        ];
        
        $rsElement = \CIBlockElement::GetList(
            ['SORT' => 'ASC'],
            $arFilter,
            false,
            false,
            ['ID', 'NAME', 'XML_ID', 'IBLOCK_ID']
        );
        
        if ($arElement = $rsElement->GetNext()) {
            // Дополнительная проверка IBLOCK_ID
            if ($arElement['IBLOCK_ID'] != $this->iblock_id) {
                return false;
            }
            
            // Элемент найден, обновляем его
            $elementId = $arElement['ID'];
        } else {
            // Элемент не найден, создаем новый
            $elementId = $this->create($fields);
            
            if (!$elementId) {
                return false;
            }
            
            // После создания элемент уже содержит все данные, можно вернуть успех
            return true;
        }
        
        // Обновляем основные поля элемента
        $el = new \CIBlockElement;
        $updateResult = $el->Update($elementId, [
            'NAME' => $updatableFields['NAME']
        ]);
        
        if ($updateResult) {
            // Обновляем свойства элемента
            $this->updateProperties($elementId, $updatableFields);
            
            // Обновляем фото, если оно передано
            if (!empty($updatableFields['PERSONAL_PHOTO'])) {
                $this->updatePhoto($elementId, $updatableFields['PERSONAL_PHOTO']);
            }
            
            return true; // Успешно обновлено
        }

        return false; // Ошибка обновления
    }

    /**
     * Обновляет свойства элемента менеджера
     * @param int $elementId - ID элемента инфоблока
     * @param array $fields - массив полей для обновления
     * @return void
     */
    private function updateProperties($elementId, $fields){
        \CIBlockElement::SetPropertyValues(
            $elementId,
            $this->iblock_id,
            $fields['PHONE'],
            'PHONE'
        );
        
        \CIBlockElement::SetPropertyValues(
            $elementId,
            $this->iblock_id,
            $fields['EMAIL'],
            'EMAIL'
        );
        
        \CIBlockElement::SetPropertyValues( 
            $elementId,
            $this->iblock_id,
            $fields['WORK_POSITION'],
            'WORK_POSITION'
        );
    }

    /**
     * Скачивает фото с B24 и возвращает массив файла для Bitrix
     * @param string $photoUrl - относительный URL фото на B24
     * @return array|false - массив файла или false в случае ошибки
     */
    private function downloadPhoto($photoUrl){
        try {
            $photoUrl = ltrim($photoUrl, '/');
            
            // Разбиваем путь по "/" и кодируем только имя файла (последний элемент)
            $pathParts = explode('/', $photoUrl);
            $fileName = array_pop($pathParts); // Получаем имя файла
            $encodedFileName = rawurlencode($fileName); // Кодируем имя файла
            $pathParts[] = $encodedFileName; // Возвращаем закодированное имя файла
            $encodedPhotoUrl = URL_B24 . implode('/', $pathParts);
            
            // Загружаем файл с внешнего ресурса URL_B24 через HTTP-клиент
            $httpClient = new \Bitrix\Main\Web\HttpClient();
            $httpClient->setTimeout(30);
            
            // Создаем временный файл с оригинальным расширением
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $tempFile = tempnam(sys_get_temp_dir(), 'photo_') . '.' . $fileExtension;
            $downloadResult = $httpClient->download($encodedPhotoUrl, $tempFile);
            
            if ($downloadResult && file_exists($tempFile)) {
                // Определяем MIME-тип
                $mimeType = $this->getMimeType($tempFile, $fileExtension);
                
                // Создаем массив файла вручную с явным указанием всех параметров
                $photoArray = [
                    'name' => 'manager_photo_' . time() . '.' . $fileExtension,
                    'size' => filesize($tempFile),
                    'tmp_name' => $tempFile,
                    'type' => $mimeType,
                    'old_file' => '',
                    'del' => '',
                    'MODULE_ID' => 'iblock'
                ];
                
                return $photoArray;
            } else {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        } catch (\Exception $e) {
            // Ошибка при загрузке фото
        }
        
        return false;
    }

    /**
     * Определяет MIME-тип файла
     * @param string $filePath - путь к файлу
     * @param string $extension - расширение файла
     * @return string - MIME-тип
     */
    private function getMimeType($filePath, $extension){
        // Пробуем определить MIME-тип через finfo
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            if ($mimeType) {
                return $mimeType;
            }
        }
        
        // Fallback: определяем по расширению
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp'
        ];
        
        $ext = strtolower($extension);
        return $mimeTypes[$ext] ?? 'application/octet-stream';
    }

    /**
     * Обновляет фото элемента менеджера
     * @param int $elementId - ID элемента инфоблока
     * @param string $photoUrl - относительный URL фото на B24
     * @return bool - результат операции
     */
    private function updatePhoto($elementId, $photoUrl){
        $photoArray = $this->downloadPhoto($photoUrl);
        
        if ($photoArray) {
            $el = new \CIBlockElement;
            $updatePhotoResult = $el->Update($elementId, [
                'PREVIEW_PICTURE' => $photoArray
            ]);
            
            // Удаляем временный файл после обработки
            if (isset($photoArray['tmp_name']) && file_exists($photoArray['tmp_name'])) {
                unlink($photoArray['tmp_name']);
            }
            
            if ($updatePhotoResult) {
                return true;
            }
        }
        
        return false;
    }
}