<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Tests;

use InteractionDesignFoundation\BatchMailer\BatchMailerMessage;
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


}