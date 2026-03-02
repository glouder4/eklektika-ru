<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);

// Получаем данные компании
$companyName = $arResult["NAME"];
//$companyEmail = $arResult["PROPERTIES"]["OS_COMPANY_EMAIL"]["VALUE"] ?? '';
$companyPhone = $arResult["PROPERTIES"]["LEGAN_ENTITY_PHONE"]["VALUE"] ?? '';
$companyInn = $arResult["PROPERTIES"]["LEGAN_ENTITY_INN"]["VALUE"] ?? '';
$companyWebSite = $arResult["PROPERTIES"]["LEGAN_ENTITY_WWW"]["VALUE"] ?? '';
//$companyCity = $arResult["PROPERTIES"]["OS_COMPANY_CITY"]["VALUE"] ?? '';
$companyBossIds = $arResult["PROPERTIES"]["LEGAN_ENTITY_BOSS"]["VALUE"] ?? [];
$companyUserIds = $arResult["PROPERTIES"]["LEGAN_ENTITY_USERS"]["VALUE"] ?? [];

$isMarketingAgent = $arResult["PROPERTIES"]["OS_IS_MARKETING_AGENT"]["VALUE_XML_ID"] ?? '';
$isHeadOfHolding = $arResult["PROPERTIES"]["LEGAN_ENTITY_ID_OF_HEAD_COMPANY"]["VALUE_XML_ID"] ?? '';

// Проверка обязательных полей компании
$requiredFields = [
    'ИНН' => !empty($companyInn),
    'Сайт' => !empty($companyWebSite),
    'Название компании' => !empty($companyName),
    //'Город' => !empty($companyCity)
];

$missingFields = [];
foreach ($requiredFields as $fieldName => $isFilled) {
    if (!$isFilled) {
        $missingFields[] = $fieldName;
    }
}

$hasRequiredFieldsErrors = !empty($missingFields);
$canShowFunctionalButtons = !$hasRequiredFieldsErrors;

// Преобразуем в массив если пришло одно значение
if (!is_array($companyBossIds)) {
    $companyBossIds = $companyBossIds ? [$companyBossIds] : [];
}
if (!is_array($companyUserIds)) {
    $companyUserIds = $companyUserIds ? [$companyUserIds] : [];
}

// Получаем информацию о руководителях
$bosses = [];
if (!empty($companyBossIds)) {
    foreach ($companyBossIds as $bossId) {
        if ($bossId) {
            $rsUser = CUser::GetByID($bossId);
            if ($boss = $rsUser->Fetch()) {
                $bosses[] = $boss;
            }
        }
    }
}

// Получаем информацию о сотрудниках (исключая руководителей)
$employees = [];
if (!empty($companyUserIds)) {
    foreach ($companyUserIds as $userId) {
        if (!in_array($userId, $companyBossIds)) {
            $rsUser = CUser::GetByID($userId);
            if ($user = $rsUser->Fetch()) {
                $employees[] = $user;
            }
        }
    }
}

// Проверяем, является ли текущий пользователь руководителем компании
global $USER;
$currentUserId = $USER->GetID();

$isCompanyBoss = in_array($currentUserId, $companyBossIds);
$isCompanyEmployee = in_array($currentUserId, $companyUserIds);
$isAdmin = $USER->IsAdmin();
$canManageCompany = $isAdmin || $isCompanyBoss;
$hasAccess = $isAdmin || $isCompanyBoss || $isCompanyEmployee;

?>

<?if($hasAccess):
    $APPLICATION->SetTitle($companyName);
    $APPLICATION->SetPageProperty("title", $companyName." купить оптом в Москве | Эклектика – нанесение логотипов на заказ");
    $APPLICATION->SetPageProperty("description", "Компания Эклектика предлагает Регистрация оптом под нанесение логотипа. ✓ Низкие цены. ✓ Доставка по России. ☎ 8(800) 777-4723");


    ?>
