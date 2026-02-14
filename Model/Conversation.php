<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model;

use Magnus\Assistant\Api\Data\ConversationInterface;
use Magnus\Assistant\Model\ResourceModel\Conversation as ConversationResource;
use Magento\Framework\Model\AbstractModel;

class Conversation extends AbstractModel implements ConversationInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'magnus_assistant_conversation';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(ConversationResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        $id = parent::getId();
        return $id !== null ? (int) $id : null;
    }

    /**
     * @inheritdoc
     */
    public function setId(mixed $id): void
    {
        parent::setId($id);
    }

    /**
     * @inheritdoc
     */
    public function getAdminUserId(): int
    {
        return (int) $this->getData('admin_user_id');
    }

    /**
     * @inheritdoc
     */
    public function setAdminUserId(int $adminUserId): void
    {
        $this->setData('admin_user_id', $adminUserId);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): string
    {
        return (string) $this->getData('created_at');
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): string
    {
        return (string) $this->getData('updated_at');
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(string $updatedAt): void
    {
        $this->setData('updated_at', $updatedAt);
    }
}
