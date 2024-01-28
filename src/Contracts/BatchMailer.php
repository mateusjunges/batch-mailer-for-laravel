<?php declare(strict_types=1);

namespace Junges\BatchMailer\Contracts;

use Junges\BatchMailer\PendingBatchMail;
use Junges\BatchMailer\SentMessage;

interface BatchMailer
{
    /** @param array<int, \Junges\BatchMailer\Mailables\Address|string> $users */
    public function to(array $users): PendingBatchMail;

    /** @param array<int, \Junges\BatchMailer\Mailables\Address|string> $users */
    public function bcc(array $users): PendingBatchMail;

    /** @param array<string, mixed> $data */
    public function send(BatchMailable|string|array $view, array $data = [], \Closure $callback = null): ?SentMessage;
}