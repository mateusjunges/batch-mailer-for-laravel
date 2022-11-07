<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Tests\Transports;

use Illuminate\Support\Facades\App;
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
    public function test_to_string()
    {
        $transportOne = $this->createMock(BatchTransport::class);
        $transportOne->expects($this->once())->method('__toString')->willReturn('test_one');
        $transportTwo = $this->createMock(BatchTransport::class);
        $transportTwo->expects($this->once())->method('__toString')->willReturn('test_two');

        $transport = new FailoverTransport([$transportOne, $transportTwo]);

        $this->assertEquals('failover(test_one test_two)', (string) $transport);
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
}