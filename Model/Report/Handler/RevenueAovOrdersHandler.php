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
use Psr\Log\LoggerInterface;

class RevenueAovOrdersHandler implements ReportHandlerInterface
{
    public function __construct(
        private readonly OrderCollectionFactory $orderCollectionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function supports(string $metric): bool
    {
        return $metric === ReportSpec::METRIC_REVENUE_AOV_ORDERS;
    }

    public function execute(ReportSpec $spec): ReportResult
    {
        $days = $spec->getDays();
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        try {
            $orderCollection = $this->orderCollectionFactory->create();
            $orderCollection->addFieldToFilter('created_at', ['gteq' => $startDate]);
            $orderCollection->addFieldToSelect(['entity_id', 'base_grand_total']);

            $orderCount = $orderCollection->getSize();
            $revenue = 0.0;
            foreach ($orderCollection as $order) {
                $revenue += (float) $order->getBaseGrandTotal();
            }
            $aov = $orderCount > 0 ? $revenue / $orderCount : 0.0;

            $reply = "Last {$days} days: Revenue $" . number_format($revenue, 2)
                . ", Orders " . $orderCount
                . ", AOV $" . number_format($aov, 2);
            if ($orderCount === 0) {
                $reply = "No orders found in the last {$days} days.";
            }

            return new ReportResult(
                $reply,
                [
                    'type' => 'key_value',
                    'revenue' => $revenue,
                    'order_count' => $orderCount,
                    'aov' => round($aov, 2),
                    'days' => $days,
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error('RevenueAovOrdersHandler: ' . $e->getMessage(), ['exception' => $e]);
            return new ReportResult(
                "An error occurred while generating the report. Please try again later."
            );
        }
    }
}
