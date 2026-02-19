<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Валидатор URL-адресов.
 */
final class UrlValidator
{
    private const ALLOWED_SCHEMES = ['http', 'https'];

    /**
     * Проверяет валидность формата URL.
     *
     * @param string $url URL для проверки
     * @return bool true если URL валиден
     */
    public static function isValid(string $url): bool
    {
        // START_CONTRACT_isValid
        // Intent: Проверить формат URL (наличие scheme и host)
        // Input: string $url
        // Output: bool - true если URL валиден
        // END_CONTRACT_isValid
        $parsed = parse_url($url);
        return isset($parsed['scheme']) && isset($parsed['host']);
    }

    /**
     * Проверяет безопасность URL (только http/https).
     *
     * @param string $url URL для проверки
     * @return bool true если URL безопасен
     */
    public static function isSafe(string $url): bool
    {
        // START_CONTRACT_isSafe
        // Intent: Проверить что URL использует только разрешенные схемы (http, https)
        // Input: string $url
        // Output: bool - true если URL безопасен
        // END_CONTRACT_isSafe
        $scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?? '');
        return in_array($scheme, self::ALLOWED_SCHEMES, true);
    }

    /**
     * Полная валидация URL (формат + безопасность).
     *
     * @param string $url URL для проверки
     * @return bool true если URL валиден и безопасен
     */
    public static function validate(string $url): bool
    {
        // START_CONTRACT_validate
        // Intent: Полная проверка URL (валидность формата и безопасность)
        // Input: string $url
        // Output: bool - true если URL валиден и безопасен
        // END_CONTRACT_validate
        return self::isValid($url) && self::isSafe($url);
    }
}
