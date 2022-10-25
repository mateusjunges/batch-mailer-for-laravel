<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Tests;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\HtmlString;
use InteractionDesignFoundation\BatchMailer\BatchMailer;
use InteractionDesignFoundation\BatchMailer\BatchMailerMessage;
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
}