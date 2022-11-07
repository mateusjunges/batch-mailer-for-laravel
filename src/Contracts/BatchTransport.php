<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Contracts;

use InteractionDesignFoundation\BatchMailer\BatchMailerMessage;
use InteractionDesignFoundation\BatchMailer\SentMessage;

interface BatchTransport
{
    public function send(BatchMailerMessage $batchMailerMessage): ?SentMessage;

    public function getNameSymbol(): string;

    public function __toString(): string;
}