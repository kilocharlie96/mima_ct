<?php

require __DIR__ . '/lang-detect.php';

$lang = mima_detect_lang();

if (mima_cookie_lang() === '') {
    mima_set_lang_cookie($lang);
}

if ($lang === 'en') {
    header('Location: en/', true, 302);
    exit;
}

if ($lang === 'de') {
    header('Location: de/', true, 302);
    exit;
}

readfile(__DIR__ . '/index.html');
