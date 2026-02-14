<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\ResourceModel\Message;

use Magnus\Assistant\Model\Message as MessageModel;
use Magnus\Assistant\Model\ResourceModel\Message as MessageResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(MessageModel::class, MessageResource::class);
    }
}
