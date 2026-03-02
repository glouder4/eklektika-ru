<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use OnlineService\Site\Company;

class OnlineServiceCompanyProfileEditComponent extends CBitrixComponent
{
    /**
     * Инициализация компонента
     */
    public function onPrepareComponentParams($arParams)
    {
        $arParams['COMPANY_ID'] = intval($arParams['COMPANY_ID']);
        $arParams['CACHE_TIME'] = intval($arParams['CACHE_TIME'] ?? 0);
        
        return $arParams;
    }

    /**
     * Выполнение компонента
     */
    public function executeComponent()
    {
        global $USER;
        
        // Проверяем авторизацию
        if (!$USER->IsAuthorized()) {
            ShowError('Необходимо авторизоваться');
            return;
        }

        // Проверяем наличие необходимых модулей
        if (!Loader::includeModule('iblock')) {
            ShowError('Модуль iblock не подключен');
            return;
        }

        $companyId = $this->arParams['COMPANY_ID'];
        
        if (empty($companyId)) {
            ShowError('Не указан ID компании');
            return;
        }

        // Получаем данные компании
        $companyData = $this->getCompanyData($companyId);
        
        if (!$companyData) {
            ShowError('Компания не найдена');
            return;
        }

        // Проверяем права доступа на редактирование
        if (!$this->checkEditAccess($companyId)) {
            ShowError('Доступ к редактированию запрещен');
            return;
        }

        // Формируем результат
        $this->arResult = [
            'COMPANY' => $companyData,
            'COMPANY_ID' => $companyId,
            'CAN_EDIT' => true,
            'IS_ADMIN' => $USER->IsAdmin()
        ];

        // Устанавливаем заголовок страницы
        global $APPLICATION;
        $APPLICATION->SetTitle('Редактирование компании: ' . $companyData['NAME']);

        // Подключаем шаблон
        $this->includeComponentTemplate();
    }

    /**
     * Получение данных компании
     */
    private function getCompanyData($companyId)
    {
        $company = new Company();
        $companyData = $company->getCompany($companyId);
        
        // Дополнительно получаем CODE элемента
        if ($companyData) {
            $rsElement = \CIBlockElement::GetByID($companyId);
            if ($arElement = $rsElement->Fetch()) {
                $companyData['CODE'] = $arElement['CODE'];
            }
        }
        
        return $companyData;
    }

    /**
     * Проверка прав доступа на редактирование
     */
    private function checkEditAccess($companyId)
    {
        global $USER;
        
        // Администратор имеет доступ всегда
        if ($USER->IsAdmin()) {
            return true;
        }

        $company = new Company();
        $companyData = $company->getCompany($companyId);
        
        if (!$companyData) {
            return false;
        }

        // Проверяем, является ли пользователь руководителем компании
        $bosses = $companyData['LEGAN_ENTITY_BOSS'] ?? [];
        if (!is_array($bosses)) {
            $bosses = $bosses ? [$bosses] : [];
        }

        return in_array($USER->GetID(), $bosses);
    }
}

