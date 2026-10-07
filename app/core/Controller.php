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
}