<div class="company-profile">
    <!-- Левая колонка: Информация о компании + Руководство -->
    <div class="company-profile__left">
        <!-- Блок 1: Информация о компании -->
        <div class="company-profile__block company-info">
            <div class="company-profile__header">
                <h2 class="company-profile__title">Информация о компании</h2>
                <div class="company-profile__badges">
                    <?if($hasRequiredFieldsErrors):?>
                    <div class="company-status-badge company-status-badge--error" title="Не заполнены обязательные поля: <?=implode(', ', $missingFields)?>">
                        Требует заполнения
                    </div>
                    <?endif;?>
                    <div class="company-status-badge <?=($isMarketingAgent == 'YES') ? 'company-status-badge--active' : 'company-status-badge--inactive'?>">
                        <?=($isMarketingAgent == 'YES') ? 'Активно' : 'Не активно'?>
                    </div>
                    <?if($isHeadOfHolding == 'Y'):?>
                    <div class="company-status-badge company-status-badge--head">
                        Головная компания
                    </div>
                    <?endif;?>
                </div>
            </div>
            <div class="company-info__content">
                <div class="company-info__name">
                    <strong><?=$companyName?></strong>
                    <?if($canManageCompany):?>
                    <a href="/company/profile/edit/?id=<?=$arResult['ID']?>" class="company-info__edit-btn" title="Редактировать компанию">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.333 2.00004C11.5081 1.82494 11.716 1.68605 11.9447 1.59129C12.1735 1.49653 12.4187 1.44775 12.6663 1.44775C12.914 1.44775 13.1592 1.49653 13.3879 1.59129C13.6167 1.68605 13.8246 1.82494 13.9997 2.00004C14.1748 2.17513 14.3137 2.383 14.4084 2.61178C14.5032 2.84055 14.552 3.08575 14.552 3.33337C14.552 3.58099 14.5032 3.82619 14.4084 4.05497C14.3137 4.28374 14.1748 4.49161 13.9997 4.66671L5.33301 13.3334L1.33301 14.6667L2.66634 10.6667L11.333 2.00004Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Редактировать
                    </a>
                    <?endif;?>
                </div>  
                
                <?if($companyInn):?>
                <div class="company-info__item">
                    <span class="company-info__label">ИНН:</span>
                    <span class="company-info__value"><?=$companyInn?></span>
                </div>
                <?endif;?>
                
                <?if($companyPhone):?>
                <div class="company-info__item">
                    <span class="company-info__label">Телефон:</span>
                    <span class="company-info__value">
                        <a href="tel:<?=$companyPhone?>"><?=$companyPhone?></a>
                    </span>
                </div>
                <?endif;?>
                
                <?/*if($companyEmail):*/?><!--
                <div class="company-info__item">
                    <span class="company-info__label">Email:</span>
                    <span class="company-info__value">
                        <a href="mailto:<?php /*=$companyEmail*/?>"><?php /*=$companyEmail*/?></a>
                    </span>
                </div>
                --><?/*endif;*/?>
            </div>
        </div>

        <!-- Блок 2: Информация о руководстве -->
        <div class="company-profile__block company-management">
            <div class="company-profile__title-wrapper">
                <h2 class="company-profile__title">Руководство</h2>
                <?if(($isHeadOfHolding == 'Y') && $canManageCompany && $canShowFunctionalButtons):?>
                <button class="company-sync__btn" onclick="syncCompanyContacts(<?=$arResult['ID']?>)">
                    <svg class="company-sync__icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M13.65 2.35C12.2 0.9 10.21 0 8 0 3.58 0 0.01 3.58 0.01 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L9 7h7V0l-2.35 2.35z" fill="currentColor"/>
                    </svg>
                    <span class="company-sync__text">Синхронизировать</span>
                </button>
                <?endif;?>
            </div>
            <div class="company-management__content">
                <?if(!empty($bosses)):?>
                <?foreach($bosses as $boss):?>
                <div class="management-card" data-employee-id="<?=$boss['ID']?>">
                    <div class="management-card__avatar">
                        <?if($boss['PERSONAL_PHOTO']):?>
                            <?$photoSrc = CFile::GetPath($boss['PERSONAL_PHOTO']);?>
                            <img src="<?=$photoSrc?>" alt="<?=$boss['NAME']?> <?=$boss['LAST_NAME']?>">
                        <?else:?>
                            <div class="management-card__avatar-placeholder">
                                <?=mb_substr($boss['NAME'], 0, 1)?>
                            </div>
                        <?endif;?>
                    </div>
                    <div class="management-card__info">
                        <div class="management-card__name">
                            <?=$boss['NAME']?> <?=$boss['LAST_NAME']?>
                            <?if($boss['WORK_POSITION']):?>
                                <span class="management-card__position"> - <?=$boss['WORK_POSITION']?></span>
                            <?endif;?>
                        </div>
                        <div class="management-card__contacts">
                            <?if($boss['PERSONAL_PHONE']):?>
                            <div>
                                <a href="tel:<?=$boss['PERSONAL_PHONE']?>" class="management-card__contact">
                                    <?=$boss['PERSONAL_PHONE']?>
                                </a>
                            </div>
                            <?endif;?>
                            <?if($boss['EMAIL']):?>
                            <div>
                                <a href="mailto:<?=$boss['EMAIL']?>" class="management-card__contact">
                                    <?=$boss['EMAIL']?>
                                </a>
                            </div>
                            <?endif;?>
                        </div>
                    </div>
                </div>
                <?endforeach;?>
                <?else:?>
                <div class="company-management__empty">
                    Руководитель не назначен
                </div>
                <?endif;?>
            </div>
        </div>
    </div>

    <!-- Правая колонка: Дочерние фирмы + Список сотрудников -->
    <div class="company-profile__right">
        <!-- Блок дочерних фирм (только для головной компании) -->
        <?if($isHeadOfHolding == 'Y'):?>
        <?
        // Получаем дочерние компании
        $company = new \OnlineService\Site\Company();
        $childCompaniesData = [];
        
        $rsChildCompanies = CIBlockElement::GetList(
            ['NAME' => 'ASC'],  
            [
                'IBLOCK_ID' => $arResult['IBLOCK_ID'],
                'PROPERTY_OS_HOLDING_OF' => $arResult['ID']
            ],
            false,
            false,
            ['ID', 'NAME', 'CODE', 'DETAIL_PAGE_URL', 'PROPERTY_OS_IS_MARKETING_AGENT']
        );
        
        while($arChild = $rsChildCompanies->GetNext()) {
            $childCompaniesData[] = $arChild;
        }
        ?>
        <div class="company-profile__block company-children">
            <div class="company-profile__title-wrapper">
                <h2 class="company-profile__title">Дочерние фирмы (<?=count($childCompaniesData)?>)</h2>
                <?if($canManageCompany && $canShowFunctionalButtons):?>
                <a href="/director/add_new_branch.php?head_company=<?=$arResult['ID']?>" id="lk-add-new-company" class="company-children__add-btn">
                    + Добавить
                </a>
                <?endif;?>
            </div>
            <div class="company-children__content">
                <?if(!empty($childCompaniesData)):?>
                <div class="children-list">
                    <?foreach($childCompaniesData as $child):?>
                    <?php
                    $isChildActive = $child['PROPERTY_OS_IS_MARKETING_AGENT_VALUE'] ?? '';
                    $isChildActive = ($isChildActive == 'Да');
                    ?>
                    <div class="child-company-card">
                        <?if($isChildActive):?>
                        <a href="<?=$child['DETAIL_PAGE_URL']?>" class="child-company-card__link">
                        <?else:?>
                        <div class="child-company-card__link child-company-card__link--disabled">
                        <?endif;?>
                            <div class="child-company-card__icon">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3 5C3 3.89543 3.89543 3 5 3H9L11 5H15C16.1046 5 17 5.89543 17 7V15C17 16.1046 16.1046 17 15 17H5C3.89543 17 3 16.1046 3 15V5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div class="child-company-card__content">
                                <div class="child-company-card__name"><?=$child['NAME']?></div>
                            </div>
                            <div class="child-company-card__badges">
                                <span class="badge badge--<?=$isChildActive ? 'active' : 'inactive'?>">
                                    <?=$isChildActive ? 'Активно' : 'На модерации'?>
                                </span>
                            </div>
                        <?if($isChildActive):?>
                        </a>
                        <?else:?>
                        </div>
                        <?endif;?>
                    </div>
                    <?endforeach;?>
                </div>
                <?else:?>
                <div class="company-children__empty">
                    Дочерние фирмы не добавлены
                </div>
                <?endif;?>
            </div>
        </div>
        <?endif;?>
        
        <!-- Блок 3: Список сотрудников -->
        <div class="company-profile__block company-employees">
            <div class="company-profile__title-wrapper">
                <h2 class="company-profile__title">Сотрудники</h2>
                <?if($isCompanyBoss && $canShowFunctionalButtons):?>
                <a href="/director/person/add-new-person.php?head_company=<?=$arResult['ID']?>" class="company-employees__add-btn">
                    + Добавить
                </a>
                <?endif;?>
            </div>
            <div class="company-employees__content">
                <?if(!empty($employees)):?>
                <div class="employees-list">
                    <?foreach($employees as $employee):?>
                    <div class="employee-card" data-employee-id="<?=$employee['ID']?>">
                        <div class="employee-card__avatar">
                            <?if($employee['PERSONAL_PHOTO']):?>
                                <?$photoSrc = CFile::GetPath($employee['PERSONAL_PHOTO']);?>
                                <img src="<?=$photoSrc?>" alt="<?=$employee['NAME']?> <?=$employee['LAST_NAME']?>">
                            <?else:?>
                                <div class="employee-card__avatar-placeholder">
                                    <?=mb_substr($employee['NAME'], 0, 1)?>
                                </div>
                            <?endif;?>
                        </div>
                        <div class="employee-card__info">
                            <div class="employee-card__name">
                                <?=$employee['NAME']?> <?=$employee['LAST_NAME']?>
                                <?if($employee['WORK_POSITION']):?>
                                    <span class="employee-card__position"> - <?=$employee['WORK_POSITION']?></span>
                                <?endif;?>
                            </div>
                            <?if($employee['PERSONAL_PHONE'] || $employee['EMAIL']):?>
                            <div class="employee-card__contacts">
                                <?if($employee['PERSONAL_PHONE']):?>
                                <a href="tel:<?=$employee['PERSONAL_PHONE']?>" class="employee-card__contact">
                                    <?=$employee['PERSONAL_PHONE']?>
                                </a>
                                <?endif;?>
                                <?if($employee['EMAIL']):?>
                                <a href="mailto:<?=$employee['EMAIL']?>" class="employee-card__contact">
                                    <?=$employee['EMAIL']?>
                                </a>
                                <?endif;?>
                            </div>
                            <?endif;?>
                        </div>
                    </div>
                    <?endforeach;?>
                </div>
                <?else:?>
                <div class="company-employees__empty">
                    Сотрудники не добавлены
                </div>
                <?endif;?>
            </div>
        </div>
    </div>
