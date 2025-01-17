<?php
/**
 * 
 */
return [    
    'production' => [
        'connection' => [
            'type' => 'ftp',
            'host' => '159.253.18.133',
            'port' => 21,
            'login' => 'mv-linux-deploy',
            'password' => 'nu1bhPVB664kWNbB'
        ],
        'upload' => '*',
        'skip' => [],
        'migrations' => true,
        'backup' => true,
        'backup_limit' => 5,
        'check_build' => true,
        'env_file' => '.env.production'
    ],

    'stage' => [

    ]
];