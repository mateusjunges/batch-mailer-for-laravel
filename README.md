# Laravel batch mailer 

A batch mailer driver for laravel with built-in support for failover configuration.

![](art/readme-banner.png)

## Installation
You can require this package using composer:

```text
composer require interaction-design-foundation/laravel-batch-mailer
```

Then, you can publish the configuration file using the command below:

```text
php artisan vendor:publish --tag=batch-mailer-config
```

## Driver/Transports

### Mailgun transport
To use the Mailgun transport, set the `default` option in your `config/batch-mailer.php` file to `mailgun`. 

After configuring your application's default batch mailer, verify that your `config/services.php` configuration contains the following options:

```php
'mailgun' => [
    'domain' => env('MAILGUN_DOMAIN'),
    'secret' => env('MAILGUN_SECRET'),
],
```

### Postmark Transport
To use the Postmark transport, set the `default` option in your `config/batch-mailer.php` file to `postmark`

After configuring your application's default batch mailer, verify that your `config/services.php` configuration contains the following options:

```php
'postmark' => [
    'token' => env('POSTMARK_TOKEN'),
],
```

## Failover configuration
Sometimes, an external service you have configured to send your application's email may be down. In these cases, it can be useful to define one or more backup mail delivery configurations that will be used in case your primary delivery is down.
To accomplish this, you should use the `failover` mailer, available by default with this package, which uses the `failover` transport. The configuration array for your application's `failover` mailer should contain an array of `mailers` that references the order in which mail drivers should be chosen for delivery:

```php
'mailers' => [
    'failover' => [
        'transport' => 'failover',
        'mailers' => [
            'postmark',
            'mailgun',
        ],
    ],
    // ...
],
```
Once your failover mailer has been defined, you should set this mailer as the default batch mailer driver used by your application by specifying its name as the name of the `default` configuration key within your application's `batch mail` configuration:

```php
'default' => env('BATCH_MAILER', 'failover'),
```

## Generating Mailables
When building Laravel applications, each type of batch emails sent by your application is represented by a "`Mailable`" class. The classes are stored in the `app/Mail/BatchMail` directory. Don't worry if you don't see this directory in your application, since it will be generated for you when you create your first mailable using the `make:batch-mail` command.

```bash
php artisan make:batch-mail TestBatchMail
```

## Writing Mailables
Once you have generated a mailable class, open it up, so we can explore its contents. Mailable class configuration is done in several methods, including the `build` and `attachments` methods.