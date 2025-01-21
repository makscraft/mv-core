<?php
/**
 * 
 */
return [    
    'production' => [
        'connection' => [
            'domain' => 'https://linux.mv-framework.com',
            'type' => 'ftp',
            'host' => '159.253.18.133',
            'port' => 21,
            'login' => 'mv-linux-deploy',
            'password' => 'nu1bhPVB664kWNbB'
        ],
        //'skip' => ['/index.php', 'core/db', '/core/auth.class.php'],
        'migrations' => true,
        'backup' => true,
        'backups_limit' => 5,
        'check_build' => true,
        'env_file' => '.env.production'
    ],

    'stage' => [

    ]
];