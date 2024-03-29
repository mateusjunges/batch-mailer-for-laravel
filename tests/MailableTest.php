<?php declare(strict_types=1);

namespace Junges\BatchMailer\Tests;

use Junges\BatchMailer\Mailable;
use Junges\BatchMailer\Mailables\Address;
use Junges\BatchMailer\Mailables\Content;
use Junges\BatchMailer\Mailables\Envelope;
use PHPUnit\Framework\AssertionFailedError;

final class MailableTest extends TestCase
{
    /** @test */
    public function it_set_recipients_correctly(): void
    {
        $mailable = new WelcomeMailableStub();
        $mailable->to(['mateus@junges.dev']);
        $this->assertEquals([new Address('mateus@junges.dev', 'mateus@junges.dev')], $mailable->to);
        $this->assertTrue($mailable->hasTo('mateus@junges.dev'));
        $mailable->assertHasTo('mateus@junges.dev');

        $mailable = new WelcomeMailableStub;
        $mailable->to(['email' => 'taylor@laravel.com', 'name' => 'Taylor Otwell']);
        $this->assertEquals([new Address('taylor@laravel.com', 'Taylor Otwell')], $mailable->to);
        $this->assertTrue($mailable->hasTo('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasTo('taylor@laravel.com'));
        $mailable->assertHasTo('taylor@laravel.com', 'Taylor Otwell');
        $mailable->assertHasTo('taylor@laravel.com');

        $mailable = new WelcomeMailableStub;
        $mailable->to(['taylor@laravel.com']);
        $this->assertEquals([new Address('taylor@laravel.com', 'taylor@laravel.com')], $mailable->to);
        $this->assertTrue($mailable->hasTo('taylor@laravel.com'));
        $this->assertFalse($mailable->hasTo('taylor@laravel.com', 'Taylor Otwell'));
        $mailable->assertHasTo('taylor@laravel.com');
        try {
            $mailable->assertHasTo('taylor@laravel.com', 'Taylor Otwell');
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Did not see expected recipient [taylor@laravel.com] in email recipients.\nFailed asserting that false is true.", $e->getMessage());
        }

        $mailable = new WelcomeMailableStub;
        $mailable->to([['name' => 'Taylor Otwell', 'email' => 'taylor@laravel.com']]);
        $this->assertEquals([new Address('taylor@laravel.com', 'Taylor Otwell')], $mailable->to);
        $this->assertTrue($mailable->hasTo('taylor@laravel.com', 'Taylor Otwell'));
        $this->assertTrue($mailable->hasTo('taylor@laravel.com'));
        $mailable->assertHasTo('taylor@laravel.com');
    }
}

class WelcomeMailableStub extends Mailable
{
    public string $framework = 'laravel';

    protected string $version = '10.x';

    public function envelope(): Envelope
    {
        return new Envelope(from: new Address('from@example.com', 'From'));
    }

    public function content(): Content
    {
        return new Content(
            html: "<html>Test</html>",
            text: "Test",
            with: [
                'first_name' => 'Mateus',
                'last_name' => 'Junges',
            ],
        );
    }
}