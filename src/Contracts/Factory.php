<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Contracts;

interface Factory
{
    public function mailer(string $name = null): BatchMailer;
}