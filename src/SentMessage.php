<?php declare(strict_types=1);

namespace Junges\BatchMailer;

final class SentMessage
{
    public function __construct(
        private readonly BatchMailerMessage $original,
        private readonly ?string $messageId = null
    ) {}

    public function originalMessage(): BatchMailerMessage
    {
        return $this->original;
    }

    public function messageId(): ?string
    {
        return $this->messageId;
    }
}