<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model;

use Magnus\Assistant\Exception\RateLimitExceededException;
use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Rate limiter using sliding window per admin user.
 */
class RateLimiter
{
    private const XML_PATH_RATE_LIMIT = 'magnus/assistant/rate_limit_per_minute';
    private const CACHE_PREFIX = 'magnus_rate_limit_';
    private const CACHE_LIFETIME = 60; // 1 minute

    /**
     * @param CacheInterface $cache
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Check if request is allowed and record it.
     *
     * @param int $adminUserId
     * @return void
     * @throws RateLimitExceededException
     */
    public function checkAndRecord(int $adminUserId): void
    {
        $limit = (int) $this->scopeConfig->getValue(self::XML_PATH_RATE_LIMIT, 'default');
        if ($limit <= 0) {
            return; // No limit configured
        }

        $cacheKey = self::CACHE_PREFIX . $adminUserId;
        $requests = $this->getRequests($cacheKey);

        // Remove requests older than 1 minute
        $now = time();
        $requests = array_filter($requests, function ($timestamp) use ($now) {
            return ($now - $timestamp) < 60;
        });

        // Check limit
        if (count($requests) >= $limit) {
            throw new RateLimitExceededException(
                __('Rate limit exceeded. Maximum %1 requests per minute allowed.', $limit)
            );
        }

        // Record this request
        $requests[] = $now;
        $this->cache->save(
            json_encode($requests),
            $cacheKey,
            [],
            self::CACHE_LIFETIME
        );
    }

    /**
     * Get request timestamps from cache.
     *
     * @param string $cacheKey
     * @return int[]
     */
    private function getRequests(string $cacheKey): array
    {
        $cached = $this->cache->load($cacheKey);
        if ($cached === false) {
            return [];
        }

        $decoded = json_decode($cached, true);
        return is_array($decoded) ? $decoded : [];
    }
}
