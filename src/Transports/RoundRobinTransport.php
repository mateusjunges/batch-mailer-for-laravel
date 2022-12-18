<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Transports;

use InteractionDesignFoundation\BatchMailer\BatchMailerMessage;
use InteractionDesignFoundation\BatchMailer\Contracts\BatchTransport;
use InteractionDesignFoundation\BatchMailer\Exceptions\TransportException;
use InteractionDesignFoundation\BatchMailer\SentMessage;

abstract class RoundRobinTransport implements BatchTransport, \Stringable
{
    /** @var \SplObjectStorage<BatchTransport, float> $deadTransports*/
    protected \SplObjectStorage $deadTransports;
    protected int $cursor = -1;

    public function __construct(private readonly array $transports, private readonly int $retryPeriod = 60)
    {
        if (! $transports) {
            throw new TransportException(sprintf('"%s" must have at least one transport configured.', static::class));
        }

        $this->deadTransports = new \SplObjectStorage();
    }

    public function send(BatchMailerMessage $batchMailerMessage): ?SentMessage
    {
        $exception = new TransportException('All transports failed.');

        while ($transport = $this->getNextTransport()) {
            try {
                return $transport->send($batchMailerMessage);
            } catch (TransportException $transportException) {
                $exception->appendDebug(
                    $this->formatException($transport, $transportException)
                );

                $this->deadTransports[$transport] = microtime(true);
            }
        }

        throw $exception;
    }

    protected function getNextTransport(): ?BatchTransport
    {
        if ($this->cursor === -1) {
            $this->cursor = $this->getInitialCursor();
        }

        $cursor = $this->cursor;
        while (true) {
            $transport = $this->transports[$cursor];

            if (!$this->isTransportDead($transport)) {
                break;
            }

            if ((microtime(true) - $this->deadTransports[$transport]) > $this->retryPeriod) {
                $this->deadTransports->detach($transport);

                break;
            }

            if ($this->cursor === $cursor = $this->moveCursor($cursor)) {
                return null;
            }
        }

        $this->cursor = $this->moveCursor($cursor);

        return $transport;
    }

    protected function isTransportDead(BatchTransport $transport): bool
    {
        return $this->deadTransports->contains($transport);
    }

    protected function getInitialCursor(): int
    {
        // the cursor initial value is randomized so that
        // when are not in a daemon, we are still rotating the transports
        return random_int(0, count($this->transports) - 1);
    }

    public function getNameSymbol(): string
    {
        return 'roundrobin';
    }

    private function moveCursor(int $cursor): int
    {
        return ++$cursor >= count($this->transports) ? 0 : $cursor;
    }

    public function __toString(): string
    {
        return $this->getNameSymbol().'('.implode(' ', array_map('strval', $this->transports)).')';
    }

    private function formatException(BatchTransport $transport, TransportException $exception): string
    {
        return sprintf('Transport %s: %s %s', $transport, $exception->getDebug(), PHP_EOL);
    }
}