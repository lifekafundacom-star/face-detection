<?php

$isLocal = (DIRECTORY_SEPARATOR === '\\') || (isset($_SERVER['HTTP_HOST']) && preg_match('/^(localhost|127\.0\.0\.1|10\.\d+\.\d+\.\d+|192\.168\.\d+\.\d+|172\.(1[6-9]|2\d|3[0-1])\.\d+\.\d+)(:\d+)?$/', $_SERVER['HTTP_HOST']));

return [
    'app' => [
        'name' => 'Employee Productivity Suite',
        'base_url' => '/backend/work-management',
        'timezone' => 'Asia/Calcutta',
    ],
    'db' => [
        'host' => $isLocal ? '127.0.0.1' : 'localhost',
        'port' => 3306,
        'database' => 'face_detection_ice_emp',
        'username' => $isLocal ? 'root' : 'lifekafunda_pheditor',
        'password' => $isLocal ? '' : 'temp@111@111',
        'charset' => 'utf8mb4',
    ],
];
