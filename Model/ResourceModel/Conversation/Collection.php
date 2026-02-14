<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\ResourceModel\Conversation;

use Magnus\Assistant\Model\Conversation as ConversationModel;
use Magnus\Assistant\Model\ResourceModel\Conversation as ConversationResource;
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
        $this->_init(ConversationModel::class, ConversationResource::class);
    }
}
