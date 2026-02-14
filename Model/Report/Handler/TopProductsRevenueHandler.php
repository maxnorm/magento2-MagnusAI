<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Report\Handler;

use Magnus\Assistant\Model\Report\ReportHandlerInterface;
use Magnus\Assistant\Model\Report\ReportResult;
use Magnus\Assistant\Model\Report\ReportSpec;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Psr\Log\LoggerInterface;

class TopProductsRevenueHandler implements ReportHandlerInterface
{
    public function __construct(
        private readonly OrderCollectionFactory $orderCollectionFactory,
        private readonly OrderItemCollectionFactory $orderItemCollectionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function supports(string $metric): bool
    {
        return $metric === ReportSpec::METRIC_TOP_PRODUCTS_REVENUE;
    }

    public function execute(ReportSpec $spec): ReportResult
    {
        $limit = $spec->getLimit();
        $days = $spec->getDays();
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        try {
            $orderCollection = $this->orderCollectionFactory->create();
            $orderCollection->addFieldToFilter('created_at', ['gteq' => $startDate]);
            $orderIds = $orderCollection->getAllIds();

            if (empty($orderIds)) {
                return new ReportResult(
                    "No orders found in the last {$days} days.",
                    [
                        'type' => 'table',
                        'table' => [
                            'headers' => ['Product', 'SKU', 'Revenue', 'Quantity'],
                            'rows' => [],
                        ],
                    ]
                );
            }

            $orderItemCollection = $this->orderItemCollectionFactory->create();
            $orderItemCollection->addFieldToFilter('order_id', ['in' => $orderIds]);
            $orderItemCollection->addFieldToSelect(['product_id', 'sku', 'name', 'base_row_total', 'qty_ordered']);

            $productData = [];
            foreach ($orderItemCollection as $item) {
                $productId = $item->getProductId();
                if (!$productId) {
                    continue;
                }
                if (!isset($productData[$productId])) {
                    $productData[$productId] = [
                        'product_id' => $productId,
                        'sku' => $item->getSku(),
                        'name' => $item->getName(),
                        'revenue' => 0,
                        'quantity' => 0,
                    ];
                }
                $productData[$productId]['revenue'] += (float) $item->getBaseRowTotal();
                $productData[$productId]['quantity'] += (float) $item->getQtyOrdered();
            }

            usort($productData, fn(array $a, array $b): int => $b['revenue'] <=> $a['revenue']);
            $productData = array_slice($productData, 0, $limit);

            $rows = [];
            foreach ($productData as $data) {
                $rows[] = [
                    $data['name'] ?? 'N/A',
                    $data['sku'] ?? 'N/A',
                    '$' . number_format($data['revenue'], 2),
                    (int) $data['quantity'],
                ];
            }

            $reply = "Top {$limit} products by revenue (last {$days} days):\n\n";
            return new ReportResult(
                $reply,
                [
                    'type' => 'table',
                    'table' => [
                        'headers' => ['Product', 'SKU', 'Revenue', 'Quantity'],
                        'rows' => $rows,
                    ],
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error('TopProductsRevenueHandler: ' . $e->getMessage(), ['exception' => $e]);
            return new ReportResult(
                "An error occurred while generating the report. Please try again later."
            );
        }
    }
}
