<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */

$company = $arResult['COMPANY'];
$companyId = $arResult['COMPANY_ID'];
$companyCode = $company['CODE'] ?? $companyId;

$APPLICATION->SetTitle($company['LEGAN_ENTITY_NAME']);
$APPLICATION->SetPageProperty("title", $company['LEGAN_ENTITY_NAME'] ." купить оптом в Москве | Эклектика – нанесение логотипов на заказ");
$APPLICATION->SetPageProperty("description", "Компания Эклектика предлагает Регистрация оптом под нанесение логотипа. ✓ Низкие цены. ✓ Доставка по России. ☎ 8(800) 777-4723");


?> 

<div class="company-edit">
    <div class="company-edit__header">
        <h1 class="company-edit__title">Редактирование компании</h1>
        <a href="/personal/lichnyj-kabinet.php" class="company-edit__back-btn">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10 13L5 8L10 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Назад к профилю
        </a>
    </div>

    <form id="companyEditForm" class="company-edit__form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_company">
        <input type="hidden" name="company_id" value="<?=$companyId?>">
        
        <!-- Основная информация -->
        <div class="form-section">
            <h2 class="form-section__title">Основная информация</h2>
            
            <div class="form-grid">
                <div class="form-group form-group--required">
                    <label for="company_name" class="form-label">
                        Название компании
                        <span class="form-required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="company_name" 
                        name="LEGAN_ENTITY_NAME"
                        class="form-input"
                        value="<?=htmlspecialchars($company['LEGAN_ENTITY_NAME'] ?? '')?>"
                        required
                    >
                    <div class="form-error" style="display: none;">Поле обязательно для заполнения</div>
                </div>

                <div class="form-group form-group--required">
                    <label for="company_inn" class="form-label">
                        ИНН
                        <span class="form-required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="company_inn" 
                        name="LEGAN_ENTITY_INN"
                        class="form-input"
                        value="<?=htmlspecialchars($company['LEGAN_ENTITY_INN'] ?? '')?>"
                        required
                    >
                    <div class="form-error" style="display: none;">Поле обязательно для заполнения</div>
                </div>

                <div class="form-group form-group--required">
                    <label for="company_city" class="form-label">
                        Город
                        <span class="form-required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="company_city" 
                        name="LEGAN_ENTITY_CITY"
                        class="form-input"
                        value="<?=htmlspecialchars($company['LEGAN_ENTITY_CITY'] ?? '')?>"
                        required
                    >
                    <div class="form-error" style="display: none;">Поле обязательно для заполнения</div>
                </div>

                <div class="form-group form-group--required">
                    <label for="company_website" class="form-label">
                        Сайт
                        <span class="form-required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="company_website"  
                        name="LEGAN_ENTITY_WWW"
                        class="form-input"
                        value="<?=htmlspecialchars($company['LEGAN_ENTITY_WWW'] ?? '')?>"
                        placeholder="https://example.com"
                        required
                    >
                    <div class="form-error" style="display: none;">Поле обязательно для заполнения</div>
                </div>
            </div>
        </div>

        <!-- Контактная информация -->
        <div class="form-section">
            <h2 class="form-section__title">Контактная информация</h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="company_phone" class="form-label">Телефон</label>
                    <input 
                        type="tel" 
                        id="company_phone" 
                        name="LEGAN_ENTITY_PHONE"
                        class="form-input"
                        value="<?=htmlspecialchars($company['LEGAN_ENTITY_PHONE'] ?? '')?>"
                        placeholder="+7 (___) ___-__-__"
                    >
                </div>

                <div class="form-group">
                    <label for="company_email" class="form-label">Email</label>
                    <input 
                        type="email" 
                        id="company_email" 
                        name="LEGAN_ENTITY_EMAIL"
                        class="form-input"
                        value="<?=htmlspecialchars($company['LEGAN_ENTITY_EMAIL'] ?? '')?>"
                    >
                    <div class="form-error" style="display: none;">Введите корректный email</div>
                </div>
            </div>
        </div>

        <!-- Реквизиты -->
        <div class="form-section">
            <h2 class="form-section__title">Реквизиты компании</h2>
            
            <div class="form-grid form-grid--single">
                <div class="form-group">
                    <label for="company_requisites" class="form-label">Файл реквизитов</label>
                    <?php
                    $currentFile = null;
                    if (!empty($company['LEGAN_ENTITY_FILE'])) {
                        $currentFile = CFile::GetFileArray($company['LEGAN_ENTITY_FILE']);
                    }
                    ?>
                    
                    <?php if ($currentFile): ?>
                    <div class="file-info">
                        <div class="file-info__current">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 1H3C2.73478 1 2.48043 1.10536 2.29289 1.29289C2.10536 1.48043 2 1.73478 2 2V14C2 14.2652 2.10536 14.5196 2.29289 14.7071C2.48043 14.8946 2.73478 15 3 15H13C13.2652 15 13.5196 14.8946 13.7071 14.7071C13.8946 14.5196 14 14.2652 14 14V6L9 1Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9 1V6H14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <a href="<?=$currentFile['SRC']?>" target="_blank" class="file-info__link">
                                <?=$currentFile['ORIGINAL_NAME']?> (<?=CFile::FormatSize($currentFile['FILE_SIZE'])?>)
                            </a>
                        </div>
                        <label class="file-info__change">
                            <input type="checkbox" name="delete_requisites" value="Y" id="delete_requisites">
                            Удалить файл
                        </label>
                    </div>
                    <?php endif; ?>
                    
                    <input 
                        type="file" 
                        id="company_requisites" 
                        name="LEGAN_ENTITY_FILE" 
                        class="form-input form-input--file"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                    >
                    <div class="form-hint">Допустимые форматы: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG. Максимальный размер: 10 МБ</div>
                </div>
            </div>
        </div>

        <!-- Кнопки управления -->
        <div class="form-actions">
            <button type="submit" class="btn btn--primary" id="saveButton">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.333 7.38666V13.3333C13.333 13.687 13.1926 14.0261 12.9426 14.2761C12.6925 14.5262 12.3535 14.6667 11.9997 14.6667H3.99967C3.6459 14.6667 3.30686 14.5262 3.05681 14.2761C2.80676 14.0261 2.66634 13.687 2.66634 13.3333V5.33333C2.66634 4.97971 2.80676 4.64057 3.05681 4.39052C3.30686 4.14048 3.6459 4 3.99967 4H9.94634" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M12 2L14 4L8 10L6 10L6 8L12 2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Сохранить изменения
            </button>
            <a href="/company/profile/<?=$companyCode?>/" class="btn btn--secondary">
                Отмена
            </a>
        </div>
    </form>
