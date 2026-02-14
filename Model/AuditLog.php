<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model;

use Magnus\Assistant\Model\ResourceModel\AuditLog as AuditLogResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Audit log model.
 */
class AuditLog extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'magnus_assistant_audit_log';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(AuditLogResource::class);
    }

    /**
     * Get admin user ID.
     *
     * @return int
     */
    public function getAdminUserId(): int
    {
        return (int) $this->getData('admin_user_id');
    }

    /**
     * Set admin user ID.
     *
     * @param int $adminUserId
     * @return void
     */
    public function setAdminUserId(int $adminUserId): void
    {
        $this->setData('admin_user_id', $adminUserId);
    }

    /**
     * Get action.
     *
     * @return string
     */
    public function getAction(): string
    {
        return (string) $this->getData('action');
    }

    /**
     * Set action.
     *
     * @param string $action
     * @return void
     */
    public function setAction(string $action): void
    {
        $this->setData('action', $action);
    }

    /**
     * Get details.
     *
     * @return string
     */
    public function getDetails(): string
    {
        return (string) $this->getData('details');
    }

    /**
     * Set details.
     *
     * @param string $details
     * @return void
     */
    public function setDetails(string $details): void
    {
        $this->setData('details', $details);
    }
}
