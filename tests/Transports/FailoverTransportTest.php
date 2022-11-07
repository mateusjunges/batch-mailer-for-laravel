<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Tests\Transports;

use Illuminate\Support\Facades\App;
use InteractionDesignFoundation\BatchMailer\BatchMailerMessage;
use InteractionDesignFoundation\BatchMailer\Contracts\BatchTransport;
use InteractionDesignFoundation\BatchMailer\Exceptions\TransportException;
use InteractionDesignFoundation\BatchMailer\Tests\TestCase;
use InteractionDesignFoundation\BatchMailer\Transports\FailoverTransport;
use InteractionDesignFoundation\BatchMailer\Transports\RoundRobinTransport;

final class FailoverTransportTest extends TestCase
{
    /** @test */
    public function get_failover_transport_with_configured_transports(): void
    {
       $this->setFailoverConfig();

        $transport = App::make('batch-mailer')->getBatchTransport();

        $this->assertInstanceOf(FailoverTransport::class, $transport);
    }

    /** @test */
    public function send_with_no_transports(): void
    {
        $this->expectException(TransportException::class);

        new FailoverTransport([]);
    }

    /** @test */
    public function test_to_string(): void
    {
        $transportOne = $this->createMock(BatchTransport::class);
        $transportOne->expects($this->once())->method('__toString')->willReturn('test_one');
        $transportTwo = $this->createMock(BatchTransport::class);
        $transportTwo->expects($this->once())->method('__toString')->willReturn('test_two');

        $transport = new FailoverTransport([$transportOne, $transportTwo]);

        $this->assertEquals('failover(test_one test_two)', (string) $transport);
    }

    /** @test */
    public function send_with_all_transports_dead(): void
    {
        $t1 = $this->createMock(BatchTransport::class);
        $t1->expects($this->once())->method('send')->will($this->throwException(new TransportException('Test')));
        $t2 = $this->createMock(BatchTransport::class);
        $t2->expects($this->once())->method('send')->will($this->throwException(new TransportException('Test 2')));
        $t = new FailoverTransport([$t1, $t2]);
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('All transports failed.');
        $t->send(new BatchMailerMessage());

        $this->assertTransports($t, 1, [$t1, $t2]);
    }

    /** @test */
    public function test_failure_debug_information()
    {
        $t1 = $this->createMock(BatchTransport::class);
        $e1 = new TransportException();
        $e1->appendDebug('Debug message 1');
        $t1->expects($this->once())->method('send')->will($this->throwException($e1));
        $t2 = $this->createMock(BatchTransport::class);
        $e2 = new TransportException();
        $e2->appendDebug('Debug message 2');
        $t2->expects($this->once())->method('send')->will($this->throwException($e2));
        $t = new FailoverTransport([$t1, $t2]);

        try {
            $t->send(new BatchMailerMessage());
        } catch (TransportException $e) {
            $this->assertStringContainsString($e1->getDebug(), $e->getDebug());
            $this->assertStringContainsString($e2->getDebug(), $e->getDebug());

            return;
        }

        $this->fail('Expected exception was not thrown!');
    }

    public function test_send_one_dead()
    {
        $t1 = $this->createMock(BatchTransport::class);
        $t1->expects($this->once())->method('send')->will($this->throwException(new TransportException()));
        $t2 = $this->createMock(BatchTransport::class);
        $t2->expects($this->exactly(3))->method('send');
        $t = new FailoverTransport([$t1, $t2]);
        $p = new \ReflectionProperty($t, 'cursor');
        $p->setValue($t, 0);
        $t->send(new BatchMailerMessage());
        $this->assertTransports($t, 0, [$t1]);
        $t->send(new BatchMailerMessage());
        $this->assertTransports($t, 0, [$t1]);
        $t->send(new BatchMailerMessage());
        $this->assertTransports($t, 0, [$t1]);
    }


    private function setFailoverConfig(): void
    {
        $this->app['config']->set('batch-mailer.default', 'failover');

        $this->app['config']->set('batch-mailer.mailers', [
            'failover' => [
                'transport' => 'failover',
                'mailers' => [
                    'mailgun',
                    'postmark',
                ]
            ],
            'mailgun' => [
                'api_token' => '',
                'domain' => '',
                'transport' => 'mailgun'
            ],
            'postmark' => [
                'transport' => 'postmark'
            ],
            'array' => [
                'transport' => 'array'
            ]
        ]);
    }

    /**
     * @throws \ReflectionException
     */
    private function assertTransports(RoundRobinTransport $transport, int $cursor, array $deadTransports): int
    {
        $property = new \ReflectionProperty($transport, 'cursor');
        if ($cursor !== -1) {
            $this->assertSame($cursor, $property->getValue($transport));
        }

        $cursor = $property->getValue($transport);

        $property = new \ReflectionProperty($transport, 'deadTransports');
        $this->assertSame($deadTransports, iterator_to_array($property->getValue($transport)));

        return $cursor;
    }
}