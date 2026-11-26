<?php
/**
 * WAGTW - WhatsApp Multi-Device Gateway & AI Platform
 * 
 * Coding by cs.baguosps@gmail.com
 * Copyright (c) 2024-2026. All rights reserved.
 */

class Controller {
    public function view($view, $data = [])
    {
        // Prevent aggressive browser/proxy caching for dynamic views and AJAX fragments
        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }

        // Auto-inject global app branding (logo & app name)
        if (!isset($data['app_logo']) || !isset($data['app_name'])) {
            try {
                $db = new Database;
                $db->query("SELECT `key`, `value` FROM settings WHERE `key` IN ('app_logo', 'app_name')");
                $settings = $db->resultSet();
                foreach ($settings as $s) {
                    if (!isset($data[$s['key']])) {
                        $data[$s['key']] = $s['value'];
                    }
                }
            } catch (Exception $e) {
                // Ignore if DB not reachable
            }
        }

        $viewPath = file_exists(__DIR__ . '/../views/' . $view . '.php') ? __DIR__ . '/../views/' . $view . '.php' : '../app/views/' . $view . '.php';
        require_once $viewPath;
    }


    public function model($model)
    {
        $modelPath = file_exists(__DIR__ . '/../models/' . $model . '.php') ? __DIR__ . '/../models/' . $model . '.php' : '../app/models/' . $model . '.php';
        require_once $modelPath;
        return new $model;
    }

    /**
     * Helper to get user IDs visible to the current logged in user based on Role.
     */
    protected function getVisibleUserIds()
    {
        if (!isset($_SESSION['user_id'])) return [];

        $userId = $_SESSION['user_id'];
        $role   = $_SESSION['role'] ?? 'user';

        if ($role === 'super_admin') {
            // Can see everyone
            $db = new Database();
            $db->query("SELECT id FROM users");
            $rows = $db->resultSet();
            $ids = [];
            foreach ($rows as $r) $ids[] = $r['id'];
            return empty($ids) ? [0] : $ids;
        } else if ($role === 'admin') {
            // Can see self + users created by this admin
            $db = new Database();
            $db->query("SELECT id FROM users WHERE parent_id = :parent_id");
            $db->bind('parent_id', $userId);
            $rows = $db->resultSet();
            $ids = [$userId];
            foreach ($rows as $r) $ids[] = $r['id'];
            return empty($ids) ? [0] : $ids;
        }

        // Default 'user' only sees themselves
        return [$userId];
    }

    /**
     * Helper to get user IDs as a comma separated string for SQL IN clause.
     */
    protected function getVisibleUserIdsString()
    {
        $ids = $this->getVisibleUserIds();
        return implode(',', array_map('intval', $ids));
    }

    /**
     * Helper cURL ke WAGTW Node.js Engine
     */
    protected function callNode($method, $path, $body = null)
    {
        $nodeUrl  = property_exists($this, 'nodeUrl') && !empty($this->nodeUrl) 
                    ? $this->nodeUrl 
                    : (defined('WA_ENGINE_URL') ? WA_ENGINE_URL : 'http://127.0.0.1:3001');
        $apiToken = property_exists($this, 'apiToken') && !empty($this->apiToken) 
                    ? $this->apiToken 
                    : (defined('WA_API_TOKEN') ? WA_API_TOKEN : 'wagtw_secret_token_2024');

        $urls = [$nodeUrl . $path];
        if (strpos($nodeUrl, '127.0.0.1:3001') === false) {
            $urls[] = 'http://127.0.0.1:3001' . $path;
        }

        foreach ($urls as $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'X-Api-Token: ' . $apiToken,
                ],
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);

            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }

            $response = curl_exec($ch);
            $err      = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response !== false && !$err && $httpCode < 500) {
                return json_decode($response, true);
            }
        }

        return null;
    }
}
