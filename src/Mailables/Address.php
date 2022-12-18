<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Mailables;

final class Address
{
    public function __construct(
        public readonly string $email,
        public readonly ?string $fullName = null
    ) {}

    public function getFullName(): string
    {
        return $this->fullName ?? $this->email;
    }
}