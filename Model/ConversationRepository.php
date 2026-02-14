<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model;

use Magnus\Assistant\Api\ConversationRepositoryInterface;
use Magnus\Assistant\Api\Data\ConversationInterface;
use Magnus\Assistant\Model\ResourceModel\Conversation\CollectionFactory as ConversationCollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class ConversationRepository implements ConversationRepositoryInterface
{
    /**
     * @param \Magnus\Assistant\Model\ResourceModel\Conversation $resource
     * @param ConversationFactory $conversationFactory
     * @param ConversationCollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly \Magnus\Assistant\Model\ResourceModel\Conversation $resource,
        private readonly ConversationFactory $conversationFactory,
        private readonly ConversationCollectionFactory $collectionFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getById(int $conversationId): ?ConversationInterface
    {
        $conversation = $this->conversationFactory->create();
        $this->resource->load($conversation, $conversationId);
        if (!$conversation->getId()) {
            return null;
        }
        return $conversation;
    }

    /**
     * @inheritdoc
     */
    public function createForUser(int $adminUserId): ConversationInterface
    {
        $conversation = $this->conversationFactory->create();
        $conversation->setAdminUserId($adminUserId);
        $this->save($conversation);
        return $conversation;
    }

    /**
     * @inheritdoc
     */
    public function save(ConversationInterface $conversation): ConversationInterface
    {
        $this->resource->save($conversation);
        return $conversation;
    }

    /**
     * @inheritdoc
     */
    public function getRecentIdsForUser(int $adminUserId, int $limit = 20): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('admin_user_id', $adminUserId);
        $collection->setOrder('updated_at', 'DESC');
        $collection->setPageSize($limit);
        $ids = [];
        foreach ($collection as $item) {
            $ids[] = (int) $item->getId();
        }
        return $ids;
    }

    /**
     * @inheritdoc
     */
    public function getRecentForUser(int $adminUserId, int $limit = 20): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('admin_user_id', $adminUserId);
        $collection->setOrder('updated_at', 'DESC');
        $collection->setPageSize($limit);
        $result = [];
        foreach ($collection as $item) {
            $result[] = [
                'id' => (int) $item->getId(),
                'updated_at' => (string) $item->getData('updated_at'),
                'created_at' => (string) $item->getData('created_at'),
            ];
        }
        return $result;
    }
}
