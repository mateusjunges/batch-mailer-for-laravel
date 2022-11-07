<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Tests;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\App;
use Illuminate\Support\HtmlString;
use InteractionDesignFoundation\BatchMailer\BatchMailer;
use InteractionDesignFoundation\BatchMailer\BatchMailerMessage;
use InteractionDesignFoundation\BatchMailer\BatchMailManager;
use InteractionDesignFoundation\BatchMailer\Contracts\BatchTransport;
use InteractionDesignFoundation\BatchMailer\Exceptions\TransportException;
use InteractionDesignFoundation\BatchMailer\SentMessage;
use InteractionDesignFoundation\BatchMailer\Transports\FailoverTransport;
use InteractionDesignFoundation\BatchMailer\ValueObjects\Address;
use InteractionDesignFoundation\BatchMailer\Events\BatchMessageSent;
use InteractionDesignFoundation\BatchMailer\Transports\ArrayTransport;
use Mockery as m;

final class BatchMailerTest extends TestCase
{
    /** @test */
    public function it_sends_the_message_with_proper_view_content(): void
    {
        $view = m::mock(Factory::class);
        $view->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');

        $mailer = new BatchMailer('array', $view, new ArrayTransport());

        $sentMessage = $mailer->send('foo', ['data'], function(BatchMailerMessage $message) {
            $message->addTo(new Address('mateus@junges.dev'))
                ->setFrom(new Address('sender@example.com'));
        });

        $this->assertStringContainsString('rendered.view', $sentMessage->originalMessage()->html());
    }

    public function test_it_sends_the_message_with_cc_and_bcc_recipients(): void
    {
        $view = m::mock(Factory::class);
        $view->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');

        $mailer = new BatchMailer('array', $view, new ArrayTransport);

        $sentMessage = $mailer->send('foo', ['data'], function (BatchMailerMessage $message) {
            $message->addTo(new Address('taylor@laravel.com'))
                ->addCc(new Address('dries@laravel.com'))
                ->addBcc(new Address('james@laravel.com'))
                ->setFrom(new Address('hello@laravel.com'));
        });

        $recipients = collect($sentMessage->originalMessage()->bcc())->map(function (Address $recipient) {
            return $recipient->email;
        });

        $this->assertEquals('rendered.view', $sentMessage->originalMessage()->html());
        $this->assertEquals('dries@laravel.com', $sentMessage->originalMessage()->cc()[0]->email);
        $this->assertEquals("james@laravel.com", $sentMessage->originalMessage()->bcc()[0]->email);
        $this->assertTrue($recipients->contains('james@laravel.com'));
    }

    /** @test */
    public function it_sends_the_message_with_proper_view_content_using_html_strings()
    {
        $view = m::mock(Factory::class);
        $view->shouldReceive('render')->never();

        $mailer = new BatchMailer('array', $view, new ArrayTransport);

        $sentMessage = $mailer->send(
            ['html' => new HtmlString('<p>Hello Laravel</p>'), 'text' => new HtmlString('Hello World')],
            ['data'],
            function (BatchMailerMessage $message) {
                $message->addTo(new Address('taylor@laravel.com'))->setFrom(new Address('hello@laravel.com'));
            }
        );

        $this->assertStringContainsString('<p>Hello Laravel</p>', $sentMessage->originalMessage()->html());
        $this->assertStringContainsString('Hello World', $sentMessage->originalMessage()->text());
    }

    /** @test */
    public function it_sends_the_message_with_proper_view_content_when_using_html_method(): void
    {
        $view = m::mock(Factory::class);
        $view->shouldReceive('render')->never();

        $mailer = new BatchMailer('array', $view, new ArrayTransport);

        $sentMessage = $mailer->html('<p>Hello World</p>', function (BatchMailerMessage $message) {
            $message->addTo(new Address('taylor@laravel.com'))->setFrom(new Address('hello@laravel.com'));
        });

        $this->assertStringContainsString('<p>Hello World</p>', $sentMessage->originalMessage()->html());
    }

    /** @test */
    public function it_sends_the_message_with_proper_plain_view_content(): void
    {
        $view = m::mock(Factory::class);
        $view->shouldReceive('make')->twice()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');
        $view->shouldReceive('render')->once()->andReturn('rendered.plain');

        $mailer = new BatchMailer('array', $view, new ArrayTransport);

        $sentMessage = $mailer->send(['foo', 'bar'], ['data'], function (BatchMailerMessage $message) {
            $message->addTo(new Address('taylor@laravel.com'))->setFrom(new Address('hello@laravel.com'));
        });

        $expected = "rendered.view";

        $this->assertStringContainsString($expected, $sentMessage->originalMessage()->html());

        $expected = "rendered.plain";

        $this->assertStringContainsString($expected, $sentMessage->originalMessage()->text());
    }

    /** @test */
    public function it_dispatches_events()
    {
        $view = m::mock(Factory::class);
        $view->shouldReceive('make')->once()->andReturn($view);
        $view->shouldReceive('render')->once()->andReturn('rendered.view');

        $events = m::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')->once()->with(m::type(BatchMessageSent::class));

        $mailer = new BatchMailer('array', $view, new ArrayTransport, $events);

        $mailer->send('foo', ['data'], function (BatchMailerMessage $message) {
            $message->addTo(new Address('taylor@laravel.com'))->setFrom(new Address('hello@laravel.com'));
        });
    }

    /** @test */
    public function it_uses_fallback_transport_if_the_first_fails(): void
    {
        $this->app['config']->set('batch-mailer.default', 'failover');

        $this->app['config']->set('batch-mailer.mailers', [
            'failover' => [
                'transport' => 'failover',
                'mailers' => [
                    'extended-fail',
                    'extended',
                ]
            ],
            'extended' => [
                'transport' => 'extended'
            ],
            'extended-fail' => [
                'transport' => 'extended-fail'
            ]
        ]);

        $manager = new BatchMailManager($this->app);

        $manager->extend('extended', function() {
            return new class implements BatchTransport {
                public function send(BatchMailerMessage $batchMailerMessage): ?SentMessage
                {
                    return new SentMessage($batchMailerMessage, 'sent-with-fallback-driver');
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

        $manager->extend('extended-fail', function() {
            return new class implements BatchTransport {
                public function send(BatchMailerMessage $batchMailerMessage): ?SentMessage
                {
                    throw new TransportException('This transport failed.');
                }

                public function getNameSymbol(): string
                {
                    return 'extended-fail';
                }

                public function __toString(): string
                {
                    return $this->getNameSymbol();
                }
            };
        });

        $transport = $manager->getBatchTransport();

        $sentMessage = $manager->to([new Address('recipient@example.com')])->send(new TestMailable());

        $this->assertEquals('sent-with-fallback-driver', $sentMessage->messageId());
        $this->assertInstanceOf(FailoverTransport::class, $transport);
    }
}