<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Tests\Transports;

use Illuminate\Support\Facades\App;
use InteractionDesignFoundation\BatchMailer\Tests\TestCase;
use InteractionDesignFoundation\BatchMailer\Transports\FailoverTransport;

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
    public function it_uses_fallback_transport_if_the_first_fails(): void
    {
        $this->setFailoverConfig();

        $this->markTestIncomplete();
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