<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\ValueObjects;

final class Attachment
{
    public function __construct(
        public readonly string $filePath,
        public readonly string $name,
        public readonly ?string $mimeType = null
    ) {}
}