<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Transports;

use InteractionDesignFoundation\BatchMailer\Contracts\BatchTransport;

final class FailoverTransport extends RoundRobinTransport
{
    private ?BatchTransport $currentTransport = null;

    protected function getNextTransport(): ?BatchTransport
    {
        if ($this->currentTransport === null || $this->isTransportDead($this->currentTransport)) {
            $this->currentTransport = parent::getNextTransport();
        }

        return $this->currentTransport;
    }

    public function getNameSymbol(): string
    {
        return 'failover';
    }

    public function getInitialCursor(): int
    {
        return 0;
    }
}