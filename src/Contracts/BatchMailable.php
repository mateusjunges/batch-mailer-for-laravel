<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Contracts;

use InteractionDesignFoundation\BatchMailer\Mailables\Address;
use InteractionDesignFoundation\BatchMailer\Mailables\Content;
use InteractionDesignFoundation\BatchMailer\Mailables\Envelope;
use InteractionDesignFoundation\BatchMailer\SentMessage;

interface BatchMailable
{
    /** Send the message using the given batch mailer.*/
    public function send(BatchMailer $batchMailer): ?SentMessage;

    /**
     * Set the recipients of the message.
     *
     * @param array<int, Address> $addresses
     */
    public function cc(array $addresses): self;

    /**
     * Set the recipients of the message.
     * @param array<int, Address> $addresses
     */
    public function bcc(array $addresses): self;

    /**
     * Set the recipients of the message.
     *
     * @param array<int, Address> $addresses
     */
    public function to(Address|array $addresses): self;

    /** Set the name of the mailer that should be used to send the message. */
    public function mailer(string $mailer): self;

    public function envelope(): Envelope;

    public function content(): Content;
}