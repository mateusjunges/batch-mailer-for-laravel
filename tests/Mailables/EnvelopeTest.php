<?php

namespace Junges\BatchMailer\Tests\Mailables;

use Junges\BatchMailer\Mailables\Address;
use Junges\BatchMailer\Mailables\Envelope;
use Junges\BatchMailer\Tests\TestCase;

class EnvelopeTest extends TestCase
{
    /** @test */
    public function it_can_receive_additional_addresses(): void
    {
        $envelope = new Envelope(to: ['taylor@example.com']);
        $envelope->to(new Address('taylorotwell@example.com'));

        $this->assertCount(2, $envelope->to);
        $this->assertEquals('taylor@example.com', $envelope->to[0]->email);
        $this->assertEquals('taylorotwell@example.com', $envelope->to[1]->email);

        $envelope->to('abigailotwell@example.com', 'Abigail Otwell');
        $this->assertEquals('abigailotwell@example.com', $envelope->to[2]->email);
        $this->assertEquals('Abigail Otwell', $envelope->to[2]->fullName);

        $envelope->to('adam@example.com');
        $this->assertEquals('adam@example.com', $envelope->to[3]->email);

        $envelope->to(['jeffrey@example.com', 'tyler@example.com']);
        $this->assertEquals('jeffrey@example.com', $envelope->to[4]->email);
        $this->assertEquals('tyler@example.com', $envelope->to[5]->email);

        $envelope->from('dries@example.com', 'Dries Vints');
        $this->assertEquals('dries@example.com', $envelope->from->email);
        $this->assertEquals('Dries Vints', $envelope->from->fullName);
    }

    /** @test */
    public function it_can_set_message_subject(): void
    {
        $envelope = new Envelope();
        $envelope->subject('Test Subject');
        $this->assertEquals('Test Subject', $envelope->subject);
    }

    /** @test */
    public function it_can_set_from_address(): void
    {
        $envelope = new Envelope();
        $envelope->from('from@example.com');

        $this->assertEquals('from@example.com', $envelope->from->email);
        $this->assertNull($envelope->from->fullName);

        $envelope = new Envelope();
        $envelope->from(new Address('from@example.com'));

        $this->assertEquals('from@example.com', $envelope->from->email);
        $this->assertNull($envelope->from->fullName);
    }

    /** @test */
    public function it_can_set_reply_to_address(): void
    {
        $envelope = new Envelope();
        $envelope->replyTo('reply-to@example.com');

        $this->assertEquals('reply-to@example.com', $envelope->replyTo[0]->email);
    }

    /** @test */
    public function it_can_set_tags(): void
    {
        $envelope = new Envelope();
        $envelope->tag('tag1');
        $envelope->tag('tag2');

        $this->assertEquals(['tag1', 'tag2'], $envelope->tags);
    }
}