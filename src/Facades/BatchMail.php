<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \InteractionDesignFoundation\BatchMailer\PendingBatchMail cc(array $users)
 * @method static \InteractionDesignFoundation\BatchMailer\PendingBatchMail bcc(array $users)
 * @method static \InteractionDesignFoundation\BatchMailer\PendingBatchMail to(array $users)
 * @method static \InteractionDesignFoundation\BatchMailer\SentMessage|null raw(string $text, \Closure $callback)
 * @method static \InteractionDesignFoundation\BatchMailer\SentMessage|null plain(string $text, \Closure $callback)
 * @method static \InteractionDesignFoundation\BatchMailer\SentMessage|null html(string $html, \Closure $callback)
 * @method static \InteractionDesignFoundation\BatchMailer\BatchMailManager extend(string $driver, \Closure $callback)
 *
 * @see \InteractionDesignFoundation\BatchMailer\BatchMailManager
 * @see \InteractionDesignFoundation\BatchMailer\BatchMailer
 */
final class BatchMail extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return 'batch-mailer.manager';
    }
}