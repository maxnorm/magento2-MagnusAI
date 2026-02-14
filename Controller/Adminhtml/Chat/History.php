<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Controller\Adminhtml\Chat;

use Magnus\Assistant\Model\Chat\ChatService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class History extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Magnus_Assistant::assistant';

    public function __construct(
        Context $context,
        private readonly ChatService $chatService
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $conversationId = (int) $this->getRequest()->getParam('conversation_id');
        if ($conversationId <= 0) {
            $result->setHttpResponseCode(400);
            $result->setData(['error' => (string) __('conversation_id is required.')]);
            return $result;
        }

        $adminUserId = (int) $this->_auth->getUser()->getId();
        $history = $this->chatService->getHistory($conversationId, $adminUserId);

        if ($history === null) {
            $result->setHttpResponseCode(404);
            $result->setData(['error' => (string) __('Conversation not found.')]);
            return $result;
        }

        $result->setData($history);
        return $result;
    }
}
