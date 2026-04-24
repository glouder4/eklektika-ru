<?php

/**
 * Глобальные функции совместимости для вызовов вне классов (Company, usersync, скрипты CRM).
 * Подключается из include.php модуля eklektika.b24.rest после регистрации классов.
 */

if (!function_exists('sendRequestB24')) {
    /**
     * @deprecated Используйте \OnlineService\B24\RestClient::callRestMethod()
     */
    function sendRequestB24($method, $params, $debug = false)
    {
        return \OnlineService\B24\RestClient::callRestMethod($method, $params, $debug);
    }
}

if (!function_exists('sendRequest')) {
    /**
     * @deprecated Используйте \OnlineService\B24\RestClient::postAjaxProxy()
     */
    function sendRequest($params, $debug = false)
    {
        return \OnlineService\B24\RestClient::postAjaxProxy($params, $debug);
    }
}

if (!function_exists('findContact')) {
    function findContact($param, $arFields, $select)
    {
        $qrList = [
            'fields' => [],
            'params' => [
                $param => $arFields[$select],
            ],
            'select' => [
                $param,
            ],
            'filter' => [],
        ];

        return sendRequestB24('crm.contact.list', $qrList);
    }
}

if (!function_exists('newRest')) {
    function newRest($param, $arFields, $select)
    {
        $qrList = [
            'fields' => [],
            'params' => [],
            'select' => [],
            'filter' => [],
        ];

        $qrList['filter'][$param] = $arFields[$select];
        $qrList['select'][] = $param;

        return sendRequestB24('crm.contact.list', $qrList);
    }
}
