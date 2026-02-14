<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model;

use Magnus\Assistant\Model\ResourceModel\AuditLog as AuditLogResource;
use Magnus\Assistant\Model\AuditLogFactory;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Helper for logging actions to audit log.
 */
class AuditLogger
{
    /**
     * @param AuditLogFactory $auditLogFactory
     * @param AuditLogResource $resource
     * @param Json $json
     */
    public function __construct(
        private readonly AuditLogFactory $auditLogFactory,
        private readonly AuditLogResource $resource,
        private readonly Json $json
    ) {
    }

    /**
     * Log an executed action.
     *
     * @param int $adminUserId
     * @param string $actionType
     * @param array<string, mixed> $params
     * @return void
     */
    public function logAction(int $adminUserId, string $actionType, array $params): void
    {
        $auditLog = $this->auditLogFactory->create();
        $auditLog->setAdminUserId($adminUserId);
        $auditLog->setAction($actionType);
        $auditLog->setDetails($this->json->serialize($params));

        $this->resource->save($auditLog);
    }
}
