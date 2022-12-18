<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer;

use Illuminate\Container\Container;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use InteractionDesignFoundation\BatchMailer\Contracts\BatchMailable;
use InteractionDesignFoundation\BatchMailer\Contracts\BatchMailer;
use InteractionDesignFoundation\BatchMailer\Contracts\Factory;
use InteractionDesignFoundation\BatchMailer\Mailables\Address;
use InteractionDesignFoundation\BatchMailer\Mailables\Attachment;
use InteractionDesignFoundation\BatchMailer\Mailables\Headers;
use PHPUnit\Framework\Assert as PHPUnit;

abstract class Mailable implements BatchMailable
{
    use Conditionable;

    public Address $from;

    /** @var array<int, Address> $to */
    public array $to = [];

    /** @var array<int, Address> $cc */
    public array $cc = [];

    /** @var array<int, Address> $bcc */
    public array $bcc = [];

    /** @var array<int, Address> $replyTo */
    public array $replyTo = [];

    /** The subject of the message. */
    public ?string $subject = null;

    /** The html template for the message (if applicable). */
    public ?string $html = null;

    /** The plain text view to use for the message. */
    public ?string $textView = null;

    /** The markdown template for the message (if applicable). */
    public ?string $markdown = null;

    /** The view to use for the message. */
    public ?string $view = null;

    /** The view data for the message. */
    public array $viewData = [];

    /** @var array<int, Attachment>  */
    public array $attachments = [];

    /** The tags for the message. */
    protected array $tags = [];

    /** The metadata for the message. */
    protected array $metadata = [];

    /** @var array<int, \Closure>  */
    public array $callbacks = [];

    public ?string $theme = null;

    /** The name of the mailer that should be used to send the message. */
    public string $mailer;

    public function send(BatchMailer $batchMailer): ?SentMessage
    {
        $this->prepareMailableForDelivery();

        $mailer = $batchMailer instanceof Factory
            ? $batchMailer->mailer($this->mailer)
            : $batchMailer;

        return $mailer->send($this->buildView(), $this->buildViewData(), function(BatchMailerMessage $message) {
            $this->buildFrom($message)
                ->buildRecipients($message)
                ->buildSubject($message)
                ->buildTags($message)
                ->buildMetadata($message)
                ->runCallbacks($message);
        });
    }

    protected function buildFrom(BatchMailerMessage $message): self
    {
        $message->setFrom($this->from);

        return $this;
    }

    protected function buildRecipients(BatchMailerMessage $message): self
    {
        foreach (['to', 'cc', 'replyTo'] as $type) {
            foreach ($this->{$type} as $recipient) {
                $type = ucfirst($type);
                $message->{"add$type"}($recipient);
            }
        }

        return $this;
    }

    protected function buildSubject(BatchMailerMessage $message): self
    {
        if ($this->subject !== null) {
            $message->setSubject($this->subject);
        } else {
            $message->setSubject(Str::title(Str::snake(class_basename($this), ' ')));
        }

        return $this;
    }

    protected function buildTags(BatchMailerMessage $message): self
    {
        if ($this->tags) {
            foreach ($this->tags as $tag) {
                $message->addTag($tag);
            }
        }

        return $this;
    }

    protected function buildMetadata(BatchMailerMessage $message): self
    {
        if ($this->metadata) {
            foreach ($this->metadata as $key => $metadata) {
                $message->addMetadata($key, $metadata);
            }
        }

        return $this;
    }

    protected function runCallbacks(BatchMailerMessage $message): self
    {
        foreach ($this->callbacks as $callback) {
            $callback($message);
        }

        return $this;
    }

    protected function buildView(): array|string
    {
        if ($this->html !== null) {
            return array_filter([
                'html' => new HtmlString($this->html),
                'text' => $this->textView,
            ]);
        }

        if ($this->markdown !== null) {
            return $this->buildMarkdownView();
        }

        if ($this->view !== null && $this->textView !== null) {
            return [$this->view, $this->textView];
        } elseif ($this->textView !== null) {
            return ['text' => $this->textView];
        }

        return $this->view;
    }

    protected function buildMarkdownView(): array
    {
        $markdown = Container::getInstance()->make(Markdown::class);

        if ($this->theme !== null) {
            $markdown->theme($this->theme);
        }

        $data = $this->buildViewData();

        return [
            'html' => $markdown->render($this->markdown, $data),
            'text' => $this->buildMarkdownText($markdown, $data),
        ];
    }

    public function buildViewData(): array
    {
        $data = $this->viewData;

        foreach ((new \ReflectionClass($this))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getDeclaringClass()->getName() !== self::class) {
                $data[$property->getName()] = $property->getValue($this);
            }
        }

