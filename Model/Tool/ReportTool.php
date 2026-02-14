<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Tool;

use Magnus\Assistant\Api\MessageRequestInterface;
use Magnus\Assistant\Api\ReplyResultInterface;
use Magnus\Assistant\Api\ToolInterface;
use Magnus\Assistant\Api\ToolMessageBuilderInterface;
use Magnus\Assistant\Model\Chat\ReplyResult;
use Magnus\Assistant\Model\Report\ReportHandlerPool;
use Magnus\Assistant\Model\Report\ReportSpec;

/**
 * Tool for executing safe read-only report queries via a semantic report spec.
 */
class ReportTool implements ToolInterface, ToolMessageBuilderInterface
{
    public function __construct(
        private readonly ReportHandlerPool $handlerPool
    ) {
    }

    /** @inheritdoc */
    public function supports(string $intent, array $context = []): bool
    {
        return $intent === 'report_query';
    }

    /** @inheritdoc */
    public function getToolName(): string
    {
        return 'run_report';
    }

    /** @inheritdoc */
    public function getToolDescription(): string
    {
        return 'Executes read-only reports and data queries. Use this when users ask for reports, analytics, '
            . 'or data summaries (e.g., top products, revenue, total product count, missing images, low stock, slow-moving products).';
    }

    /** @inheritdoc */
    public function getToolParameters(): array
    {
        $allowed = implode(', ', ReportSpec::ALLOWED_METRICS);
        return [
            'report_spec' => [
                'type' => 'object',
                'description' => 'Structured report request: metric, optional dimensions, filters, and limit.',
                'properties' => [
                    'metric' => [
                        'type' => 'string',
                        'description' => "Report metric. One of: {$allowed}",
                        'enum' => ReportSpec::ALLOWED_METRICS,
                    ],
                    'dimensions' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'Optional dimensions (e.g. product); for future use.',
                    ],
                    'filters' => [
                        'type' => 'object',
                        'description' => 'Optional filters: days (int, for date-based reports), store_id (int), limit (int).',
                        'properties' => [
                            'days' => ['type' => 'integer', 'description' => 'Days to look back (default 30)'],
                            'store_id' => ['type' => 'integer', 'description' => 'Store ID filter'],
                            'limit' => ['type' => 'integer', 'description' => 'Max results in filters'],
                        ],
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Max number of results to return (default 100, cap 100)',
                    ],
                ],
                'required' => ['metric'],
            ],
        ];
    }

    /** @inheritdoc */
    public function buildMessageFromArguments(array $arguments): string
    {
        $spec = $arguments['report_spec'] ?? $arguments;
        $metric = $spec['metric'] ?? 'top_products_revenue';
        $days = (int) ($spec['filters']['days'] ?? $spec['days'] ?? ReportSpec::DEFAULT_DAYS);
        $limit = (int) ($spec['limit'] ?? $spec['filters']['limit'] ?? 10);
        if ($metric === ReportSpec::METRIC_REVENUE_AOV_ORDERS) {
            return "Revenue, order count, and AOV last {$days} days";
        }
        if ($metric === ReportSpec::METRIC_TOP_PRODUCTS_REVENUE) {
            return "Top {$limit} products by revenue last {$days} days";
        }
        if ($metric === ReportSpec::METRIC_MISSING_IMAGES) {
            return "Products missing images (up to {$limit})";
        }
        if ($metric === ReportSpec::METRIC_LOW_STOCK) {
            return "Low stock products (up to {$limit})";
        }
        if ($metric === ReportSpec::METRIC_SLOW_MOVING) {
            return "Slow-moving products (no sales in {$days} days, up to {$limit})";
        }
        if ($metric === ReportSpec::METRIC_PRODUCT_COUNT) {
            return 'Total number of products in the store';
        }
        return "Run report: {$metric}";
    }

    /** @inheritdoc */
    public function getToolExamples(): array
    {
        return [
            'What are my top 10 products by revenue?',
            'Top 5 products last 7 days',
            'Show me revenue report',
            'What was my revenue last 7 days?',
            'AOV last 30 days',
            'Which products have no image?',
            'Products missing images',
            'Show me low stock',
            'Slow-moving products last 30 days',
            'How many products are in this store?',
            'Total product count',
        ];
    }

    /** @inheritdoc */
    public function execute(MessageRequestInterface $request): ReplyResultInterface
    {
        $args = $request->getContext()['tool_arguments'] ?? [];
        $specData = $args['report_spec'] ?? $args;

        if (empty($specData) || !is_array($specData)) {
            return new ReplyResult(
                'Please provide a report_spec with at least "metric". '
                . 'Allowed metrics: ' . implode(', ', ReportSpec::ALLOWED_METRICS)
            );
        }

        try {
            $spec = ReportSpec::fromArray($specData);
        } catch (\InvalidArgumentException $e) {
            return new ReplyResult($e->getMessage());
        }

        $handler = $this->handlerPool->get($spec->getMetric());
        if ($handler === null) {
            return new ReplyResult(
                'Unknown metric "' . $spec->getMetric() . '". Allowed: ' . implode(', ', $this->handlerPool->getSupportedMetrics())
            );
        }

        $result = $handler->execute($spec);
        return new ReplyResult(
            $result->getReply(),
            null,
            $result->getStructuredData()
        );
    }
}
