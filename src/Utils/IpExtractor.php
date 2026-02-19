<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Извлекает IP-адрес из данных сервера.
 */
final class IpExtractor
{
    /**
     * Извлекает IP-адрес из массива $_SERVER.
     * Поддерживает X-Forwarded-For для прокси.
     *
     * @param array $serverData Данные сервера ($_SERVER)
     * @return string IP-адрес или 'unknown'
     */
    public static function fromServerData(array $serverData): string
    {
        // START_CONTRACT_fromServerData
        // Intent: Извлечь IP-адрес клиента из данных сервера (с поддержкой прокси)
        // Input: array $serverData ($_SERVER)
        // Output: string - IP-адрес или 'unknown'
        // END_CONTRACT_fromServerData
        if (!empty($serverData['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $serverData['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $serverData['REMOTE_ADDR'] ?? 'unknown';
    }
}
