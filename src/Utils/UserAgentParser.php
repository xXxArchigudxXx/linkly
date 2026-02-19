<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Parses User-Agent string for device, browser, and OS information.
 */
final class UserAgentParser
{
    /**
     * Parses device type from User-Agent string.
     *
     * @param string $userAgent User-Agent header value
     * @return string Device type: 'mobile', 'tablet', or 'desktop'
     */
    public static function parseDevice(string $userAgent): string
    {
        // START_CONTRACT_parseDevice
        // Intent: Determine device type from User-Agent
        // Input: string $userAgent
        // Output: string - 'mobile', 'tablet', or 'desktop'
        // END_CONTRACT_parseDevice
        $ua = strtolower($userAgent);
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android')) {
            return 'mobile';
        }
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'tablet';
        }
        return 'desktop';
    }

    /**
     * Parses browser name from User-Agent string.
     *
     * @param string $userAgent User-Agent header value
     * @return string Browser name: 'Firefox', 'Edge', 'Chrome', 'Safari', 'Opera', or 'Other'
     */
    public static function parseBrowser(string $userAgent): string
    {
        // START_CONTRACT_parseBrowser
        // Intent: Determine browser name from User-Agent
        // Input: string $userAgent
        // Output: string - browser name or 'Other'
        // END_CONTRACT_parseBrowser
        $ua = strtolower($userAgent);
        if (str_contains($ua, 'firefox')) {
            return 'Firefox';
        }
        if (str_contains($ua, 'edg/')) {
            return 'Edge';
        }
        if (str_contains($ua, 'chrome')) {
            return 'Chrome';
        }
        if (str_contains($ua, 'safari')) {
            return 'Safari';
        }
        if (str_contains($ua, 'opera') || str_contains($ua, 'opr/')) {
            return 'Opera';
        }
        return 'Other';
    }

    /**
     * Parses operating system from User-Agent string.
     *
     * @param string $userAgent User-Agent header value
     * @return string OS name: 'Windows', 'macOS', 'Linux', 'Android', 'iOS', or 'Other'
     */
    public static function parseOs(string $userAgent): string
    {
        // START_CONTRACT_parseOs
        // Intent: Determine operating system from User-Agent
        // Input: string $userAgent
        // Output: string - OS name or 'Other'
        // END_CONTRACT_parseOs
        $ua = strtolower($userAgent);
        if (str_contains($ua, 'windows')) {
            return 'Windows';
        }
        if (str_contains($ua, 'mac os') || str_contains($ua, 'macos')) {
            return 'macOS';
        }
        if (str_contains($ua, 'linux')) {
            return 'Linux';
        }
        if (str_contains($ua, 'android')) {
            return 'Android';
        }
        if (str_contains($ua, 'iphone') || str_contains($ua, 'ios')) {
            return 'iOS';
        }
        return 'Other';
    }

    /**
     * Parses all User-Agent components at once.
     *
     * @param string $userAgent User-Agent header value
     * @return array{device: string, browser: string, os: string}
     */
    public static function parseAll(string $userAgent): array
    {
        // START_CONTRACT_parseAll
        // Intent: Parse all User-Agent components in single call
        // Input: string $userAgent
        // Output: array with device, browser, os keys
        // END_CONTRACT_parseAll
        return [
            'device' => self::parseDevice($userAgent),
            'browser' => self::parseBrowser($userAgent),
            'os' => self::parseOs($userAgent),
        ];
    }
}