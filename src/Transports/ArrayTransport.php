<?php

namespace Junges\BatchMailer\Transports;

use Illuminate\Support\Collection;
use Junges\BatchMailer\BatchMailerMessage;
use Junges\BatchMailer\Contracts\BatchTransport;
use Junges\BatchMailer\SentMessage;

final class ArrayTransport implements BatchTransport
{
    /** @param \Illuminate\Support\Collection<int, ?SentMessage> $messages */
    private Collection $messages;

    public function __construct()
    {
        $this->messages = new Collection();
    }

    public function send(BatchMailerMessage $batchMailerMessage): ?SentMessage
    {
        $this->messages->push($sentMessage = new SentMessage($batchMailerMessage));

        return $sentMessage;
    }

    public function getNameSymbol(): string
    {
        return 'array';
    }

    /** @return \Illuminate\Support\Collection<int, ?SentMessage> */
    public function messages(): Collection
    {
        return $this->messages;
    }

    public function flush(): Collection
    {
        return $this->messages = new Collection();
    }

    public function __toString(): string
    {
        return $this->getNameSymbol();
    }
}