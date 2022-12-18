<?php

namespace InteractionDesignFoundation\BatchMailer\Mailables;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;

class Headers
{
    use Conditionable;

    public function __construct(
        public ?string $messageId = null,
        public array $references = [],
        public array $text = []
    ) {}

    /** Set the message ID. */
    public function messageId(string $messageId): self
    {
        $this->messageId = $messageId;
        return $this;
    }

    /** Set the message IDs referenced by this message. */
    public function references(array $references): self
    {
        $this->references = $references;
        return $this;
    }

    /** Set the headers for this message. */
    public function text(array $text): self
    {
        $this->text = array_merge($this->text, $text);
        return $this;
    }

    /** Get the references header as string. */
    public function referencesString(): string
    {
        return collect($this->references)->map(function ($messageId) {
            return Str::finish(Str::start($messageId, '<'), '>');
        })->implode(' ');
    }
}