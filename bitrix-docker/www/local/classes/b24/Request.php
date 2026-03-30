<?php

namespace OnlineService\B24
{
    class Request
    {
        /**
         * Прямой вызов REST Bitrix24 (crm.*). База вебхука без завершающего слэша, например:
         * https://portal.bitrix24.ru/rest/1/xxxxxxxxxxxxxxxx
         * Окружение: B24_REST_WEBHOOK или B24_WEBHOOK_URL, либо константа B24_REST_WEBHOOK в init.php.
         *
         * @return mixed поле result из ответа API или null при ошибке/не настроенном вебхуке
         */
        public static function restRequest(string $method, array $params, bool $debug = false)
        {
            $base = getenv('B24_REST_WEBHOOK');
            if ($base === false || $base === '') {
                $base = getenv('B24_WEBHOOK_URL');
            }
            if (($base === false || $base === '') && \defined('B24_REST_WEBHOOK')) {
                $base = (string) \constant('B24_REST_WEBHOOK');
            }
            $base = \is_string($base) ? \rtrim($base, '/') : '';

            if ($base === '') {
                if ($debug && \function_exists('pre')) {
                    \pre('[Request::restRequest] Не задан вебхук REST: B24_REST_WEBHOOK или B24_WEBHOOK_URL');
                }
                return null;
            }

            $url = $base . '/' . $method;
            $payload = \json_encode($params, JSON_UNESCAPED_UNICODE);

            $curl = \curl_init();
            \curl_setopt_array($curl, [
                \CURLOPT_URL => $url,
                \CURLOPT_POST => true,
                \CURLOPT_POSTFIELDS => $payload,
                \CURLOPT_RETURNTRANSFER => true,
                \CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
                \CURLOPT_SSL_VERIFYPEER => 0,
                \CURLOPT_SSL_VERIFYHOST => false,
                \CURLOPT_TIMEOUT => 60,
                \CURLOPT_CONNECTTIMEOUT => 15,
            ]);

            $result = \curl_exec($curl);
            $httpCode = (int) \curl_getinfo($curl, \CURLINFO_HTTP_CODE);
            $curlErrno = \curl_errno($curl);
            $curlError = \curl_error($curl);
            \curl_close($curl);

            if ($debug && \function_exists('pre')) {
                \pre('=== REST ' . $method . ' ===');
                \pre('URL: ' . $url);
                \pre('HTTP: ' . $httpCode);
                if ($curlErrno) {
                    \pre('CURL: ' . $curlError);
                }
                \pre('Body: ' . $result);
            }

            if ($curlErrno) {
                return null;
            }
            if ($httpCode !== 200) {
                return null;
            }

            $decoded = \json_decode((string) $result, true);
            if (!\is_array($decoded)) {
                return null;
            }
            if (isset($decoded['error'])) {
                return null;
            }

            return $decoded['result'] ?? null;
        }

        protected function sendRequest($params, $debug = false)
        {
            $queryUrl = URL_B24 . 'local/classes/site_requests_handler.php';
            $curl = \curl_init();
            $queryData = \http_build_query($params);

            \curl_setopt_array($curl, [
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_POST => 1,
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $queryUrl,
                CURLOPT_POSTFIELDS => $queryData,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
            ]);

            $result = \curl_exec($curl);
            $httpCode = \curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = \curl_error($curl);
            $curlErrno = \curl_errno($curl);

            \curl_close($curl);

            if ($debug) {
                \pre("=== CURL Request Details ===");
                \pre("URL: " . $queryUrl);
                \pre("Params: " . print_r($params, true));
                \pre("HTTP Code: " . $httpCode);
                \pre("CURL Error: " . $curlError);
                \pre("CURL Errno: " . $curlErrno);
                \pre("Raw Response: " . $result);
            }

            if ($curlErrno) {
                \pre("CURL Error occurred: " . $curlError);
                return [
                    'success' => 0,
                    'error' => 'CURL Error: ' . $curlError,
                    'errno' => $curlErrno,
                ];
            }

            if ($httpCode !== 200) {
                if ($debug) {
                    \pre("HTTP Error: " . $httpCode);
                }

                return [
                    'success' => 0,
                    'error' => 'HTTP Error: ' . $httpCode,
                    'response' => $result,
                ];
            }

            $decodedResult = \json_decode($result, true);

            if (\json_last_error() !== \JSON_ERROR_NONE) {
                if ($debug) {
                    \pre("JSON Parse Error: " . \json_last_error_msg());
                    \pre("Raw response that failed to parse: " . $result);
                }
                return [
                    'success' => 0,
                    'error' => 'JSON Parse Error: ' . \json_last_error_msg(),
                    'raw_response' => $result,
                ];
            }
            if ($debug) {
                \pre("=== Parsed Response ===");
                \pre($decodedResult);
                die();
            }

            return $decodedResult;
        }
    }
}

namespace
{
    if (!\function_exists('sendRequestB24')) {
        /**
         * Глобальная обёртка для совместимости с вызовами из классов (корневой namespace).
         */
        function sendRequestB24(string $method, array $params, $debug = false)
        {
            return \OnlineService\B24\Request::restRequest($method, $params, (bool) $debug);
        }
    }
}
