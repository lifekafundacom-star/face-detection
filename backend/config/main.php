<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

$isLocalHost = (DIRECTORY_SEPARATOR === '\\') || (isset($_SERVER['HTTP_HOST']) && preg_match('/^(localhost|127\.0\.0\.1|10\.\d+\.\d+\.\d+|192\.168\.\d+\.\d+|172\.(1[6-9]|2\d|3[0-1])\.\d+\.\d+)(:\d+)?$/', $_SERVER['HTTP_HOST']));
$backendBaseUrl = '/backend';
if ($isLocalHost) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $backendBaseUrl = preg_replace('#/backend/web/index\.php$#', '/backend', $scriptName);
    $backendBaseUrl = rtrim($backendBaseUrl, '/');
}

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'homeUrl' => Yii::getAlias('@backendUrl'),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'on beforeAction' => function ($event) {
        $action = $event->action;
        $controllerId = $action->controller->id;

        // Allow guest to access login/error/etc.
        if (Yii::$app->user->isGuest) {
            return;
        }

        $user = Yii::$app->user->identity;
        if ($user && $user->role !== 'admin') {
            $routePermissionMap = [
                'language' => 'website.language',
                'category' => 'website.category',
                'subcategory' => 'website.subcategory',
                'template' => 'cards.template',
                'product' => 'cards.product',
                'template-mapping' => 'cards.template_mapping',
                'google-analytics' => 'analytics.google',
                'analytics' => 'analytics.internal',
                'search-analytics' => 'analytics.search',
                'work-definition' => 'work.definitions',
                'contact' => 'backend.contact',
            ];

            if (isset($routePermissionMap[$controllerId])) {
                $permission = $routePermissionMap[$controllerId];
                if (!$user->canAccess($permission)) {
                    throw new \yii\web\ForbiddenHttpException('You are not allowed to perform this action.');
                }
            }
        }
    },
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-backend',
            'baseUrl' => $backendBaseUrl,
        ],
        'user' => [
            'identityClass' => 'common\models\EmployeeUser',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
        ],
        'dbEmployee' => [
            'class' => \yii\db\Connection::class,
            'dsn' => $isLocalHost ? 'mysql:host=127.0.0.1;dbname=face_detection_ice_emp' : 'mysql:host=localhost;dbname=face_detection_ice_emp',
            'username' => $isLocalHost ? 'root' : 'lifekafunda_pheditor',
            'password' => $isLocalHost ? '' : 'temp@111@111',
            'charset' => 'utf8mb4',
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'advanced-backend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
       
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
       
    ],
    'params' => $params,
];
