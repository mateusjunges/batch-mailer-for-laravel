<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Tests;

use InteractionDesignFoundation\BatchMailer\Contracts\BatchMailable;
use InteractionDesignFoundation\BatchMailer\Mailable;
use InteractionDesignFoundation\BatchMailer\Mailables\Attachment;
use InteractionDesignFoundation\BatchMailer\Mailables\Content;
use InteractionDesignFoundation\BatchMailer\Mailables\Envelope;
use InteractionDesignFoundation\BatchMailer\ValueObjects\Address;

class TestMailable extends Mailable {
    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromPath(__DIR__."/test-file-attachment.txt")->as('Test File Attachment')
        ];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('from@example.com', 'From'),
            replyTo: [
                [
                    'email' => 'mateus@example.com',
                    'name' => 'Mateus'
                ]
            ]
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "<html>Test</html>",
        );
    }
}