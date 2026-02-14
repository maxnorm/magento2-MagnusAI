<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class AuditLog extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'magnus_assistant_audit_log_resource';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('magnus_assistant_audit_log', 'id');
    }
}
