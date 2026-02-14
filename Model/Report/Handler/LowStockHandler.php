<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Report\Handler;

use Magnus\Assistant\Model\Report\ReportHandlerInterface;
use Magnus\Assistant\Model\Report\ReportResult;
use Magnus\Assistant\Model\Report\ReportSpec;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class LowStockHandler implements ReportHandlerInterface
{
    private const DEFAULT_THRESHOLD = 10;

    public function __construct(
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly ResourceConnection $resourceConnection,
        private readonly LoggerInterface $logger
    ) {
    }

    public function supports(string $metric): bool
    {
        return $metric === ReportSpec::METRIC_LOW_STOCK;
    }

    public function execute(ReportSpec $spec): ReportResult
    {
        $limit = $spec->getLimit();
        $threshold = self::DEFAULT_THRESHOLD;

        try {
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect(['name', 'sku']);
            $stockItemTable = $this->resourceConnection->getTableName('cataloginventory_stock_item');
            $collection->getSelect()->joinInner(
                ['stock_item' => $stockItemTable],
                'e.entity_id = stock_item.product_id',
                ['qty' => 'qty', 'is_in_stock' => 'is_in_stock']
            )->where('stock_item.qty <= ?', $threshold)->order('stock_item.qty ASC');
            $collection->setPageSize($limit);
            $collection->setCurPage(1);

            $total = $collection->getSize();
            $rows = [];
            foreach ($collection as $product) {
                $qty = (float) $product->getData('qty');
                $rows[] = [
                    $product->getName() ?? 'N/A',
                    $product->getSku() ?? 'N/A',
                    (string) $product->getId(),
                    (int) $qty,
                ];
            }

            $reply = $total === 0
                ? "No products with low stock (qty ≤ {$threshold})."
                : "Found {$total} product(s) with low stock, qty ≤ {$threshold} (showing up to {$limit}):\n\n";

            return new ReportResult(
                $reply,
                [
                    'type' => 'table',
                    'table' => [
                        'headers' => ['Product', 'SKU', 'ID', 'Qty'],
                        'rows' => $rows,
                    ],
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error('LowStockHandler: ' . $e->getMessage(), ['exception' => $e]);
            return new ReportResult(
                "An error occurred while generating the report. Please try again later."
            );
        }
    }
}
