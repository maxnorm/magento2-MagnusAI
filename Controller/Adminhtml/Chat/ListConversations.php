<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Controller\Adminhtml\Chat;

use Magnus\Assistant\Api\ConversationRepositoryInterface;
use Magnus\Assistant\Api\MessageRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class ListConversations extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Magnus_Assistant::assistant';

    private const DEFAULT_LIMIT = 20;

    private const TITLE_MAX_LENGTH = 50;

    public function __construct(
        Context $context,
        private readonly ConversationRepositoryInterface $conversationRepository,
        private readonly MessageRepositoryInterface $messageRepository
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

        $adminUserId = (int) $this->_auth->getUser()->getId();
        $conversations = $this->conversationRepository->getRecentForUser($adminUserId, self::DEFAULT_LIMIT);

        foreach ($conversations as $key => $conv) {
            $preview = $this->messageRepository->getFirstUserMessagePreview(
                (int) $conv['id'],
                self::TITLE_MAX_LENGTH
            );
            $conversations[$key]['title'] = $preview ?? '';
        }

        $result->setData(['conversations' => $conversations]);
        return $result;
    }
}
