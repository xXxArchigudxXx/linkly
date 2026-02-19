<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AnalyticsService;
use App\Service\AuthService;
use App\Service\LinkService;
use App\Utils\ResponseHelper;

/**
 * Handles user-specific operations.
 */
final class UserController
{
    private AuthService $authService;
    private LinkService $linkService;
    private AnalyticsService $analyticsService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->linkService = new LinkService();
        $this->analyticsService = new AnalyticsService();
    }

    public function listLinks(array $params, array $serverData): void
    {
        // START_CONTRACT_listLinks
        // Intent: List current user's links with pagination
        // Input: params (page, limit), serverData
        // Output: JSON response with paginated links
        // END_CONTRACT_listLinks
        $user = $this->authService->getCurrentUser();
        if ($user === null) {
            ResponseHelper::unauthorized();
            return;
        }

        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 20);

        $result = $this->linkService->getUserLinks($user->getId(), $page, $limit);
        ResponseHelper::success($result->toArray());
    }

    public function deleteLink(array $params, array $serverData): void
    {
        // START_CONTRACT_deleteLink
        // Intent: Delete user's link
        // Input: params (id), serverData
        // Output: JSON response with success status
        // END_CONTRACT_deleteLink
        $user = $this->authService->getCurrentUser();
        if ($user === null) {
            ResponseHelper::unauthorized();
            return;
        }

        $linkId = (int) ($params['id'] ?? 0);
        if ($linkId <= 0) {
            ResponseHelper::error('Invalid link ID', 422);
            return;
        }

        if ($this->linkService->deleteLink($user->getId(), $linkId)) {
            ResponseHelper::success(['message' => 'Link deleted']);
        } else {
            ResponseHelper::error('Link not found or not owned by you', 404);
        }
    }

    public function getStats(array $params, array $serverData): void
    {
        // START_CONTRACT_getStats
        // Intent: Get statistics for user's link
        // Input: params (id), serverData
        // Output: JSON response with link stats
        // END_CONTRACT_getStats
        $user = $this->authService->getCurrentUser();
        if ($user === null) {
            ResponseHelper::unauthorized();
            return;
        }

        $linkId = (int) ($params['id'] ?? 0);
        if ($linkId <= 0) {
            ResponseHelper::error('Invalid link ID', 422);
            return;
        }

        $stats = $this->analyticsService->getLinkStats($linkId, $user->getId());
        ResponseHelper::success($stats);
    }
}