<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Test\Unit\Model;

use Magnus\Assistant\Exception\RateLimitExceededException;
use Magnus\Assistant\Model\RateLimiter;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class RateLimiterTest extends TestCase
{
    /**
     * @var CacheInterface|MockObject
     */
    private $cache;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var RateLimiter
     */
    private $rateLimiter;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->rateLimiter = new RateLimiter($this->cache, $this->scopeConfig);
    }

    public function testCheckAndRecordNoLimit(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('magnus/assistant/rate_limit_per_minute', 'default')
            ->willReturn(0);

        // Should not throw exception
        $this->rateLimiter->checkAndRecord(1);
    }

    public function testCheckAndRecordWithinLimit(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('magnus/assistant/rate_limit_per_minute', 'default')
            ->willReturn(60);

        $this->cache->expects($this->exactly(2))
            ->method('load')
            ->willReturn(false); // No existing requests

        $this->cache->expects($this->once())
            ->method('save')
            ->with(
                $this->isType('string'),
                $this->stringContains('magnus_rate_limit_1'),
                [],
                60
            );

        // Should not throw exception
        $this->rateLimiter->checkAndRecord(1);
    }

    public function testCheckAndRecordExceedsLimit(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('magnus/assistant/rate_limit_per_minute', 'default')
            ->willReturn(2);

        // Simulate 2 requests already in cache
        $requests = json_encode([time(), time()]);
        $this->cache->expects($this->once())
            ->method('load')
            ->willReturn($requests);

        $this->expectException(RateLimitExceededException::class);
        $this->rateLimiter->checkAndRecord(1);
    }
}