</div>

<script>
function syncCompanyContacts(companyId) {
    const btn = document.querySelector('.company-sync__btn');
    const originalText = btn.querySelector('.company-sync__text').textContent;
    const icon = btn.querySelector('.company-sync__icon');
    
    // Показываем состояние загрузки
    btn.disabled = true;
    btn.querySelector('.company-sync__text').textContent = 'Синхронизация...';
    icon.style.animation = 'spin 1s linear infinite';
    
    // AJAX запрос
    fetch('/local/classes/ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ACTION=SYNC_COMPANY_CONTACTS&COMPANY_ID=${companyId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Успешная синхронизация
            btn.querySelector('.company-sync__text').textContent = 'Готово!';
            btn.style.background = '#d4edda';
            btn.style.color = '#155724';
            btn.style.borderColor = '#c3e6cb';
            
            // Перезагружаем страницу через 2 секунды
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            // Ошибка
            btn.querySelector('.company-sync__text').textContent = 'Ошибка';
            btn.style.background = '#f8d7da';
            btn.style.color = '#721c24';
            btn.style.borderColor = '#f5c6cb';
            
            alert('Ошибка синхронизации: ' + data.error);
            
            // Возвращаем исходное состояние через 3 секунды
            setTimeout(() => {
                resetButton();
            }, 3000);
        }
    })
    .catch(error => {
        btn.querySelector('.company-sync__text').textContent = 'Ошибка';
        btn.style.background = '#f8d7da';
        btn.style.color = '#721c24';
        btn.style.borderColor = '#f5c6cb';
        
        alert('Ошибка сети при синхронизации');
        
        setTimeout(() => {
            resetButton();
        }, 3000);
    });
    
    function resetButton() {
        btn.disabled = false;
        btn.querySelector('.company-sync__text').textContent = originalText;
        icon.style.animation = '';
        btn.style.background = '';
        btn.style.color = '';
        btn.style.borderColor = '';
    }
}

