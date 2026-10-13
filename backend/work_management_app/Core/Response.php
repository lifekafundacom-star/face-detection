<?php

namespace App\Core;

class Response
{
    private static function basePath(): string
    {
        $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $mountPath = '/backend/work-management';
        $mountPosition = strpos($uriPath, $mountPath);
        if ($mountPosition !== false) {
            return substr($uriPath, 0, $mountPosition + strlen($mountPath));
        }

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

        return ($basePath && $basePath !== '.') ? $basePath : '';
    }

    public static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_PRETTY_PRINT);
    }

    public static function redirect(string $path): void
    {
        $basePath = self::basePath();
        $target = $path;

        if ($basePath && strpos($path, $basePath) !== 0) {
            $target = $basePath . ($path === '/' ? '/' : $path);
        }

        header('Location: ' . $target);
        exit;
    }
}
