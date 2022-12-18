<?php

namespace InteractionDesignFoundation\BatchMailer\Mailables;

use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Conditionable;
use InteractionDesignFoundation\BatchMailer\ValueObjects\Address;

class Envelope
{
    use Conditionable;

    /** The address sending the message. */
    public Address $from;

    /**
     * The recipients of the message.
     *
     * @var array<int, Address> $to
     */
    public array $to = [];

    /**
     * The recipients receiving a copy of the message.
     *
     * @var array<int, Address> $cc
     */
    public array $cc = [];

    /**
     * The recipients receiving a blind copy of the message.
     *
     * @var array<int, Address> $bcc
     */
    public array $bcc = [];

    /**
     * The recipients that should be replied to.
     *
     * @var array<int, Address> $replyTo
     */
    public array $replyTo = [];

    /** The subject of the message. */
    public ?string $subject = null;

    /** The message's tags. */
    public array $tags = [];

    /** The message's meta data. */
    public array $metadata = [];

    /**
     * Create a new message envelope instance.
     *
     * @named-arguments-supported
     */
    public function __construct(Address|string $from = null, array $to = [], array $cc = [], array $bcc = [], array $replyTo = [], string $subject = null, array $tags = [], array $metadata = [])
    {
        $this->from = $from;
        $this->to = $this->normalizeAddresses($to);
        $this->cc = $this->normalizeAddresses($cc);
        $this->bcc = $this->normalizeAddresses($bcc);
        $this->replyTo = $this->normalizeAddresses($replyTo);
        $this->subject = $subject;
        $this->tags = $tags;
        $this->metadata = $metadata;
    }

    /** Normalize the given array of addresses. */
    protected function normalizeAddresses(Address|string|array $addresses, string $name = null): array
    {
        $addresses = Arr::wrap($addresses);

        if (Arr::isAssoc($addresses) && array_key_exists('email', $addresses)) {
            return [new Address($addresses['email'], $addresses['name'] ?? $name ?? $addresses['email'])];
        }

        return collect($addresses)->map(function (Address|array|string $address) use ($name): Address {
            if ($address instanceof Address) {
                return $address;
            }

            if (is_string($address)) {
                return new Address($address, $name ?? $address);
            }

            return new Address($address['email'], $address['name'] ?? $name ?? $address['email']);
        })->all();
    }

    /** Specify who the message will be "from". */
    public function from(Address|string $address, string $name = null): self
    {
        $this->from = is_string($address) ? new Address($address, $name) : $address;

        return $this;
    }

    /** Add a "to" recipient to the message envelope. */
    public function to(Address|array|string $address, string $name = null): self
    {
        $this->to = array_merge($this->to, $this->normalizeAddresses(
            is_string($name) ? [new Address($address, $name)] : Arr::wrap($address),
        ));

        return $this;
    }

    /** Add a "cc" recipient to the message envelope. */
    public function cc(Address|array|string $address, $name = null): self
    {
        $this->cc = array_merge($this->cc, $this->normalizeAddresses(
            is_string($name) ? [new Address($address, $name)] : Arr::wrap($address),
        ));

        return $this;
    }

    /** Add a "bcc" recipient to the message envelope. */
    public function bcc(Address|array|string $address, $name = null): self
    {
        $this->bcc = array_merge($this->bcc, $this->normalizeAddresses(
            is_string($name) ? [new Address($address, $name)] : Arr::wrap($address),
        ));

        return $this;
    }

    /** Add a "reply to" recipient to the message envelope. */
    public function replyTo(Address|array|string $address, $name = null): self
    {
        $this->replyTo = array_merge($this->replyTo, $this->normalizeAddresses(
            is_string($name) ? [new Address($address, $name)] : Arr::wrap($address),
        ));

        return $this;
    }

    /** Set the subject of the message. */
    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /** Add "tags" to the message. */
    public function tags(array $tags): self
    {
        $this->tags = array_merge($this->tags, $tags);

        return $this;
    }

    /** Add a "tag" to the message. */
    public function tag(string $tag): self
    {
        $this->tags[] = $tag;

        return $this;
    }

    /** Add metadata to the message. */
    public function metadata(string $key, string|int $value): self
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    /** Determine if the message is from the given address. */
    public function isFrom(string $address, string $name = null): bool
    {
        if (is_null($name)) {
            return $this->from->email === $address;
        }

        return $this->from->email === $address &&
            $this->from->fullName === $name;
    }
}