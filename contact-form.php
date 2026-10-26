<?php

function mima_redirect_contact(string $lang, string $status): void
{
    $paths = [
        'sk' => '/?contact=' . rawurlencode($status) . '#kontakt',
        'en' => '/en/?contact=' . rawurlencode($status) . '#kontakt',
        'de' => '/de/?contact=' . rawurlencode($status) . '#kontakt',
    ];

    header('Location: ' . $paths[$lang], true, 303);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

$lang = (string) ($_POST['lang'] ?? 'sk');
if (!in_array($lang, ['sk', 'en', 'de'], true)) {
    $lang = 'sk';
}

function mima_form_value(string $key, int $maxLength): ?string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        return null;
    }

    $value = trim($value);
    if (strlen($value) > $maxLength) {
        return null;
    }

    return $value;
}

$name = mima_form_value('full_name', 400);
$email = mima_form_value('email', 254);
$phone = mima_form_value('phone', 120);
$message = mima_form_value('message', 20000);

if ($name === null || $email === null || $phone === null || $message === null
    || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    mima_redirect_contact($lang, 'invalid');
}

$subjectByLang = [
    'sk' => 'Nový dopyt z kontaktného formulára',
    'en' => 'New contact form enquiry',
    'de' => 'Neue Anfrage über das Kontaktformular',
];

$body = implode("\n", [
    'Meno / Name: ' . ($name !== '' ? $name : '(neuvedené)'),
    'E-mail: ' . $email,
    'Telefón / Phone: ' . ($phone !== '' ? $phone : '(neuvedené)'),
    '',
    'Požiadavka / Request:',
    $message !== '' ? $message : '(neuvedené)',
]);

$headers = [
    'From: MIMA CT <info@mimact.sk>',
    'Reply-To: ' . $email,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
];
$subject = '=?UTF-8?B?' . base64_encode($subjectByLang[$lang]) . '?=';

if (!mail('info@mimact.sk', $subject, $body, implode("\r\n", $headers))) {
    error_log('MIMA contact form: mail() failed.');
    mima_redirect_contact($lang, 'error');
}

mima_redirect_contact($lang, 'sent');
