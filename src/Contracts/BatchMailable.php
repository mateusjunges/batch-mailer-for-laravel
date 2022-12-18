<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Contracts;

use InteractionDesignFoundation\BatchMailer\Mailables\Content;
use InteractionDesignFoundation\BatchMailer\Mailables\Envelope;
use InteractionDesignFoundation\BatchMailer\ValueObjects\Address;
use InteractionDesignFoundation\BatchMailer\SentMessage;

interface BatchMailable
{
    /** Send the message using the given batch mailer.*/
    public function send(BatchMailer $batchMailer): ?SentMessage;

   public function envelope(): Envelope;

   /** Defines the content of the message. */
   public function content(): Content;

    /** Set the name of the mailer that should be used to send the message. */
    public function mailer(string $mailer): self;
}