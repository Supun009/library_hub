<?php
// includes/Router.php

class Router {
    private $routes = [];
    private $baseUrl;

    public function __construct($baseUrl = '') {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function add($method, $path, $callback) {
        $path = $this->normalizePath($path);
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'callback' => $callback
        ];
    }

    public function get($path, $callback) {
        $this->add('GET', $path, $callback);
    }

    public function post($path, $callback) {
        $this->add('POST', $path, $callback);
    }

    public function dispatch($method, $uri) {
        $method = strtoupper($method);
        $uri = $this->parseUri($uri);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $uri, $params)) {
                return call_user_func_array($route['callback'], $params);
            }
        }

        // 404 Handler
        http_response_code(404);
        echo "404 Not Found";
    }

    private function normalizePath($path) {
        return '/' . trim($path, '/');
    }

    private function parseUri($uri) {
        // Remove base URL from URI if present
        if ($this->baseUrl && strpos($uri, $this->baseUrl) === 0) {
            $uri = substr($uri, strlen($this->baseUrl));
        }
        
        // Remove query string
        $uri = parse_url($uri, PHP_URL_PATH);
        return $this->normalizePath($uri);
    }

    private function matchPath($routePath, $uri, &$params) {
        $params = [];
        
        // Convert route params like :id to regex
        $pattern = preg_replace('/\:([a-zA-Z0-9_]+)/', '(?P<\1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[] = $value;
                }
            }
            return true;
        }

        return false;
    }
}
