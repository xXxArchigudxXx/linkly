<?php

declare(strict_types=1);

namespace App\Repository;

use App\Utils\Logger;
use PDO;

/**
 * Records click events and retrieves aggregated statistics.
 */
final class ClickRepository
{
    private PDO $pdo;
    private Logger $logger;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
        $this->logger = Logger::getInstance();
    }

    public function create(int $linkId, array $clickData): void
    {
        // START_CONTRACT_create
        // Intent: Record click event with geo/device data
        // Input: linkId, clickData (ip, user_agent, country, city, device, browser, os)
        // Output: void
        // END_CONTRACT_create
        $this->logger->debug('[ClickRepository][create] Belief: Record click | Input: linkId=' . $linkId . ' | Expected: Click record created');

        $stmt = $this->pdo->prepare(
            'INSERT INTO clicks (link_id, ip_address, user_agent, country_code, city, device_type, browser, os, clicked_at) 
             VALUES (:link_id, :ip, :user_agent, :country, :city, :device, :browser, :os, NOW())'
        );
        $stmt->execute([
            'link_id' => $linkId,
            'ip' => $clickData['ip'] ?? null,
            'user_agent' => $clickData['user_agent'] ?? null,
            'country' => $clickData['country'] ?? null,
            'city' => $clickData['city'] ?? null,
            'device' => $clickData['device'] ?? null,
            'browser' => $clickData['browser'] ?? null,
            'os' => $clickData['os'] ?? null,
        ]);
    }

    public function countByLinkId(int $linkId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM clicks WHERE link_id = :link_id'
        );
        $stmt->execute(['link_id' => $linkId]);
        return (int) $stmt->fetchColumn();
    }

    public function getStatsByLinkId(int $linkId): array
    {
        // START_CONTRACT_getStatsByLinkId
        // Intent: Get aggregated statistics for a link
        // Input: linkId
        // Output: array with total_clicks, unique_ips, first_click, last_click
        // END_CONTRACT_getStatsByLinkId
        $stmt = $this->pdo->prepare(
            'SELECT 
                COUNT(*) as total_clicks,
                COUNT(DISTINCT ip_address) as unique_ips,
                MIN(clicked_at) as first_click,
                MAX(clicked_at) as last_click
             FROM clicks WHERE link_id = :link_id'
        );
        $stmt->execute(['link_id' => $linkId]);
        return $stmt->fetch() ?: [];
    }

    public function getGeoStats(int $linkId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT country_code, COUNT(*) as count 
             FROM clicks WHERE link_id = :link_id AND country_code IS NOT NULL 
             GROUP BY country_code ORDER BY count DESC LIMIT 10'
        );
        $stmt->execute(['link_id' => $linkId]);
        return $stmt->fetchAll();
    }

    public function getDeviceStats(int $linkId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT device_type, COUNT(*) as count 
             FROM clicks WHERE link_id = :link_id AND device_type IS NOT NULL 
             GROUP BY device_type ORDER BY count DESC'
        );
        $stmt->execute(['link_id' => $linkId]);
        return $stmt->fetchAll();
    }

    public function getBrowserStats(int $linkId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT browser, COUNT(*) as count 
             FROM clicks WHERE link_id = :link_id AND browser IS NOT NULL 
             GROUP BY browser ORDER BY count DESC LIMIT 5'
        );
        $stmt->execute(['link_id' => $linkId]);
        return $stmt->fetchAll();
    }

    public function getTimeStats(int $linkId, string $period = 'day'): array
    {
        $dateFormat = match ($period) {
            'hour' => '%Y-%m-%d %H:00',
            'day' => '%Y-%m-%d',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $stmt = $this->pdo->prepare(
            "SELECT DATE_FORMAT(clicked_at, '{$dateFormat}') as period, COUNT(*) as count 
             FROM clicks WHERE link_id = :link_id 
             GROUP BY period ORDER BY period DESC LIMIT 30"
        );
        $stmt->execute(['link_id' => $linkId]);
        return $stmt->fetchAll();
    }
}