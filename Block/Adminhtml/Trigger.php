<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class Trigger extends Template
{
    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get URL for chat send API.
     */
    public function getSendUrl(): string
    {
        return $this->getUrl('magnus/chat/send', ['_secure' => true]);
    }

    /**
     * Get URL for chat history API.
     */
    public function getHistoryUrl(): string
    {
        return $this->getUrl('magnus/chat/history', ['_secure' => true]);
    }
}
