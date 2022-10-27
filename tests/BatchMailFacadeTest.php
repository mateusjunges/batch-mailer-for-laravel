<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Tests;

use InteractionDesignFoundation\BatchMailer\BatchMail;
use InteractionDesignFoundation\BatchMailer\PendingBatchMail;
use InteractionDesignFoundation\BatchMailer\SentMessage;
use InteractionDesignFoundation\BatchMailer\ValueObjects\Address;

final class BatchMailFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('batch-mailer.default', 'array');

        $this->app['config']->set('batch-mailer.mailers', [
            'array' => [
                'transport' => 'array'
            ]
        ]);
    }

    /** @test */
    public function it_returns_a_pending_mail_instance(): void
    {
        $pending = BatchMail::to([
            new Address('mateus@junges.dev', 'Mateus')
        ]);

        $this->assertInstanceOf(PendingBatchMail::class, $pending);
    }

    /** @test */
    public function it_can_send_mailables(): void
    {
        $sent = BatchMail::to([
            new Address('mateus@junges.dev', 'Mateus')
        ])->send(new TestMailable());

        $this->assertInstanceOf(SentMessage::class, $sent);
    }
}