<?php

return [
    'groups' => [
        'auth' => [
            'login',
            'logout',
            'register',
            'password.*',
            'verification.*',
        ],
        'app' => [
            'dashboard',
            'receive-job.*',
            'execute-test.*',
            'report.*',
            'certificates.*',
            'performance.*',
            'profile.*',
            'master-data.*',
            'logout',
        ],
    ],
];
