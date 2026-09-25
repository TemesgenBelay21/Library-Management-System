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
        'BookController' => 'authenticated',
        'AdminController' => 'admin',
        'MemberController' => 'member',
    ],
];
