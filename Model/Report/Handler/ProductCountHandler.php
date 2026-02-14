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
use Magento\Framework\Module\ModuleListInterface;
use Psr\Log\LoggerInterface;

/**
 * Report handler for total product count. Answers "How many products in this store?"
 */
class ProductCountHandler implements ReportHandlerInterface
{
    public function __construct(
        private readonly ModuleListInterface $moduleList,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function supports(string $metric): bool
    {
        return $metric === ReportSpec::METRIC_PRODUCT_COUNT;
    }

    public function execute(ReportSpec $spec): ReportResult
    {
        if (!$this->moduleList->isEnabled('Magento_Catalog')) {
            return new ReportResult(
                'Catalog is not enabled on this store. Product count is not available.'
            );
        }

        try {
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect('entity_id');
            $total = $collection->getSize();

            $reply = $total === 1
                ? 'There is **1 product** in this store.'
                : "There are **{$total} products** in this store.";

            return new ReportResult(
                $reply,
                [
                    'type' => 'key_value',
                    'key_value' => [
                        'Total products' => (string) $total,
                    ],
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error('ProductCountHandler: ' . $e->getMessage(), ['exception' => $e]);
            return new ReportResult(
                'An error occurred while counting products. Please try again later.'
            );
        }
    }
}