        return $data;
    }

    protected function buildMarkdownText(Markdown $markdown, array $data): string
    {
        return $this->textView ?? (string) $markdown->renderText($this->markdown, $data);
    }

    public function from(Address $from): BatchMailable
    {
        $this->from = $from;

        return $this;
    }

    public function replyTo(Address|string $replyTo, string $name = null): BatchMailable
    {
        $address = $this->getAddresses($replyTo, $name);

        $this->replyTo = array_merge($this->replyTo, $address);

        return $this;
    }

    public function cc(array $addresses): BatchMailable
    {
        $this->cc = $this->getAddresses($addresses);

        return $this;
    }

    public function bcc(array $addresses): BatchMailable
    {
        $this->bcc = $this->getAddresses($addresses);

        return $this;
    }

    public function to(Address|array $addresses): BatchMailable
    {
        $this->to = $this->getAddresses($addresses);

        return $this;
    }

    /** Set the name of the mailer that should send the message. */
    public function mailer(string $mailer): BatchMailable
    {
        $this->mailer = $mailer;

        return $this;
    }

    protected function getAddresses(Address|string|array $addresses, string $name = null): array
    {
        $addresses = Arr::wrap($addresses);

        if (Arr::isAssoc($addresses) && array_key_exists('email', $addresses)) {
            return [new Address($addresses['email'], $addresses['name'] ?? $name ?? $addresses['email'])];
        }

        return collect($addresses)->map(function (Address|array|string $address) use ($name): Address {
            if ($address instanceof Address) {
                return $address;
            }

            if (is_string($address)) {
                return new Address($address, $name ?? $address);
            }

            return new Address($address['email'], $address['name'] ?? $name ?? $address['email']);
        })->all();
    }

    public function hasTo(Address|array|string $address, string $name = null): bool
    {
        return $this->hasRecipient($address, $name);
    }

    protected function hasRecipient(Address|array|string $address, string $name = null, string $property = 'to'): bool
    {
        if (empty($address)) {
            return false;
        }

        $expected = $address;

        if (is_array($address)) {
            $expected = new Address($address['email'], $address['name']);
        }

        if (is_string($address)) {
            $expected = new Address($address, $name ?? $address);
        }

        return collect($this->{$property})->contains(function (Address $address) use ($name, $expected) {
            if (is_null($name)) {
                return $address->email === $expected->email;
            }

            return $address == $expected;
        });
    }

    public function assertHasTo(Address|array|string $address, ?string $name = null)
    {
        $recipient = $address;

        if ($address instanceof Address) {
            $recipient = $address->email;
        }

        if (is_array($address)) {
            $recipient = $address['email'];
        }

        PHPUnit::assertTrue(
            $this->hasTo($address, $name),
            "Did not see expected recipient [$recipient] in email recipients."
        );
    }

    private function prepareMailableForDelivery(): void
    {
        $this->ensureEnvelopeIsHydrated();
        $this->ensureAttachmentsAreHydrated();
        $this->ensureContentIsHydrated();
        $this->ensureHeadersAreHydrated();
    }

    private function ensureContentIsHydrated(): void
    {
        $content = $this->content();

        $this->view = $content->view;
        $this->textView = $content->text;
        $this->markdown = $content->markdown;

        if ($content->htmlString) {
            $this->html = $content->htmlString;
        }

        $this->viewData = $content->with;
    }

    private function ensureEnvelopeIsHydrated(): void
    {
        $envelope = $this->envelope();

        $this->from = $envelope->from;
        $this->subject = $envelope->subject;
        $this->to = $this->uniqueAddresses('to', $envelope->to);
        $this->cc = $this->uniqueAddresses('cc', $envelope->cc);
        $this->bcc = $this->uniqueAddresses('bcc', $envelope->bcc);
        $this->replyTo = $this->uniqueAddresses('replyTo', $envelope->replyTo);
        $this->tags = $envelope->tags;
        $this->metadata = $envelope->metadata;
    }

    /** @param array<int, Address> $addresses */
    private function uniqueAddresses(string $property, array $addresses): array
    {
        return collect($addresses)
            ->merge(collect($this->{$property}))
            ->unique(fn (Address $address) => $address->email)
            ->all();
    }

    private function ensureHeadersAreHydrated(): void
    {
        if (! method_exists($this, 'headers')) {
            return;
        }

        $headers = $this->headers();
        assert($headers instanceof Headers);

        $this->withBatchMailerMessage(function (BatchMailerMessage $message) use ($headers) {
            if ($headers->messageId){
                $message->addCustomHeader('Message-Id', $headers->messageId);
            }

            if (count($headers->references) > 0) {
                $message->addCustomHeader('References', $headers->referencesString());
            }

            foreach ($headers->text as $name => $value) {
                $message->addCustomHeader($name, $value);
            }
        });
    }

    protected function ensureAttachmentsAreHydrated(): void
    {
        if (! method_exists($this, 'attachments')) {
            return;
        }

        $attachments = $this->attachments();

        Collection::make($attachments)
            ->each(fn ($attachment) => $this->attach($attachment));

        $this->withBatchMailerMessage(function (BatchMailerMessage $message) use ($attachments) {
            foreach ($attachments as $attachment) {
                $message->attach($attachment);
            }
        });
    }

    protected function withBatchMailerMessage(\Closure $callback): self
    {
        $this->callbacks[] = $callback;

        return $this;
    }

    public function attach(Attachable|Attachment|string $attachment, array $options = []): self
    {
        if ($attachment instanceof Attachable) {
            $attachment = $attachment->toMailAttachment();
        }

        if ($attachment instanceof Attachment) {
            return $attachment->attachTo($this);
        }

        $this->attachments = collect($this->attachments)
            ->push(compact('attachment', 'options'))
            ->unique('attachment')
            ->all();

        return $this;
    }

    /** Attach in-memory data as an attachment. */
    public function attachData(string $data, string $name, array $options = []): self
    {
        $this->rawAttachments = collect($this->rawAttachments)
            ->push(compact('data', 'name', 'options'))
            ->unique(fn ($file) => $file['name'].$file['data'])
            ->all();

        return $this;
    }
}