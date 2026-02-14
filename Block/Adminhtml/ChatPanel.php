<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKey;

class ChatPanel extends Template
{
    /**
     * @param Context $context
     * @param FormKey $formKeyService
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly FormKey $formKeyService,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get form key for AJAX requests.
     */
    public function getFormKey(): string
    {
        return $this->formKeyService->getFormKey();
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

    /**
     * Get URL for chat conversation list API.
     */
    public function getListUrl(): string
    {
        return $this->getUrl('magnus/chat/listConversations', ['_secure' => true]);
    }
}