// CSS анимация для иконки 
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .employee-card, .management-card {
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    
    .employee-card:hover, .management-card:hover {
        background-color: #f8f9fa;
    }
`;
document.head.appendChild(style);

// Обработка кликов по карточкам сотрудников и руководителей
document.addEventListener('DOMContentLoaded', function() {
    const employeeCards = document.querySelectorAll('.employee-card, .management-card');
    
    employeeCards.forEach(function(card) {
        card.addEventListener('click', function(event) {
            // Проверяем, не был ли клик по ссылке контакта
            if (event.target.closest('.employee-card__contact') || event.target.closest('.management-card__contact')) {
                return; // Не переходим на профиль, если клик по телефону/email
            }
            
            // Получаем ID сотрудника/руководителя и переходим на его профиль
            const employeeId = card.getAttribute('data-employee-id');
            if (employeeId) {
                window.location.href = '/company/user/' + employeeId + '/';
            }
        });
    });
});
</script>
<?else:
    $APPLICATION->SetTitle("Доступ запрещен");
    $APPLICATION->AddChainItem("Доступ запрещен", "");
    $APPLICATION->SetPageProperty("title", "Доступ запрещен купить оптом в Москве | Эклектика – нанесение логотипов на заказ");
    $APPLICATION->SetPageProperty("description", "Компания Эклектика предлагает Регистрация оптом под нанесение логотипа. ✓ Низкие цены. ✓ Доставка по России. ☎ 8(800) 777-4723");

    ?>
<div class="company-profile__no-access">
    <div class="no-access-message">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8 0-1.85.63-3.55 1.69-4.9L16.9 18.31C15.55 19.37 13.85 20 12 20zm6.31-3.1L7.1 5.69C8.45 4.63 10.15 4 12 4c4.42 0 8 3.58 8 8 0 1.85-.63 3.55-1.69 4.9z" fill="currentColor"/>
        </svg>
        <h3>Доступ ограничен</h3>
        <p>У вас нет прав для просмотра информации об этой компании.</p>
        <a href="/personal/lichnyj-kabinet.php" class="btn btn-primary">Вернуться в личный кабинет</a>
    </div>
</div>
<?endif;?>