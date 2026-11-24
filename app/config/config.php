<?php
/**
 * WAGTW - WhatsApp Multi-Device Gateway & AI Platform
 * 
 * Coding by cs.baguosps@gmail.com
 * Copyright (c) 2024-2026. All rights reserved.
 */

// ─── Dynamic BASEURL & App Root Detection ─────────────────────────────────────
// Works seamlessly on Localhost (XAMPP), Subdomains, Subdirectories, and HTTPS
if (!defined('BASEURL')) {
    if (isset($_SERVER['HTTP_HOST'])) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                   (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
                   (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $protocol = $isHttps ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptDir === '/' || $scriptDir === '.') {
            $scriptDir = '';
        }
        define('BASEURL', rtrim($protocol . $host . $scriptDir, '/'));
    } else {
        define('BASEURL', 'http://localhost:8080/wagtw/public');
    }
}

// App root URL without /public (used for cPanel passenger /server path)
$appRootUrl = preg_replace('#/public$#i', '', BASEURL);
if (!defined('APP_ROOT_URL')) {
    define('APP_ROOT_URL', $appRootUrl);
}

// ─── Read server/.env if available ────────────────────────────────────────────
$envFile = dirname(dirname(__DIR__)) . '/server/.env';
$envConfig = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($envKey, $envVal) = explode('=', $line, 2);
        $envKey = trim($envKey);
        $envVal = trim($envVal);
        $envVal = trim($envVal, '"\''); // Strip outer quotes
        $envConfig[$envKey] = $envVal;
    }
}

// ─── Environment & Database Auto-Detection ────────────────────────────────────
$serverHost = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
$hostOnly = strtolower(preg_replace('/:\d+$/', '', trim($serverHost)));

$isPrivateIp = false;
if (filter_var($hostOnly, FILTER_VALIDATE_IP)) {
    $isPrivateIp = !filter_var($hostOnly, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) ||
                   strpos($hostOnly, '192.168.') === 0 ||
                   strpos($hostOnly, '10.') === 0 ||
                   strpos($hostOnly, '172.') === 0 ||
                   strpos($hostOnly, '127.') === 0;
}

// Cek apakah host adalah domain publik (seperti wagtw.winseeoptik.com)
$isProductionDomain = (strpos($hostOnly, 'winseeoptik.com') !== false);

// Cek apakah lingkungan lokal (XAMPP localhost)
$isLocalHost = ($hostOnly === 'localhost' || 
                $hostOnly === '127.0.0.1' || 
                $hostOnly === '::1' || 
                $isPrivateIp ||
                strpos($hostOnly, '192.168.') === 0 ||
                strpos($hostOnly, '10.') === 0 ||
                (function_exists('str_ends_with') && (str_ends_with($hostOnly, '.local') || str_ends_with($hostOnly, '.test'))));

// Jika domain produksi atau bukan localhost/private IP, maka PASTI Production/Hosting
$isLocal = !$isProductionDomain && $isLocalHost;

if (!defined('IS_LOCAL')) {
    define('IS_LOCAL', $isLocal);
}

if ($isLocal) {
    // Localhost XAMPP Settings
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'wagtw');
} else {
    // Production / Hosting Settings (Diambil dari file server/.env dengan fallback default hosting)
    define('DB_HOST', !empty($envConfig['DB_HOST']) ? $envConfig['DB_HOST'] : 'localhost');
    define('DB_USER', !empty($envConfig['DB_USER']) ? $envConfig['DB_USER'] : 'winseeop_wagtw');
    define('DB_PASS', isset($envConfig['DB_PASS']) && $envConfig['DB_PASS'] !== '' ? $envConfig['DB_PASS'] : 'winseeop_wagtw');
    define('DB_NAME', !empty($envConfig['DB_NAME']) ? $envConfig['DB_NAME'] : 'winseeop_wagtw');
}

// ─── Node.js Baileys Engine API Settings ───────────────────────────────────────
if ($isLocal) {
    define('WA_ENGINE_URL', 'http://127.0.0.1:3001');
} else {
    // Pada hosting cPanel dengan sub-path /server pada Setup Node.js App
    define('WA_ENGINE_URL', APP_ROOT_URL . '/server');
}
define('WA_API_TOKEN', $envConfig['API_SECRET_TOKEN'] ?? 'wagtw_secret_token_2024');

