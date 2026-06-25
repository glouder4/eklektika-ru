<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Application;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

header('Content-Type: application/json; charset=utf-8');

$request = Application::getInstance()->getContext()->getRequest();
$webFormKey = (int)($request->getPost('WEB_FORM') ?: $request->getPost('WEB_FORM_ID') ?: 1);

$result = WebFormSubmissionHandler::submit($webFormKey, $request->getPostList()->toArray());

echo json_encode($result);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
