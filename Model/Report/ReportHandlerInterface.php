<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Report;

/**
 * Handler for one or more report metrics. Executes safe read-only queries.
 */
interface ReportHandlerInterface
{
    /**
     * Execute the report and return a result for the tool reply.
     */
    public function execute(ReportSpec $spec): ReportResult;

    /**
     * Whether this handler supports the given metric.
     */
    public function supports(string $metric): bool;
}
