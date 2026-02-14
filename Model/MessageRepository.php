<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model;

use Magnus\Assistant\Api\Data\MessageInterface;
use Magnus\Assistant\Api\MessageRepositoryInterface;
use Magnus\Assistant\Model\ResourceModel\Message\CollectionFactory as MessageCollectionFactory;
use Magento\Framework\Serialize\Serializer\Json;

class MessageRepository implements MessageRepositoryInterface
{
    /**
     * @param MessageFactory $messageFactory
     * @param \Magnus\Assistant\Model\ResourceModel\Message $resource
     * @param MessageCollectionFactory $collectionFactory
     * @param Json $json
     */
    public function __construct(
        private readonly MessageFactory $messageFactory,
        private readonly \Magnus\Assistant\Model\ResourceModel\Message $resource,
        private readonly MessageCollectionFactory $collectionFactory,
        private readonly Json $json
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getByConversationId(int $conversationId, ?int $limit = null): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('conversation_id', $conversationId);
        if ($limit !== null) {
            $collection->setOrder('created_at', 'DESC');
            $collection->setPageSize($limit);
            $items = [];
            foreach ($collection as $item) {
                $items[] = $item;
            }
            return array_reverse($items);
        }
        $collection->setOrder('created_at', 'ASC');
        $items = [];
        foreach ($collection as $item) {
            $items[] = $item;
        }
        return $items;
    }

    /**
     * @inheritdoc
     */
    public function save(MessageInterface $message): MessageInterface
    {
        $this->resource->save($message);
        return $message;
    }

    /**
     * @inheritdoc
     */
    public function getFirstUserMessagePreview(int $conversationId, int $maxLength = 50): ?string
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('conversation_id', $conversationId);
        $collection->addFieldToFilter('role', 'user');
        $collection->setOrder('created_at', 'ASC');
        $collection->setPageSize(1);
        $item = $collection->getFirstItem();
        if (!$item || !$item->getId()) {
            return null;
        }
        $content = (string) $item->getData('content');
        $content = trim(preg_replace('/\s+/', ' ', $content));
        if ($maxLength > 0 && strlen($content) > $maxLength) {
            $content = substr($content, 0, $maxLength) . '…';
        }
        return $content === '' ? null : $content;
    }

    /**
     * @inheritdoc
     */
    public function create(
        int $conversationId,
        string $role,
        string $content,
        ?array $toolContext = null
    ): MessageInterface {
        $message = $this->messageFactory->create();
        $message->setConversationId($conversationId);
        $message->setRole($role);
        $message->setContent($content);
        if ($toolContext !== null && $toolContext !== []) {
            $message->setToolContext($this->json->serialize($toolContext));
        }
        $this->save($message);
        return $message;
    }
}
