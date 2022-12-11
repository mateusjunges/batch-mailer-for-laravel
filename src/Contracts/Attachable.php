<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Contracts;
use InteractionDesignFoundation\BatchMailer\Mailable\Attachment;

interface Attachable
{
    public function toMailAttachment(): Attachment;
}