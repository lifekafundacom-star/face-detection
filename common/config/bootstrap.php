<?php
Yii::setAlias('@common', dirname(__DIR__));
Yii::setAlias('@frontend', dirname(dirname(__DIR__)) . '/frontend');
Yii::setAlias('@backend', dirname(dirname(__DIR__)) . '/backend');
Yii::setAlias('@console', dirname(dirname(__DIR__)) . '/console');

Yii::setAlias('@storage', dirname(dirname(__DIR__)) . '/storage');
Yii::setAlias('@api', dirname(dirname(__DIR__)) . '/api');

$WebProtocol = (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$WebHost = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
$WebHostName = preg_replace('/:\d+$/', '', $WebHost);
$IsLocalNetwork = preg_match('/^(localhost|127\.0\.0\.1|10\.\d+\.\d+\.\d+|192\.168\.\d+\.\d+|172\.(1[6-9]|2\d|3[0-1])\.\d+\.\d+)$/', $WebHostName);

$WebSubDir = "";
if ($IsLocalNetwork && isset($_SERVER['SCRIPT_NAME'])) {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    if (preg_match('#^/([^/]+)/(backend|frontend|api|storage|web)#', $script, $m)) {
        $WebSubDir = '/' . $m[1];
    } elseif (preg_match('#^/([^/]+)/index\.php#', $script, $m) && !in_array($m[1], ['backend', 'frontend', 'api', 'storage', 'web'], true)) {
        $WebSubDir = '/' . $m[1];
    }
}
Yii::setAlias('@WebSubDir', $WebSubDir);

Yii::setAlias('@frontendUrl', $WebProtocol . "://" . $WebHost . $WebSubDir);
Yii::setAlias('@backendUrl', $WebProtocol . "://" . $WebHost . $WebSubDir . "/backend");
Yii::setAlias('@storageUrl', $WebProtocol . "://" . $WebHost . $WebSubDir . "/storage");
Yii::setAlias('@apiUrl', $WebProtocol . "://" . $WebHost . $WebSubDir . "/api");
