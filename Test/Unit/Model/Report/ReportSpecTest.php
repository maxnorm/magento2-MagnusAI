<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Test\Unit\Model\Report;

use Magnus\Assistant\Model\Report\ReportSpec;
use PHPUnit\Framework\TestCase;

class ReportSpecTest extends TestCase
{
    public function testFromArrayWithAllowedMetric(): void
    {
        $spec = ReportSpec::fromArray(['metric' => ReportSpec::METRIC_TOP_PRODUCTS_REVENUE]);
        $this->assertSame(ReportSpec::METRIC_TOP_PRODUCTS_REVENUE, $spec->getMetric());
        $this->assertSame(ReportSpec::DEFAULT_DAYS, $spec->getDays());
        $this->assertSame(ReportSpec::DEFAULT_LIMIT, $spec->getLimit());
    }

    public function testFromArrayWithFiltersAndLimit(): void
    {
        $spec = ReportSpec::fromArray([
            'metric' => ReportSpec::METRIC_REVENUE_AOV_ORDERS,
            'filters' => ['days' => 7],
            'limit' => 50,
        ]);
        $this->assertSame(7, $spec->getDays());
        $this->assertSame(50, $spec->getLimit());
    }

    public function testFromArrayRejectsUnknownMetric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown or missing metric');
        ReportSpec::fromArray(['metric' => 'invalid_metric']);
    }

    public function testFromArrayRejectsEmptyMetric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown or missing metric');
        ReportSpec::fromArray([]);
    }

    public function testFromArrayClampsDays(): void
    {
        $spec = ReportSpec::fromArray([
            'metric' => ReportSpec::METRIC_SLOW_MOVING,
            'filters' => ['days' => 999],
        ]);
        $this->assertSame(365, $spec->getDays());
    }

    public function testFromArrayClampsLimit(): void
    {
        $spec = ReportSpec::fromArray([
            'metric' => ReportSpec::METRIC_MISSING_IMAGES,
            'limit' => 500,
        ]);
        $this->assertSame(ReportSpec::DEFAULT_LIMIT, $spec->getLimit());
    }

    public function testAllAllowedMetricsCanBeParsed(): void
    {
        foreach (ReportSpec::ALLOWED_METRICS as $metric) {
            $spec = ReportSpec::fromArray(['metric' => $metric]);
            $this->assertSame($metric, $spec->getMetric());
        }
    }
}
