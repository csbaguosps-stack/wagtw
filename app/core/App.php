<?php
/**
 * WAGTW - WhatsApp Multi-Device Gateway & AI Platform
 * 
 * Coding by cs.baguosps@gmail.com
 * Copyright (c) 2024-2026. All rights reserved.
 */

class App {
    protected $controller = 'Auth';
    protected $method = 'index';
    protected $params = [];

    public function __construct()
    {
        $url = $this->parseURL();

        // Check if controller exists (case-insensitive for Linux hosting support)
        if(isset($url[0])) {
            $ctrlSearch = strtolower($url[0]);
            $ctrlDir = '../app/controllers/';
            
            if(file_exists($ctrlDir . ucfirst($url[0]) . '.php')) {
                $this->controller = ucfirst($url[0]);
                unset($url[0]);
            } else {
                $controllerFiles = glob($ctrlDir . '*.php') ?: [];
                foreach ($controllerFiles as $file) {
                    $base = basename($file, '.php');
                    if (strtolower($base) === $ctrlSearch) {
                        $this->controller = $base;
                        unset($url[0]);
                        break;
                    }
                }
            }
        }

        require_once '../app/controllers/' . $this->controller . '.php';
        $this->controller = new $this->controller;

        // Check method
        if(isset($url[1])) {
            if(method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            }
        }

        // Setup params
        if(!empty($url)) {
            $this->params = array_values($url);
        }

        // Execute controller & method + send params
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    public function parseURL()
    {
        if(isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);
            return $url;
        }
        return [];
    }
}
