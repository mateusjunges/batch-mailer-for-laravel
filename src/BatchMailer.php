<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use InteractionDesignFoundation\BatchMailer\Contracts\BatchMailable as MailableContract;
use InteractionDesignFoundation\BatchMailer\Contracts\BatchMailer as BatchMailerContract;
use InteractionDesignFoundation\BatchMailer\Contracts\BatchTransport;
use InteractionDesignFoundation\BatchMailer\Events\BatchMessageSent;
use InteractionDesignFoundation\BatchMailer\Mailables\Address;

final class BatchMailer implements BatchMailerContract
{
    protected Address $from;

    protected ?Address $replyTo = null;

    /** @var array<int, Address> $to */
    protected array $to = [];

    public function __construct(
        protected string $name,
        protected Factory $views,
        protected BatchTransport $transport,
        protected ?Dispatcher $events = null
    ) {}

    /** @inheritDoc */
    public function to(array $users): PendingBatchMail
    {
        return (new PendingBatchMail($this))->to($users);
    }

    /** @inheritDoc */
    public function bcc(array $users): PendingBatchMail
    {
        return (new PendingBatchMail($this))->bcc($users);
    }

    /** @param array<int, \InteractionDesignFoundation\BatchMailer\Mailables\Address|string> $users */
    public function cc(array $users): PendingBatchMail
    {
        return (new PendingBatchMail($this))->cc($users);
    }

    public function html(string $html, \Closure $callback = null): ?SentMessage
    {
        return $this->send(['html' => new HtmlString($html)], [], $callback);
    }

    public function plain(string $text, \Closure $callback = null): ?SentMessage
    {
        return $this->send(['text' => $text], [], $callback);
    }

    public function render(string|array $view, array $data = []): string
    {
        [$view, $plain, $raw] = $this->parseView($view);

        $data['message'] = $this->createMessage();

        return $this->renderView($view ?: $plain, $data);
    }

    public function getBatchTransport(): BatchTransport
    {
        return $this->transport;
    }

    public function getViewFactory(): Factory
    {
        return $this->views;
    }

    public function send(MailableContract|string|array $view, array $data = [], \Closure $callback = null): ?SentMessage
    {
        if ($view instanceof MailableContract) {
            return $this->sendMailable($view);
        }

        [$view, $plain, $raw] = $this->parseView($view);

        $message = $this->createMessage();

        if ($callback !== null) {
            $callback($message);
        }

        $this->addContent($message, $view, $plain, $raw, $data);

        // todo: add check to verify if the message should be sent
        $sentMessage = $this->sendBatchMessage($message);

        if (! $sentMessage) {
            return null;
        }

        $this->dispatchSentEvent($sentMessage, $data);

        return $sentMessage;
    }

    private function dispatchSentEvent(SentMessage $message, array $data = [])
    {
        $this->events?->dispatch(
            new BatchMessageSent($message, $data)
        );
    }

    protected function sendBatchMessage(BatchMailerMessage $message): ?SentMessage
    {
        try {
            return $this->transport->send($message);
        } finally {
            Storage::disk('local')->deleteDirectory(
                config('batch-mailer.attachments_temp_path')
            );
        }
    }

    protected function addContent(BatchMailerMessage $message, Htmlable|string|null $view, Htmlable|string|null $plain, ?string $raw, array $data = []): void
    {
        if (isset($view)) {
            $message->setHtml($this->renderView($view, $data) ?: ' ');
        }

        if (isset($plain)) {
            $message->setText($this->renderView($plain, $data) ?: ' ');
        }

        if (isset($raw)) {
            $message->setText($raw);
        }
    }

    protected function renderView(Htmlable|string $view, array $data): string
    {
        return $view instanceof Htmlable
            ? $view->toHtml()
            : $this->views->make($view, $data)->render();
    }

    protected function createMessage(): BatchMailerMessage
    {
        $message = new BatchMailerMessage();

        foreach ($this->to as $recipient) {
            $message->addTo($recipient);
        }

        if ($this->replyTo !== null) {
            $message->addReplyTo($this->replyTo);
        }

        return $message;
    }

    protected function parseView(string|array $view): array
    {
        if (is_string($view)) {
            return [$view, null, null];
        }

        if (is_array($view) && isset($view[0])) {
            return [$view[0], $view[1], null];
        }

        if (is_array($view)) {
            return [
                $view['html'] ?? null,
                $view['text'] ?? null,
                $view['raw'] ?? null
            ];
        }

        throw new \InvalidArgumentException('Invalid View');
    }

    protected function sendMailable(MailableContract $mailable): ?SentMessage
    {
        return $mailable->mailer($this->name)->send($this);
    }
}