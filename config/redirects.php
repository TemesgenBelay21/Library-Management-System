<?php

declare(strict_types=1);

return [
    'homes' => [
        'guest' => 'login',
        'member' => 'member',
        'admin' => 'admin',
    ],
    'controller_roles' => [
        'AuthController' => null,
        'AdminController' => 'admin',
        'MemberController' => 'member',
    ],
    'permissions' => [
        'admin' => [
            'dashboard',
            'books',
            'transactions',
            'overdue',
            'members',
        ],
        'member' => [
            'dashboard',
            'catalog',
            'history',
        ],
    ],
];
