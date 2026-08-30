<?php

const MIMA_LANG_COOKIE = 'mima_lang';
const MIMA_LANG_DEFAULT = 'sk';
const MIMA_LANGS = ['sk', 'en', 'de'];

function mima_client_ip(): string
{
    $candidates = [];

    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }
        foreach (explode(',', (string) $_SERVER[$key]) as $part) {
            $candidates[] = trim($part);
        }
    }

    foreach ($candidates as $ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }
    }

    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '';
}

function mima_is_bot(): bool
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return (bool) preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|linkedinbot|embedly|quora|pinterest|whatsapp|telegram/i', $ua);
}

function mima_country_to_lang(string $country): string
{
    $country = strtoupper($country);

    if (in_array($country, ['SK', 'CZ'], true)) {
        return 'sk';
    }
    if (in_array($country, ['DE', 'AT', 'CH', 'LI'], true)) {
        return 'de';
    }
    if ($country !== '' && $country !== 'XX' && $country !== 'T1') {
        return 'en';
    }

    return '';
}

function mima_lang_from_accept_language(): string
{
    $header = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    if ($header === '') {
        return '';
    }

    if (preg_match('/\b(sk|cs)([-_]|$|,|;)/', $header)) {
        return 'sk';
    }
    if (preg_match('/\bde([-_]|$|,|;)/', $header)) {
        return 'de';
    }
    if (preg_match('/\ben([-_]|$|,|;)/', $header)) {
        return 'en';
    }

    return '';
}

function mima_http_get(string $url): string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'MIMA-lang-detect/1.0',
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        return is_string($body) ? $body : '';
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 2,
            'ignore_errors' => true,
            'header' => "User-Agent: MIMA-lang-detect/1.0\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    return is_string($body) ? $body : '';
}

function mima_country_from_headers(): string
{
    foreach (['HTTP_CF_IPCOUNTRY', 'HTTP_GEOIP_COUNTRY_CODE', 'GEOIP_COUNTRY_CODE', 'HTTP_X_COUNTRY_CODE'] as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }
        $code = strtoupper(preg_replace('/[^A-Z]/', '', (string) $_SERVER[$key]));
        if (strlen($code) === 2) {
            return $code;
        }
    }

    return '';
}

function mima_country_from_ip(string $ip): string
{
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return '';
    }

    $body = mima_http_get('https://ipwho.is/' . rawurlencode($ip) . '?fields=success,country_code');
    if ($body === '') {
        return '';
    }

    $data = json_decode($body, true);
    if (!is_array($data) || empty($data['success']) || empty($data['country_code'])) {
        return '';
    }

    return strtoupper((string) $data['country_code']);
}

function mima_cookie_lang(): string
{
    $lang = strtolower((string) ($_COOKIE[MIMA_LANG_COOKIE] ?? ''));
    return in_array($lang, MIMA_LANGS, true) ? $lang : '';
}

function mima_set_lang_cookie(string $lang): void
{
    setcookie(MIMA_LANG_COOKIE, $lang, [
        'expires' => time() + 60 * 60 * 24 * 365,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[MIMA_LANG_COOKIE] = $lang;
}

function mima_detect_lang(): string
{
    $fromCookie = mima_cookie_lang();
    if ($fromCookie !== '') {
        return $fromCookie;
    }

    if (mima_is_bot()) {
        return MIMA_LANG_DEFAULT;
    }

    $fromHeaders = mima_country_to_lang(mima_country_from_headers());
    if ($fromHeaders !== '') {
        return $fromHeaders;
    }

    $fromIp = mima_country_to_lang(mima_country_from_ip(mima_client_ip()));
    if ($fromIp !== '') {
        return $fromIp;
    }

    $fromBrowser = mima_lang_from_accept_language();
    if ($fromBrowser !== '') {
        return $fromBrowser;
    }

    return MIMA_LANG_DEFAULT;
}
