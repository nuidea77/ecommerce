<?php

namespace App\Support;

/**
 * Mongolian mobile numbers: 8 digits, first digit 6-9 (optionally prefixed with +976 / 976).
 */
class Phone
{
    public const REGEX = '/^(?:\+?976)?[6-9]\d{7}$/';

    /** Strip spaces, dashes and the +976 country code; returns null when not a valid MN mobile. */
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/[\s\-()]+/', '', (string) $value) ?? '';
        if (! preg_match(self::REGEX, $digits)) {
            return null;
        }

        return substr($digits, -8);
    }
}
