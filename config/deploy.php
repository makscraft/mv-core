<?php
/**
 * 
 */
return [    
    'production' => [
        'connection' => [
            'type' => 'ftp',
            'host' => '',
            'port' => '',
            'login' => '',
            'password' => ''
        ],
        'upload' => '*',
        'skip' => [],
        'migrations' => true,
        'backup' => true,
        'check_build' => true,
        'env_file' => '.env.production'
    ],

    'stage' => [

    ]
];