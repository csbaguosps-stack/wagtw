<?php
/**
 * WAGTW - WhatsApp Multi-Device Gateway & AI Platform
 * 
 * Coding by cs.baguosps@gmail.com
 * Copyright (c) 2024-2026. All rights reserved.
 */

// ─── Polyfills for PHP < 8.0 Compatibility (PHP 7.4 Hosting) ───────────────────
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle === '' || mb_strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return (string)$needle === '' || substr($haystack, -strlen($needle)) === (string)$needle;
    }
}

require_once 'core/App.php';
require_once 'core/Controller.php';
require_once 'core/Database.php';
require_once 'config/config.php';
