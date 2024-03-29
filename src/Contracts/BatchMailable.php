<?php declare(strict_types=1);

namespace Junges\BatchMailer\Contracts;

use Junges\BatchMailer\Mailables\Address;
use Junges\BatchMailer\Mailables\Content;
use Junges\BatchMailer\Mailables\Envelope;
use Junges\BatchMailer\SentMessage;

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