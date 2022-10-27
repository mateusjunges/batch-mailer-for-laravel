<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Tests\Transports;

use Illuminate\Support\Facades\App;
use InteractionDesignFoundation\BatchMailer\BatchMailerMessage;
use InteractionDesignFoundation\BatchMailer\Exceptions\TooManyRecipients;
use InteractionDesignFoundation\BatchMailer\Tests\TestCase;
use InteractionDesignFoundation\BatchMailer\Transports\PostmarkBatchTransport;
use InteractionDesignFoundation\BatchMailer\ValueObjects\Address;
use InteractionDesignFoundation\BatchMailer\ValueObjects\Attachment;
use Ixdf\Postmark\Api\Message\Requests\Batch;
use Ixdf\Postmark\Api\Message\Requests\Message;
use Ixdf\Postmark\Facades\Postmark;

final class PostmarkTransportTest extends TestCase
{
    /** @test */
    public function it_respect_the_number_of_max_recipients(): void
    {
        $transport = new PostmarkBatchTransport();

        $message = (new BatchMailerMessage())
            ->setFrom(new Address('foo@bar.baz', 'Foo'))
            ->addTo(new Address('bar@foo.baz', 'Bar'))
            ->addBcc(new Address('bcc@bcc.com', 'Bcc'))
            ->addCc(new Address('cc@cc.com', 'Cc'))
            ->setSubject('Foo bar')
            ->addReplyTo(new Address('reply@to.foo', 'Reply To'))
            ->setText('Hi')
            ->setMessageStream('broadcast')
            ->setHtml('<html>Hi</html>');

        foreach (range(0, 500) as $recipient) {
            $message->addTo(new Address("email$recipient@test.com", (string) $recipient));
        }

        $this->expectException(TooManyRecipients::class);

        $transport->send($message);
    }

    public function test_send(): void
    {
        Postmark::fake();

        $transport = new PostmarkBatchTransport();

        $this->assertEquals('postmark', $transport->getNameSymbol());

        $message = (new BatchMailerMessage())
            ->setFrom(new Address('foo@bar.baz', 'Foo'))
            ->addTo(new Address('bar@foo.baz', 'Bar'))
            ->addBcc(new Address('bcc@bcc.com', 'Bcc'))
            ->addCc(new Address('cc@cc.com', 'Cc'))
            ->setSubject('Foo bar')
            ->addCustomHeader('foo', 'bar')
            ->addReplyTo(new Address('reply@to.foo', 'Reply To'))
            ->setText('Hi')
            ->setMessageStream('broadcast')
            ->setHtml('<html>Hi</html>');

        $transport->send($message);

        Postmark::assertBatchSent(function(Batch $batch): bool {
            $message = $batch->all()[0];

            assert($message instanceof Message);

            if ($message->getToAddress() !== ['Bar <bar@foo.baz>']) {
                return false;
            }

            if ($message->getFrom() !== 'Foo <foo@bar.baz>') {
                return false;
            }

            if ($message->getSubject() !== 'Foo bar') {
                return false;
            }

            if ($message->getTextBody() !== 'Hi') {
                return false;
            }

            if ($message->getHtmlBody() !== '<html>Hi</html>') {
                return false;
            }

            if ($message->getReplyTo() !== ['Reply To <reply@to.foo>']) {
                return false;
            }

            if ($message->getBcc() !== ['Bcc <bcc@bcc.com>']) {
                return false;
            }

            if ($message->getCc() !== ['Cc <cc@cc.com>']) {
                return false;
            }

            $expectedHeaders = [
                [
                    'Name' => 'foo',
                    'Value' => 'bar'
                ]
            ];

            if ($message->getHeaders() !== $expectedHeaders) {
                return false;
            }

            return true;
        });
    }

    public function test_basic_attachment(): void
    {
        Postmark::fake();

        $transport = new PostmarkBatchTransport();

        $message = (new BatchMailerMessage())
            ->setFrom(new Address('foo@bar.baz', 'Foo'))
            ->addTo(new Address('bar@foo.baz', 'Bar'))
            ->addBcc(new Address('bcc@bcc.com', 'Bcc'))
            ->addCc(new Address('cc@cc.com', 'Cc'))
            ->setSubject('Foo bar')
            ->addReplyTo(new Address('reply@to.foo', 'Reply To'))
            ->setText('Hi')
            ->attach(new Attachment(
                __DIR__."/../test-file-attachment.txt",
                'Testing attachments',
            ))
            ->setMessageStream('broadcast')
            ->setHtml('<html>Hi</html>');

        $transport->send($message);

        Postmark::assertBatchSent(function(Batch $batch): bool {
            $message = $batch->all()[0];

            assert($message instanceof Message);

            $expected = [
                "Name" => "VGVzdCBmaWxlIGF0dGFjaG1lbnQ=",
                "Content" => "Testing attachments",
                "ContentType" => "text/plain",
                "ContentId" => "VGVzdCBmaWxlIGF0dGFjaG1lbnQ=",
            ];

            if ($message->getAttachments()[0] !== $expected) {
                return false;
            }

            return true;
        });
    }

    /** @test */
    public function get_postmark_transport(): void
    {
        $this->app['config']->set('batch-mailer.default', 'postmark');

        $this->app['config']->set('batch-mailer.mailers', [
            'postmark' => [
                'transport' => 'postmark'
            ]
        ]);

        $transport = App::make('batch-mailer')->getBatchTransport();

        $this->assertInstanceOf(PostmarkBatchTransport::class, $transport);
    }
}