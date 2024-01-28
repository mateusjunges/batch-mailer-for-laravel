<?php

namespace Junges\BatchMailer\Events;

use Junges\BatchMailer\SentMessage;

final class BatchMessageSent
{
    public function __construct(
        public readonly SentMessage $sentMessage,
        public readonly array $data
    ) {}
}