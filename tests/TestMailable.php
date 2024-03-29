<?php declare(strict_types=1);

namespace Junges\BatchMailer\Tests;

use Junges\BatchMailer\Mailable;
use Junges\BatchMailer\Mailables\Address;
use Junges\BatchMailer\Mailables\Attachment;
use Junges\BatchMailer\Mailables\Content;
use Junges\BatchMailer\Mailables\Envelope;

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