</div>

<!-- Модальное окно уведомлений -->
<div class="notification-overlay" id="notificationOverlay" style="display: none;">
    <div class="notification" id="notification">
        <div class="notification-icon" id="notificationIcon">
            <div class="loading-spinner" id="loadingSpinner"></div>
            <span id="statusIcon" style="display: none;">✓</span>
        </div>
        <h3 class="notification-title" id="notificationTitle">Обработка запроса</h3>
        <p class="notification-message" id="notificationMessage">Пожалуйста, подождите...</p>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('companyEditForm');
    const overlay = document.getElementById('notificationOverlay');
    const notification = document.getElementById('notification');
    const notificationIcon = document.getElementById('notificationIcon');
    const statusIcon = document.getElementById('statusIcon');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const notificationTitle = document.getElementById('notificationTitle');
    const notificationMessage = document.getElementById('notificationMessage');
    const progressFill = document.getElementById('progressFill');

    // Функция показа уведомления
    function showNotification(type, title, message, redirectUrl = null) {
        notification.className = 'notification ' + type;
        notificationIcon.className = 'notification-icon ' + type;
        progressFill.className = 'progress-fill ' + type;
        
        if (type === 'success') {
            statusIcon.innerHTML = '✓';
            statusIcon.style.display = 'block';
            loadingSpinner.style.display = 'none';
        } else if (type === 'error') {
            statusIcon.innerHTML = '✕';
            statusIcon.style.display = 'block';
            loadingSpinner.style.display = 'none';
        } else {
            statusIcon.style.display = 'none';
            loadingSpinner.style.display = 'block';
        }
        
        notificationTitle.textContent = title;
        notificationMessage.textContent = message;
        overlay.style.display = 'flex';
        
        progressFill.style.width = '0%';
        setTimeout(() => {
            progressFill.style.width = '100%';
        }, 10);
        
        if (type !== 'loading') {
            setTimeout(() => {
                if (type === 'success' && redirectUrl) {
                    window.location.href = redirectUrl;
                } else {
                    overlay.style.display = 'none';
                }
            }, 3000);
        }
    }

    // Валидация формы
    function validateForm() {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            const formGroup = field.closest('.form-group');
            const errorDiv = formGroup ? formGroup.querySelector('.form-error') : null;
            
            if (!field.value.trim()) {
                isValid = false;
                if (formGroup) formGroup.classList.add('has-error');
                if (errorDiv) errorDiv.style.display = 'block';
            } else {
                if (formGroup) formGroup.classList.remove('has-error');
                if (errorDiv) errorDiv.style.display = 'none';
            }
        });
        
        return isValid;
    }

    // Убираем ошибку при вводе
    form.querySelectorAll('.form-input, .form-textarea').forEach(field => {
        field.addEventListener('input', function() {
            const formGroup = this.closest('.form-group');
            const errorDiv = formGroup ? formGroup.querySelector('.form-error') : null;
            
            if (formGroup) formGroup.classList.remove('has-error');
            if (errorDiv) errorDiv.style.display = 'none';
        });
    });

    // Обработка отправки формы
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            showNotification('error', 'Ошибка валидации', 'Пожалуйста, заполните все обязательные поля');
            return;
        }
        
        showNotification('loading', 'Сохранение', 'Обновляем данные компании...');
        
        const formData = new FormData(form);
        
        fetch('/local/components/online-service/company.profile.edit/ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const redirectUrl = '/company/profile/' + (data.company_id || '<?=$companyId?>') + '/';
                showNotification('success', 'Успешно!', 'Данные компании обновлены', redirectUrl);
            } else {
                showNotification('error', 'Ошибка!', data.message || 'Не удалось обновить данные');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Ошибка!', 'Произошла ошибка при сохранении данных');
        });
    });

    // Маска для телефона
    const phoneInput = document.getElementById('company_phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            let formattedValue = '';
            
            if (value.length > 0) {
                formattedValue = '+' + value.substring(0, 1);
                if (value.length > 1) {
                    formattedValue += ' (' + value.substring(1, 4);
                    if (value.length > 4) {
                        formattedValue += ') ' + value.substring(4, 7);
                        if (value.length > 7) {
                            formattedValue += '-' + value.substring(7, 9);
                            if (value.length > 9) {
                                formattedValue += '-' + value.substring(9, 11);
                            }
                        }
                    }
                }
            }
            
            e.target.value = formattedValue;
        });
    }
});
</script>

