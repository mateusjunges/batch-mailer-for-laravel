<?php declare(strict_types=1);

namespace Junges\BatchMailer\Contracts;

use Junges\BatchMailer\BatchMailerMessage;
use Junges\BatchMailer\SentMessage;

interface BatchTransport
{
    public function send(BatchMailerMessage $batchMailerMessage): ?SentMessage;

    public function getNameSymbol(): string;

    public function __toString(): string;
}