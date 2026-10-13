<?php

namespace App\Core;

class Request
{
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        $mountPath = '/backend/work-management';
        $mountPosition = strpos($path, $mountPath);

        if ($mountPosition !== false) {
            $path = substr($path, $mountPosition + strlen($mountPath));
        } elseif ($scriptName && strpos($path, $scriptName) === 0) {
            $path = substr($path, strlen($scriptName));
        } elseif ($basePath && $basePath !== '.' && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }

        return rtrim($path, '/') ?: '/';
    }

    public function query(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public function input(string $key, $default = null)
    {
        $data = $this->all();
        return $data[$key] ?? $default;
    }

    public function all(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (strpos($contentType, 'application/json') !== false) {
            $decoded = json_decode(file_get_contents('php://input') ?: '[]', true);
            return is_array($decoded) ? $decoded : [];
        }

        return array_merge($_GET, $_POST);
    }
}
