<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer;

use Illuminate\Support\Traits\Conditionable;
use InteractionDesignFoundation\BatchMailer\Contracts\BatchMailable;
use InteractionDesignFoundation\BatchMailer\Contracts\BatchMailer;

final class PendingBatchMail
{
    use Conditionable;

    /** @var array<int, \InteractionDesignFoundation\BatchMailer\Mailables\Address> $to*/
    protected array $to = [];

    /** @var array<int, \InteractionDesignFoundation\BatchMailer\Mailables\Address> $cc*/
    protected array $cc = [];

    /** @var array<int, \InteractionDesignFoundation\BatchMailer\Mailables\Address> $bcc*/
    protected array $bcc = [];

    public function __construct(protected BatchMailer $mailer) {}

    public function send(BatchMailable $mailable): ?SentMessage
    {
        return $this->mailer->send($this->fill($mailable));
    }

    /** @param array<int, \InteractionDesignFoundation\BatchMailer\Mailables\Address> $users */
    public function to(array $users): self
    {
        $this->to = $users;

        return $this;
    }

    /** @param array<int, \InteractionDesignFoundation\BatchMailer\Mailables\Address> $users */
    public function cc(array $users): self
    {
        $this->cc = $users;

        return $this;
    }

    /** @param array<int, \InteractionDesignFoundation\BatchMailer\Mailables\Address> $users */
    public function bcc(array $users): self
    {
        $this->bcc = $users;

        return $this;
    }

    protected function fill(BatchMailable $mailable): BatchMailable
    {
        return tap($mailable->to($this->to))
            ->cc($this->cc)
            ->bcc($this->bcc);
    }
}