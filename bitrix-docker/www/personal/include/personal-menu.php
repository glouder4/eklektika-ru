<?php
/**
 * Меню личного кабинета
 */
if (!defined("B_PROLOG_INCLUDED") && !defined("BITRIX_INCLUDED")) {
    return;
}
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<nav class="personal-menu sale-personal-section-links-desktop">
    <div id="personal-profile-menu-desktop">
        <a href="/personal/lichnyj-kabinet.php" class="menu-desktop-link personal-menu__link<?= $currentPage === 'lichnyj-kabinet.php' ? ' personal-menu__link--active active active' : '' ?>">Главная страница личного кабинета</a>
        <a href="/personal/redaktirovanie-dannyh.php" class="menu-desktop-link personal-menu__link<?= $currentPage === 'redaktirovanie-dannyh.php' ? ' personal-menu__link--active active' : '' ?>">Редактировать данные</a>
        <a href="/personal/prosmotr-zakazov.php" class="menu-desktop-link personal-menu__link<?= $currentPage === 'prosmotr-zakazov.php' ? ' personal-menu__link--active active' : '' ?>">Просмотр заказов</a>
    </div>
</nav>
