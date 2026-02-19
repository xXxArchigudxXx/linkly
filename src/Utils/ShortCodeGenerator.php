<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Generates unique short codes using base62 encoding.
 */
final class ShortCodeGenerator
{
    private const CHARSET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public static function generate(int $length = 6): string
    {
        // START_CONTRACT_generate
        // Intent: Generate random base62 short code
        // Input: length (int, default 6)
        // Output: string - random base62 string
        // END_CONTRACT_generate
        $code = '';
        $charsetLength = strlen(self::CHARSET);

        for ($i = 0; $i < $length; $i++) {
            $code .= self::CHARSET[random_int(0, $charsetLength - 1)];
        }

        return $code;
    }

    public static function isValidCode(string $code): bool
    {
        return preg_match('/^[a-zA-Z0-9]+$/', $code) === 1;
    }
}