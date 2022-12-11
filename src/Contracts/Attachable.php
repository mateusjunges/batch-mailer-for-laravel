<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Contracts;
use InteractionDesignFoundation\BatchMailer\ValueObjects\Attachment;
interface Attachable
{
    public function toMailAttachment(): Attachment;
}