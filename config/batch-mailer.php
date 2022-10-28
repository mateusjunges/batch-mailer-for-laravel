<?php

return [
    'default' => env('BATCH_MAILER', 'failover'),

    'mailers' => [
        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'mailgun',
                'postmark'
            ]
        ],
        'mailgun' => [
            'transport' => 'mailgun',
            'api_token' => '',
            'domain' => '',
        ],
        'postmark' => [
            'transport' => 'postmark'
        ],
    ]
];
