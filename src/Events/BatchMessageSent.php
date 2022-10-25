<?php

namespace InteractionDesignFoundation\BatchMailer\Events;

use InteractionDesignFoundation\BatchMailer\SentMessage;

final class BatchMessageSent
{
    public function __construct(
        public readonly SentMessage $sentMessage,
        public readonly array $data
    ) {}
}