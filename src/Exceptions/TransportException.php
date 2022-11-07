<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Exceptions;

use InteractionDesignFoundation\BatchMailer\Contracts\Exception;

final class TransportException extends \RuntimeException implements Exception
{
    private string $debug = '';

    public function getDebug(): string
    {
        return $this->debug;
    }

    public function appendDebug(string $debug): void
    {
        $this->debug .= $debug;
    }
}