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
use Psr\Log\LoggerInterface;

class MissingImagesHandler implements ReportHandlerInterface
{
    public function __construct(
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function supports(string $metric): bool
    {
        return $metric === ReportSpec::METRIC_MISSING_IMAGES;
    }

    public function execute(ReportSpec $spec): ReportResult
    {
        $limit = $spec->getLimit();

        try {
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect(['name', 'sku', 'image']);
            $collection->addAttributeToFilter(
                [
                    ['attribute' => 'image', 'null' => true],
                    ['attribute' => 'image', 'eq' => 'no_selection'],
                    ['attribute' => 'image', 'eq' => ''],
                ]
            );
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
                ? "No products are missing images."
                : "Found {$total} product(s) missing images (showing up to {$limit}):\n\n";

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
            $this->logger->error('MissingImagesHandler: ' . $e->getMessage(), ['exception' => $e]);
            return new ReportResult(
                "An error occurred while generating the report. Please try again later."
            );
        }
    }
}
