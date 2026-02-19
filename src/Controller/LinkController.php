<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Config;
use App\DTO\CreateLinkRequest;
use App\DTO\LinkResponse;
use App\Service\AnalyticsService;
use App\Service\LinkService;
use App\Service\RateLimiter;
use App\Utils\IpExtractor;
use App\Utils\JsonRequestParser;
use App\Utils\ResponseHelper;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Handles public link operations.
 */
final class LinkController
{
    use JsonRequestParser;

    private LinkService $linkService;
    private AnalyticsService $analyticsService;
    private RateLimiter $rateLimiter;
    private Config $config;

    public function __construct()
    {
        $this->linkService = new LinkService();
        $this->analyticsService = new AnalyticsService();
        $this->rateLimiter = new RateLimiter();
        $this->config = Config::getInstance();
    }

    public function create(array $params, array $serverData): void
    {
        // START_CONTRACT_create
        // Intent: Create short URL from request
        // Input: params (route params), serverData ($_SERVER, php://input)
        // Output: JSON response with short_code
        // END_CONTRACT_create
        $ip = IpExtractor::fromServerData($serverData);
        if (!$this->rateLimiter->checkLimit($ip)) {
            ResponseHelper::tooManyRequests($this->rateLimiter->getRetryAfter($ip));
            return;
        }

        $request = CreateLinkRequest::fromArray($this->parseJsonInput());

        if (!$request->isValid()) {
            ResponseHelper::error('Validation failed', 422, $request->getErrors());
            return;
        }

        try {
            $link = $this->linkService->createShortUrl(
                userId: null,
                originalUrl: $request->getUrl(),
                customAlias: $request->getCustomAlias(),
                ttl: $request->getTtl()
            );

            $response = LinkResponse::fromLink($link, $this->getBaseUrl());
            ResponseHelper::success($response->toArray());
        } catch (\Exception $e) {
            ResponseHelper::error($e->getMessage(), 400);
        }
    }

    public function show(array $params, array $serverData): void
    {
        $code = $params['code'] ?? '';
        $link = $this->linkService->getRedirectInfo($code);

        if ($link === null) {
            ResponseHelper::notFound('Link not found');
            return;
        }

        $response = LinkResponse::fromLink($link, $this->getBaseUrl());
        ResponseHelper::success($response->toArray());
    }

    public function redirect(array $params, array $serverData): void
    {
        // START_CONTRACT_redirect
        // Intent: Redirect to original URL and record click
        // Input: params (short_code), serverData
        // Output: HTTP redirect or 404
        // END_CONTRACT_redirect
        $code = $params['code'] ?? '';
        $link = $this->linkService->getRedirectInfo($code);

        if ($link === null) {
            ResponseHelper::notFound('Link not found');
            return;
        }

        // Record click (fire-and-forget)
        $this->analyticsService->recordClick($link->getId(), $serverData);

        ResponseHelper::redirect($link->getOriginalUrl(), 302);
    }

    public function qrCode(array $params, array $serverData): void
    {
        // START_CONTRACT_qrCode
        // Intent: Generate QR code for short URL
        // Input: params (short_code)
        // Output: PNG image
        // END_CONTRACT_qrCode
        $code = $params['code'] ?? '';
        $link = $this->linkService->getRedirectInfo($code);

        if ($link === null) {
            ResponseHelper::notFound('Link not found');
            return;
        }

        $shortUrl = rtrim($this->getBaseUrl(), '/') . '/' . $link->getShortCode();

        try {
            $result = Builder::create()
                ->writer(new PngWriter())
                ->writerOptions([])
                ->data($shortUrl)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                ->size($this->config->getInt('QR_CODE_SIZE', 300))
                ->margin($this->config->getInt('QR_CODE_MARGIN', 10))
                ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
                ->build();

            ResponseHelper::image($result->getString(), 'image/png');
        } catch (\Exception $e) {
            ResponseHelper::error('Failed to generate QR code', 500);
        }
    }

    /**
     * Возвращает базовый URL приложения.
     */
    private function getBaseUrl(): string
    {
        // START_CONTRACT_getBaseUrl
        // Intent: Получить базовый URL приложения из конфигурации
        // Input: нет
        // Output: string - базовый URL
        // END_CONTRACT_getBaseUrl
        return $this->config->get('APP_URL', 'http://localhost:8080');
    }
}