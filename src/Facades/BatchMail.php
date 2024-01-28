<?php declare(strict_types=1);

namespace Junges\BatchMailer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Junges\BatchMailer\PendingBatchMail cc(array $users)
 * @method static \Junges\BatchMailer\PendingBatchMail bcc(array $users)
 * @method static \Junges\BatchMailer\PendingBatchMail to(array $users)
 * @method static \Junges\BatchMailer\SentMessage|null raw(string $text, \Closure $callback)
 * @method static \Junges\BatchMailer\SentMessage|null plain(string $text, \Closure $callback)
 * @method static \Junges\BatchMailer\SentMessage|null html(string $html, \Closure $callback)
 * @method static \Junges\BatchMailer\BatchMailManager extend(string $driver, \Closure $callback)
 *
 * @see \Junges\BatchMailer\BatchMailManager
 * @see \Junges\BatchMailer\BatchMailer
 */
final class BatchMail extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return 'batch-mailer.manager';
    }
}