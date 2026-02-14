<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Report;

/**
 * Value object for report request: metric, dimensions, filters, limit.
 * Validates and normalizes allowed combinations.
 */
class ReportSpec
{
    public const METRIC_TOP_PRODUCTS_REVENUE = 'top_products_revenue';
    public const METRIC_REVENUE_AOV_ORDERS = 'revenue_aov_orders';
    public const METRIC_MISSING_IMAGES = 'missing_images';
    public const METRIC_LOW_STOCK = 'low_stock';
    public const METRIC_SLOW_MOVING = 'slow_moving';
    public const METRIC_PRODUCT_COUNT = 'product_count';

    public const ALLOWED_METRICS = [
        self::METRIC_TOP_PRODUCTS_REVENUE,
        self::METRIC_REVENUE_AOV_ORDERS,
        self::METRIC_MISSING_IMAGES,
        self::METRIC_LOW_STOCK,
        self::METRIC_SLOW_MOVING,
        self::METRIC_PRODUCT_COUNT,
    ];

    public const DEFAULT_LIMIT = 100;
    public const DEFAULT_DAYS = 30;

    private string $metric;
    /** @var string[] */
    private array $dimensions;
    /** @var array{days?: int, store_id?: int, limit?: int} */
    private array $filters;
    private int $limit;

    /**
     * @param array{metric?: string, dimensions?: string[], filters?: array{days?: int, store_id?: int, limit?: int}, limit?: int} $data
     * @return self
     * @throws \InvalidArgumentException
     */
    public static function fromArray(array $data): self
    {
        $metric = $data['metric'] ?? '';
        if ($metric === '' || !in_array($metric, self::ALLOWED_METRICS, true)) {
            throw new \InvalidArgumentException(
                'Unknown or missing metric. Allowed: ' . implode(', ', self::ALLOWED_METRICS)
            );
        }

        $dimensions = $data['dimensions'] ?? [];
        if (!is_array($dimensions)) {
            $dimensions = [];
        }

        $filters = $data['filters'] ?? [];
        if (!is_array($filters)) {
            $filters = [];
        }

        $days = isset($filters['days']) ? (int) $filters['days'] : self::DEFAULT_DAYS;
        $days = max(1, min(365, $days));
        $filters['days'] = $days;

        if (isset($filters['store_id'])) {
            $filters['store_id'] = (int) $filters['store_id'];
        }

        $limit = isset($data['limit']) ? (int) $data['limit'] : ($filters['limit'] ?? self::DEFAULT_LIMIT);
        $limit = max(1, min(self::DEFAULT_LIMIT, $limit));
        $filters['limit'] = $limit;

        return new self($metric, $dimensions, $filters, $limit);
    }

    /**
     * @param string[] $dimensions
     * @param array{days: int, store_id?: int, limit: int} $filters
     */
    private function __construct(
        string $metric,
        array $dimensions,
        array $filters,
        int $limit
    ) {
        $this->metric = $metric;
        $this->dimensions = $dimensions;
        $this->filters = $filters;
        $this->limit = $limit;
    }

    public function getMetric(): string
    {
        return $this->metric;
    }

    /**
     * @return string[]
     */
    public function getDimensions(): array
    {
        return $this->dimensions;
    }

    /**
     * @return array{days: int, store_id?: int, limit: int}
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getDays(): int
    {
        return $this->filters['days'] ?? self::DEFAULT_DAYS;
    }
}
