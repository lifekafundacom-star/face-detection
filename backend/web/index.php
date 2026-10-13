<?php

$isLocal = (DIRECTORY_SEPARATOR === '\\') || (isset($_SERVER['HTTP_HOST']) && preg_match('/^(localhost|127\.0\.0\.1|10\.\d+\.\d+\.\d+|192\.168\.\d+\.\d+|172\.(1[6-9]|2\d|3[0-1])\.\d+\.\d+)(:\d+)?$/', $_SERVER['HTTP_HOST']));
defined('YII_DEBUG') or define('YII_DEBUG', $isLocal);
defined('YII_ENV') or define('YII_ENV', $isLocal ? 'dev' : 'prod');

require __DIR__ . '/../../vendor_bk/autoload.php';
require __DIR__ . '/../../vendor_bk/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../config/main.php',
    require __DIR__ . '/../config/main-local.php'
);

(new yii\web\Application($config))->run();
