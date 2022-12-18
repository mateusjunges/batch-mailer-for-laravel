<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Contracts;

use InteractionDesignFoundation\BatchMailer\PendingBatchMail;
use InteractionDesignFoundation\BatchMailer\SentMessage;

interface BatchMailer
{
    /** @param array<int, \InteractionDesignFoundation\BatchMailer\Mailables\Address|string> $users */
    public function to(array $users): PendingBatchMail;

    /** @param array<int, \InteractionDesignFoundation\BatchMailer\Mailables\Address|string> $users */
    public function bcc(array $users): PendingBatchMail;

    /** @param array<string, mixed> $data */
    public function send(BatchMailable|string|array $view, array $data = [], \Closure $callback = null): ?SentMessage;
}