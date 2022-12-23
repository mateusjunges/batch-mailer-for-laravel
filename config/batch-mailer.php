<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send any email
    | messages sent by your application. Alternative mailers may be setup
    | and used as needed; however, this mailer will be used by default.
    |
    */
    'default' => env('BATCH_MAILER', 'failover'),

    /*
     | ---------------------------------------------------------------------
     | Attachment configuration
     | ---------------------------------------------------------------------
     |
     | This option controls the default attachment configuration that is used to attach files from Storage.
     | The default path used is "batch-mailer-temp", on your local disk. Please note that this directory will
     | be cleared every time you send a batch mail, so make sure to change it if you are already using this path
     | for something else in your application.
     */
    'attachments_temp_path' => env('BATCH_MAILER_ATTACHMENTS_TEMP_PATH', 'batch-mailer-temp'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you, and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers to be used while
    | sending an e-mail. You will specify which one you are using for your
    | mailers below. You are free to add additional mailers as required.
    |
    | Supported: "mailgun", "postmark", "array", "failover"
    |
    */
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
