<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Tests;

use Illuminate\Contracts\Mail\Attachable;
use InteractionDesignFoundation\BatchMailer\BatchMailerMessage;
use InteractionDesignFoundation\BatchMailer\Contracts\BatchMailable;
use InteractionDesignFoundation\BatchMailer\Mailable;
use InteractionDesignFoundation\BatchMailer\Mailables\Attachment;
use InteractionDesignFoundation\BatchMailer\Mailables\Content;
use InteractionDesignFoundation\BatchMailer\Mailables\Envelope;
use InteractionDesignFoundation\BatchMailer\ValueObjects\Address;

final class BatchMailerMessageTest extends TestCase
{
    private BatchMailerMessage $message;

    protected function setUp(): void
    {
        $this->message = new BatchMailerMessage();
    }

    public function test_from_method(): void
    {
        $this->assertInstanceOf(BatchMailerMessage::class, $message = $this->message->setFrom(new Address('mateus@junges.dev', 'Mateus')));
        $this->assertEquals(new Address('mateus@junges.dev', 'Mateus'), $message->from());
    }

    public function test_to_method(): void
    {
        $this->assertInstanceOf(BatchMailerMessage::class, $message = $this->message->addTo(new Address('mateus@junges.dev', 'Mateus')));
        $this->assertEquals(new Address('mateus@junges.dev', 'Mateus'), $message->recipients()[0]);
    }

    public function test_cc_method()
    {
        $this->assertInstanceOf(BatchMailerMessage::class, $message = $this->message->addCc(new Address('foo@bar.baz', 'Foo')));
        $this->assertEquals(new Address('foo@bar.baz', 'Foo'), $message->cc()[0]);
    }

    public function test_bcc_method()
    {
        $this->assertInstanceOf(BatchMailerMessage::class, $message = $this->message->addBcc(new Address('foo@bar.baz', 'Foo')));
        $this->assertEquals(new Address('foo@bar.baz', 'Foo'), $message->bcc()[0]);
    }

    public function test_reply_to_method()
    {
        $this->assertInstanceOf(BatchMailerMessage::class, $message = $this->message->addReplyTo(new Address('foo@bar.baz', 'Foo')));
        $this->assertEquals(new Address('foo@bar.baz', 'Foo'), $message->replyTo()[0]);
    }

    public function test_subject_method()
    {
        $this->assertInstanceOf(BatchMailerMessage::class, $message = $this->message->setSubject('foo'));
        $this->assertSame('foo', $message->subject());
    }

    public function test_attachment(): void
    {
        $mailable = new ExampleMailable();;
        $mailable->attach(
            new class() implements Attachable
            {
                public function toMailAttachment(): Attachment
                {
                    return Attachment::fromPath(__DIR__."/test-file-attachment.txt")->as('bar')->withMime('text/plain');
                }
            },
        );
        $mailable->attach(Attachment::fromPath(__DIR__."/test-file-attachment-1.txt")->as('pdf-file')->withMime('text/pdf'));

        $this->assertSame([
            'attachment' => __DIR__."/test-file-attachment.txt",
            'options' => [
                'as' => 'bar',
                'mime' => 'text/plain'
            ],
        ], $mailable->attachments[0]);
        $this->assertSame([
            'attachment' => __DIR__."/test-file-attachment-1.txt",
            'options' => [
                'as' => 'pdf-file',
                'mime' => 'text/pdf'
            ],
        ], $mailable->attachments[1]);
    }
}

class ExampleMailable extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('from@example.com', 'From'),
            replyTo: [
                'email' => 'mateus@example.com',
                'name' => 'Mateus'
            ]
        );
    }

    public function content(): Content
    {
        return new Content(
            html: "<html>Test</html>",
            text: "Test"
        );
    }
}