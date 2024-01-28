<?php declare(strict_types=1);

namespace Junges\BatchMailer\Tests;

use Junges\BatchMailer\BatchMailerMessage;
use Junges\BatchMailer\BatchMailManager;
use Junges\BatchMailer\Contracts\BatchTransport;
use Junges\BatchMailer\Mailables\Address;
use Junges\BatchMailer\SentMessage;

final class BatchMailManagerTest extends TestCase
{
    private BatchMailManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new BatchMailManager($this->app);
    }

    /** @test */
    public function i_can_extend_drivers(): void
    {
        $this->app['config']->set('batch-mailer.mailers', [
            'extended' => [
                'transport' => 'extended',
            ],
        ]);

        $this->manager->extend('extended', function() {
            return new class implements BatchTransport {
                public function send(BatchMailerMessage $batchMailerMessage): ?SentMessage
                {
                    return new SentMessage($batchMailerMessage, 'sent-with-extended-driver');
                }

                public function getNameSymbol(): string
                {
                    return 'extended';
                }

                public function __toString(): string
                {
                    return $this->getNameSymbol();
                }
            };
        });

        $mailer = $this->manager->mailer('extended');

        $sentMessage = $mailer->to([new Address('recipient@example.com')])->send(new TestMailable());

        $this->assertEquals('sent-with-extended-driver', $sentMessage->messageId());
    }
}