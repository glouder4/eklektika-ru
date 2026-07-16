<?php
// ========== ПОДКЛЮЧАЕМ PHPMailer ==========
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$phpmailerBase = __DIR__ . '/../local/PHPMailer/src';
require $phpmailerBase . '/Exception.php';
require $phpmailerBase . '/PHPMailer.php';
require $phpmailerBase . '/SMTP.php';

// Обработчик заявок лендинга «День нефтяника».
// Куда слать лиды:
// Вебхук Битрикс24 (crm.lead.add): вставьте URL — лид уйдёт в CRM автоматически.
// Битрикс24 → Разработчикам → Другое → Входящий вебхук → права CRM.
// Пример: https://ВАШ_ПОРТАЛ.bitrix24.ru/rest/1/xxxxxxxx/crm.lead.add.json
$b24_webhook = 'https://bitrix.eklektika.ru/rest/1/p3w9pdvar22bpv27/crm.lead.add.json';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }

function clean($k) {
    return isset($_POST[$k]) ? trim(strip_tags(mb_substr($_POST[$k], 0, 500))) : '';
}

$name    = clean('name');
$company = clean('company');
$phone   = clean('phone');
$email   = clean('email');
$comment = clean('comment');
$subscribe = isset($_POST['subscribe']) ? 'ДА' : 'Нет';
$product = clean('product');
$to = 'team@eklektika.ru';

if ($name === '' || $phone === '' || $company === '') {
    http_response_code(422);
    exit('Заполните обязательные поля');
}

// ========== ФОРМИРУЕМ ПИСЬМО ==========
$subject = 'Заявка: День нефтяника — ' . $product;
$body = "Лендинг: День нефтяника 2026\n"
    . "Товар/интерес: $product\n"
    . "Имя: $name\n"
    . "Компания: $company\n"
    . "Телефон: $phone\n"
    . "Email: $email\n"
    . "Комментарий: $comment\n"
    . "Согласие на рекламную рассылку: $subscribe\n"
    . "Время: " . date('d.m.Y H:i') . "\n"
    . "IP: " . ($_SERVER['REMOTE_ADDR'] ?? '');

// ========== ОТПРАВКА ЧЕРЕЗ SMTP (Яндекс) ==========
$mail = new PHPMailer(true);

try {
    // Настройки SMTP
    $mail->isSMTP();
    $mail->Host = 'mail.eklektika.ru';
    $mail->SMTPAuth = true;
    $mail->Username = 'no-reply@eklektika.ru';     // Логин от почты
    $mail->Password = 'XbAo0caY9I3E9~4uZd-z';  // Пароль приложения (НЕ пароль от почты!)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    // Отключаем проверку SSL для отладки (на проде убрать)
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    // Кодировка и отправитель
    $mail->CharSet = 'UTF-8';
    $mail->setFrom('no-reply@eklektika.ru', 'Сайт eklektika.ru');
    $mail->addAddress($to);
    $mail->addReplyTo('no-reply@eklektika.ru', 'Сайт eklektika.ru');

    // Контент
    $mail->isHTML(false);
    $mail->Subject = $subject;
    $mail->Body = $body;

    // Отправляем
    $mail_sent = $mail->send();
    $debug = "✅ Письмо отправлено через SMTP!";

} catch (Exception $e) {
    $mail_sent = false;
    $debug = "❌ Ошибка: " . $mail->ErrorInfo;
}


// Если письмо не ушло - показываем подробную ошибку
if (!$mail_sent) {
    echo "<pre style='color:red;'>";
    echo "=== ПОДРОБНАЯ ДИАГНОСТИКА ===\n";
    echo "Проверьте:\n";
    echo "1. Логин: no-reply@eklektika.ru\n";
    echo "2. Пароль приложения (создан в Яндексе)\n";
    echo "3. Включен ли SMTP в настройках почты\n";
    echo "4. Не заблокирован ли доступ к почте\n";
    echo "</pre>";
}

/**
 * Отправка лида в Битрикс24
 */
function sendLeadToBitrix24($b24_webhook, $data) {
    $fields = [
        'TITLE' => 'День нефтяника 2026: ' . ($data['product'] ?? ''),
        'NAME' => $data['name'] ?? '',
        'COMPANY_TITLE' => $data['company'] ?? '',
        'PHONE' => [['VALUE' => $data['phone'] ?? '', 'VALUE_TYPE' => 'WORK']],
        'EMAIL' => !empty($data['email']) ? [['VALUE' => $data['email'], 'VALUE_TYPE' => 'WORK']] : [],
        'COMMENTS' => ($data['comment'] ?? '') . "\nСогласие на рекламную рассылку: " . ($data['subscribe'] ?? 'Нет'),
        'SOURCE_ID' => 'WEB',
        'UTM_SOURCE' => clean($data['utm_source'] ?? ''),
        'UTM_CAMPAIGN' => clean($data['utm_campaign'] ?? ''),
    ];

    $payload = json_encode(['fields' => $fields], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($b24_webhook);
    curl_setopt_array($ch, [
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErrno = curl_errno($ch);
    curl_close($ch);

    if ($curlErrno || $httpCode !== 200) {
        return false;
    }

    $data = json_decode($response, true);

    return isset($data['result']);
}


// ── Отправка лида в Битрикс24 (если задан вебхук) ──
if ($b24_webhook !== '') {
    $data = [
        'product' => $product,
        'name' => $name,
        'company' => $company,
        'phone' => $phone,
        'email' => $email,
        'comment' => $comment,
        'subscribe' => $subscribe,
        'utm_source' => clean('utm_source'),
        'utm_campaign' => clean('utm_campaign'),
    ];

    $b24_ok = sendLeadToBitrix24($b24_webhook, $data);
} else { $b24_ok = false; }

if ($mail_sent || $b24_ok) { http_response_code(200); echo 'OK'; }
else { http_response_code(500); echo 'Send error'; }
