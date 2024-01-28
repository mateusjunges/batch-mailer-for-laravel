<?php declare(strict_types=1);

namespace Junges\BatchMailer\Tests\Transports;

use Illuminate\Support\Facades\App;
use Junges\BatchMailer\Tests\TestCase;
use Junges\BatchMailer\Transports\MailgunBatchTransport;

final class MailgunTransportTest extends TestCase
{
    /** @test */
    public function get_mailgun_transport(): void
    {
        $this->app['config']->set('batch-mailer.default', 'mailgun');

        $this->app['config']->set('batch-mailer.mailers', [
            'mailgun' => [
                'api_token' => 'test-token',
                'domain' => 'test-domain',
                'transport' => 'mailgun'
            ]
        ]);

        $transport = App::make('batch-mailer')->getBatchTransport();

        $this->assertInstanceOf(MailgunBatchTransport::class, $transport);
    }
}