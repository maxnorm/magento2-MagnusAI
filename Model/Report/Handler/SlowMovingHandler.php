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
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Psr\Log\LoggerInterface;

class SlowMovingHandler implements ReportHandlerInterface
{
    public function __construct(
        private readonly OrderCollectionFactory $orderCollectionFactory,
        private readonly OrderItemCollectionFactory $orderItemCollectionFactory,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function supports(string $metric): bool
    {
        return $metric === ReportSpec::METRIC_SLOW_MOVING;
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

            $soldProductIds = [];
            if (!empty($orderIds)) {
                $orderItemCollection = $this->orderItemCollectionFactory->create();
                $orderItemCollection->addFieldToFilter('order_id', ['in' => $orderIds]);
                $orderItemCollection->addFieldToSelect('product_id');
                foreach ($orderItemCollection as $item) {
                    $pid = $item->getProductId();
                    if ($pid) {
                        $soldProductIds[$pid] = true;
                    }
                }
            }

            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect(['name', 'sku']);
            $collection->addAttributeToFilter('type_id', ['neq' => 'configurable']);
            if (!empty($soldProductIds)) {
                $collection->addFieldToFilter('entity_id', ['nin' => array_keys($soldProductIds)]);
            }
            $collection->setPageSize($limit);
            $collection->setCurPage(1);

            $total = $collection->getSize();
            $rows = [];
            foreach ($collection as $product) {
                $rows[] = [
                    $product->getName() ?? 'N/A',
                    $product->getSku() ?? 'N/A',
                    (string) $product->getId(),
                ];
            }

            $reply = $total === 0
                ? "No slow-moving products (all products had sales in the last {$days} days)."
                : "Found {$total} product(s) with no sales in the last {$days} days (showing up to {$limit}):\n\n";

            return new ReportResult(
                $reply,
                [
                    'type' => 'table',
                    'table' => [
                        'headers' => ['Product', 'SKU', 'ID'],
                        'rows' => $rows,
                    ],
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error('SlowMovingHandler: ' . $e->getMessage(), ['exception' => $e]);
            return new ReportResult(
                "An error occurred while generating the report. Please try again later."
            );
        }
    }
}
