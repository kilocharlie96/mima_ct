<?php

require __DIR__ . '/lang-detect.php';

$lang = mima_detect_lang();
$contactStatus = (string) ($_GET['contact'] ?? '');
$contactQuery = in_array($contactStatus, ['sent', 'error', 'invalid'], true)
    ? '?contact=' . rawurlencode($contactStatus)
    : '';

if (mima_cookie_lang() === '') {
    mima_set_lang_cookie($lang);
}

if ($lang === 'en') {
    header('Location: en/' . $contactQuery . ($contactQuery !== '' ? '#kontakt' : ''), true, 302);
    exit;
}

if ($lang === 'de') {
    header('Location: de/' . $contactQuery . ($contactQuery !== '' ? '#kontakt' : ''), true, 302);
    exit;
}

readfile(__DIR__ . '/index.html');
