<?php

return [
    'autoload' => false,
    'hooks' => [
        'user_sidenav_after' => [
            'invite',
        ],
        'user_register_successed' => [
            'invite',
        ],
        'admin_login_init' => [
            'loginbg',
        ],
        'config_init' => [
            'nkeditor',
        ],
    ],
    'route' => [

    ],
];
