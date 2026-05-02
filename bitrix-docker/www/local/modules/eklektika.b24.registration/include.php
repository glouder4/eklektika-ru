<?php

defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Loader;

/**
 * Регистрация юрлица на сайте и синхронизация компании/контакта с CRM через n8n.
 * Перенос классов из eklektika.b24.usersync — см. tasks/2026-05-01-b24-usersync-registration-module-split.
 *
 * Планируемый namespace корня домена: OnlineService\B24\Registration\...
 */
Loader::registerAutoLoadClasses(null, [
    \OnlineService\B24\Registration\CrmRegistrationOrchestrator::class => '/local/modules/eklektika.b24.registration/lib/CrmRegistrationOrchestrator.php',
    \OnlineService\B24\Registration\AjaxRegisterActionService::class => '/local/modules/eklektika.b24.registration/lib/Registration/AjaxRegisterActionService.php',
    \OnlineService\B24\Registration\CompanyRegistrationService::class => '/local/modules/eklektika.b24.registration/lib/Registration/CompanyRegistrationService.php',
    \OnlineService\B24\Registration\Config\RegisterUserCompanyConfig::class => '/local/modules/eklektika.b24.registration/lib/Config/RegisterUserCompanyConfig.php',
    \OnlineService\B24\Registration\AjaxRegister\CrmRegistrationN8nTransport::class => '/local/modules/eklektika.b24.registration/lib/Registration/AjaxRegister/CrmRegistrationN8nTransport.php',
    \OnlineService\B24\Registration\AjaxRegister\CrmRegistrationN8nPrecheckResponse::class => '/local/modules/eklektika.b24.registration/lib/Registration/AjaxRegister/CrmRegistrationN8nPrecheckResponse.php',
    \OnlineService\B24\Registration\AjaxRegister\AjaxRegisterCrmContactPrecheck::class => '/local/modules/eklektika.b24.registration/lib/Registration/AjaxRegister/AjaxRegisterCrmContactPrecheck.php',
    \OnlineService\B24\Registration\AjaxRegister\AjaxRegisterResponse::class => '/local/modules/eklektika.b24.registration/lib/Registration/AjaxRegister/AjaxRegisterResponse.php',
    \OnlineService\B24\Registration\AjaxRegister\AjaxRegisterExecutionContext::class => '/local/modules/eklektika.b24.registration/lib/Registration/AjaxRegister/AjaxRegisterExecutionContext.php',
    \OnlineService\B24\Registration\AjaxRegister\AjaxRegisterPostParser::class => '/local/modules/eklektika.b24.registration/lib/Registration/AjaxRegister/AjaxRegisterPostParser.php',
    \OnlineService\B24\Registration\AjaxRegister\AjaxRegisterSiteCompanyResolver::class => '/local/modules/eklektika.b24.registration/lib/Registration/AjaxRegister/AjaxRegisterSiteCompanyResolver.php',
    \OnlineService\B24\Registration\AjaxRegister\AjaxRegisterDuplicateGuard::class => '/local/modules/eklektika.b24.registration/lib/Registration/AjaxRegister/AjaxRegisterDuplicateGuard.php',
    \OnlineService\B24\Registration\AjaxRegister\AjaxRegisterUserPayloadBuilder::class => '/local/modules/eklektika.b24.registration/lib/Registration/AjaxRegister/AjaxRegisterUserPayloadBuilder.php',
    \OnlineService\B24\Registration\AjaxRegister\AjaxRegisterBitrixApplication::class => '/local/modules/eklektika.b24.registration/lib/Registration/AjaxRegister/AjaxRegisterBitrixApplication.php',
]);
