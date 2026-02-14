<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\ResourceModel\PendingAction;

use Magnus\Assistant\Model\PendingAction;
use Magnus\Assistant\Model\ResourceModel\PendingAction as PendingActionResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(PendingAction::class, PendingActionResource::class);
    }
}
