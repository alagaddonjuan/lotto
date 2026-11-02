<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If the
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store the
    | meta information required to keep track of your queued jobs.
    |
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Supervisor Segregated Pipelines
    |--------------------------------------------------------------------------
    |
    | 1. ledger-serial: Single-threaded worker (maxProcesses = 1) ensuring HMAC-SHA256
    |    cryptographic audit hash chains never fork under high concurrent stakes.
    | 2. webhooks-payment: High-priority workers processing DVA deposits with lockForUpdate.
    | 3. whatsapp-outbound: Rate-limited workers sending Meta Graph API messages.
    |
    */

    'environments' => [
        'production' => [
            'supervisor-ledger-serial' => [
                'connection' => 'redis',
                'queue' => ['ledger-serial'],
                'balance' => 'simple',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 1,
                'minProcesses' => 1,
                'tries' => 3,
                'timeout' => 60,
            ],
            'supervisor-webhooks-payment' => [
                'connection' => 'redis',
                'queue' => ['webhooks-payment'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 10,
                'minProcesses' => 2,
                'tries' => 5,
                'timeout' => 30,
            ],
            'supervisor-whatsapp-outbound' => [
                'connection' => 'redis',
                'queue' => ['whatsapp-outbound'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 5,
                'minProcesses' => 1,
                'tries' => 3,
                'timeout' => 30,
            ],
        ],

        'local' => [
            'supervisor-ledger-serial' => [
                'connection' => env('QUEUE_CONNECTION', 'database'),
                'queue' => ['ledger-serial'],
                'balance' => 'simple',
                'maxProcesses' => 1,
                'minProcesses' => 1,
                'tries' => 3,
            ],
            'supervisor-webhooks-payment' => [
                'connection' => env('QUEUE_CONNECTION', 'database'),
                'queue' => ['webhooks-payment'],
                'balance' => 'auto',
                'maxProcesses' => 3,
                'minProcesses' => 1,
                'tries' => 3,
            ],
            'supervisor-whatsapp-outbound' => [
                'connection' => env('QUEUE_CONNECTION', 'database'),
                'queue' => ['whatsapp-outbound'],
                'balance' => 'auto',
                'maxProcesses' => 2,
                'minProcesses' => 1,
                'tries' => 3,
            ],
        ],
    ],
];

