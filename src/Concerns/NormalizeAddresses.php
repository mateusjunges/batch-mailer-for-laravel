<?php

namespace InteractionDesignFoundation\BatchMailer\Concerns;

use Illuminate\Support\Arr;
use InteractionDesignFoundation\BatchMailer\Mailables\Address;

trait NormalizeAddresses
{
    /** Normalize the given array of addresses. */
    protected function normalizeAddresses(Address|string|array $addresses, string $name = null): array
    {
        $addresses = Arr::wrap($addresses);

        if (Arr::isAssoc($addresses) && array_key_exists('email', $addresses)) {
            return [new Address($addresses['email'], $addresses['name'] ?? $name ?? $addresses['email'])];
        }

        return collect($addresses)->map(function (Address|array|string $address) use ($name): Address {
            if ($address instanceof Address) {
                return $address;
            }

            if (is_string($address)) {
                return new Address($address, $name ?? $address);
            }

            return new Address($address['email'], $address['name'] ?? $name ?? $address['email']);
        })->all();
    }
}