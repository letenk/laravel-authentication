<?php

namespace App\Support;

class PhoneNormalizer
{
    /**
     * Strip '+' and non-digit characters.
     * Client is expected to send correct international format (e.g. +628123456789).
     *
     * Examples:
     *   +628123456789  → 628123456789
     *   +1234567890    → 1234567890
     */
    public static function normalize(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }
}
