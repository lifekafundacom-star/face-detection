<?php

return [
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'mysql:host=localhost;dbname=lifekafunda_pheditor',
            'username' => 'lifekafunda_pheditor',
            'password' => 'temp@111@111',
            'charset' => 'utf8',
        ],
        'dbEmployee' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'mysql:host=localhost;dbname=face_detection_ice_emp',
            'username' => 'lifekafunda_pheditor',
            'password' => 'temp@111@111',
            'charset' => 'utf8mb4',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
        ],
    ],
];
