<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Report;

/**
 * Registry of metric → handler. ReportTool uses this to dispatch by metric.
 */
class ReportHandlerPool
{
    /**
     * @param ReportHandlerInterface[] $handlers
     */
    public function __construct(
        private readonly array $handlers = []
    ) {
    }

    public function get(string $metric): ?ReportHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($metric)) {
                return $handler;
            }
        }
        return null;
    }

    /**
     * @return string[]
     */
    public function getSupportedMetrics(): array
    {
        return ReportSpec::ALLOWED_METRICS;
    }
}
