<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Contracts;

use InteractionDesignFoundation\BatchMailer\SentMessage;
use InteractionDesignFoundation\BatchMailer\PendingBatchMail;

interface BatchMailer
{
    /** @param array<int, \InteractionDesignFoundation\BatchMailer\ValueObjects\Address|string> $users */
    public function to(array $users): PendingBatchMail;

    /** @param array<int, \InteractionDesignFoundation\BatchMailer\ValueObjects\Address|string> $users */
    public function bcc(array $users): PendingBatchMail;

    /** @param array<string, mixed> $data */
    public function send(BatchMailable|string|array $view, array $data = [], \Closure $callback = null): ?SentMessage;
}