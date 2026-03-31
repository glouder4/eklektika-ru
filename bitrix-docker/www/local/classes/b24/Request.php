<?php

namespace OnlineService\B24
{
    class Request
    {
        private static function truncateLog(string $s, int $max = 2000): string
        {
            $s = (string) \preg_replace('/\s+/', ' ', $s);

            return \strlen($s) <= $max ? $s : \substr($s, 0, $max) . '…';
        }

        private static function logRest(string $line): void
        {
            $dRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
            if ($dRoot === '') {
                return;
            }
            $dir = $dRoot . '/local/logs';
            @\mkdir($dir, 0755, true);
            $path = $dir . '/b24-rest.log';
            @\file_put_contents(
                $path,
                \date('Y-m-d H:i:s') . ' ' . $line . \PHP_EOL,
                \FILE_APPEND | \LOCK_EX
            );
        }

        /**
         * Прямой вызов REST Bitrix24 (crm.*). Лог: local/logs/b24-rest.log.
         * База вебхука без завершающего слэша; env B24_REST_WEBHOOK / B24_WEBHOOK_URL или константа B24_REST_WEBHOOK.
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
                self::logRest('ERROR no_webhook method=' . $method);
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
                self::logRest(
                    'ERROR curl method=' . $method . ' errno=' . $curlErrno . ' ' . $curlError
                );
                return null;
            }
            if ($httpCode !== 200) {
                self::logRest(
                    'ERROR http method=' . $method . ' code=' . $httpCode
                    . ' body=' . self::truncateLog((string) $result)
                );
                return null;
            }

            $decoded = \json_decode((string) $result, true);
            if (!\is_array($decoded)) {
                self::logRest(
                    'ERROR json method=' . $method . ' raw=' . self::truncateLog((string) $result)
                );
                return null;
            }
            if (isset($decoded['error'])) {
                $err = (string) ($decoded['error'] ?? '');
                $desc = (string) ($decoded['error_description'] ?? '');
                self::logRest(
                    'ERROR api method=' . $method . ' error=' . $err . ' desc=' . self::truncateLog($desc)
                );
                return null;
            }

            self::logRest('OK method=' . $method . ' http=' . $httpCode);

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
