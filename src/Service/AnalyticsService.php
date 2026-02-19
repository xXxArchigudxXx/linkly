<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\Config;
use App\Repository\ClickRepository;
use App\Repository\RedisClient;
use App\Utils\IpExtractor;
use App\Utils\Logger;
use App\Utils\UserAgentParser;
use GeoIp2\Database\Reader;

/**
 * Records click events with geo/device data.
 * Provides aggregated statistics with caching.
 */
final class AnalyticsService
{
    private ClickRepository $clickRepository;
    private Logger $logger;
    private Config $config;
    private ?Reader $geoReader = null;

    public function __construct()
    {
        $this->clickRepository = new ClickRepository();
        $this->logger = Logger::getInstance();
        $this->config = Config::getInstance();
        $this->initGeoReader();
    }

    public function recordClick(int $linkId, array $serverData): void
    {
        // START_CONTRACT_recordClick
        // Intent: Record click event with geo/device data (fire-and-forget)
        // Input: linkId, serverData ($_SERVER)
        // Output: void
        // END_CONTRACT_recordClick
        $this->logger->debug('[AnalyticsService][recordClick] Belief: Record click | Input: linkId=' . $linkId . ' | Expected: Click record created');

        $userAgent = $serverData['HTTP_USER_AGENT'] ?? '';
        $ip = IpExtractor::fromServerData($serverData);
        $uaData = UserAgentParser::parseAll($userAgent);

        $clickData = [
            'ip' => $ip,
            'user_agent' => $userAgent,
            'device' => $uaData['device'],
            'browser' => $uaData['browser'],
            'os' => $uaData['os'],
        ];

        $geoData = $this->resolveGeo($ip);
        $clickData['country'] = $geoData['country'] ?? null;
        $clickData['city'] = $geoData['city'] ?? null;

        $this->clickRepository->create($linkId, $clickData);
        $this->invalidateStatsCache($linkId);
    }

    public function getLinkStats(int $linkId, int $userId): array
    {
        // START_CONTRACT_getLinkStats
        // Intent: Get aggregated statistics for a link (with caching)
        // Input: linkId, userId
        // Output: array with stats
        // END_CONTRACT_getLinkStats
        $cacheKey = "stats:link:{$linkId}";
        $cached = RedisClient::get($cacheKey);

        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $stats = [
            'link_id' => $linkId,
            'totals' => $this->clickRepository->getStatsByLinkId($linkId),
            'geo' => $this->clickRepository->getGeoStats($linkId),
            'devices' => $this->clickRepository->getDeviceStats($linkId),
            'browsers' => $this->clickRepository->getBrowserStats($linkId),
            'timeline' => $this->clickRepository->getTimeStats($linkId),
        ];

        $cacheTtl = $this->config->getInt('STATS_CACHE_TTL', 300);
        RedisClient::set($cacheKey, json_encode($stats), $cacheTtl);

        return $stats;
    }

    public function parseUserAgent(string $userAgent): array
    {
        return UserAgentParser::parseAll($userAgent);
    }

    public function resolveGeo(?string $ip): array
    {
        // START_CONTRACT_resolveGeo
        // Intent: Resolve geo data from IP using MaxMind
        // Input: ip
        // Output: array with country, city
        // END_CONTRACT_resolveGeo
        if ($ip === null || $ip === 'unknown' || $this->geoReader === null) {
            return [];
        }

        try {
            $record = $this->geoReader->city($ip);
            return [
                'country' => $record->country->isoCode,
                'city' => $record->city->name,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    private function initGeoReader(): void
    {
        $geoDbPath = $this->config->get('GEOIP_DB_PATH');
        if ($geoDbPath === null || $geoDbPath === '') {
            $geoDbPath = dirname(__DIR__, 2) . '/data/GeoLite2-City.mmdb';
        }

        if (file_exists($geoDbPath)) {
            try {
                $this->geoReader = new Reader($geoDbPath);
            } catch (\Exception $e) {
                $this->logger->warning('[AnalyticsService] GeoIP database not loaded: ' . $e->getMessage());
            }
        }
    }

    private function invalidateStatsCache(int $linkId): void
    {
        RedisClient::del("stats:link:{$linkId}");
    }
}