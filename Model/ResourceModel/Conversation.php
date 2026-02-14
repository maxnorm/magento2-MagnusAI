<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Conversation extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'magnus_assistant_conversation_resource';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('magnus_assistant_conversation', 'id');
    }
}
