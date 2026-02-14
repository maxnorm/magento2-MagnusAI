<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model;

use Magnus\Assistant\Api\Data\MessageInterface;
use Magnus\Assistant\Model\ResourceModel\Message as MessageResource;
use Magento\Framework\Model\AbstractModel;

class Message extends AbstractModel implements MessageInterface
{
    public const ROLE_USER = 'user';
    public const ROLE_ASSISTANT = 'assistant';

    /**
     * @var string
     */
    protected $_eventPrefix = 'magnus_assistant_message';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(MessageResource::class);
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
    public function getConversationId(): int
    {
        return (int) $this->getData('conversation_id');
    }

    /**
     * @inheritdoc
     */
    public function setConversationId(int $conversationId): void
    {
        $this->setData('conversation_id', $conversationId);
    }

    /**
     * @inheritdoc
     */
    public function getRole(): string
    {
        return (string) $this->getData('role');
    }

    /**
     * @inheritdoc
     */
    public function setRole(string $role): void
    {
        $this->setData('role', $role);
    }

    /**
     * @inheritdoc
     */
    public function getContent(): string
    {
        return (string) $this->getData('content');
    }

    /**
     * @inheritdoc
     */
    public function setContent(string $content): void
    {
        $this->setData('content', $content);
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
    public function getToolContext(): ?string
    {
        $v = $this->getData('tool_context');
        return $v === null || $v === '' ? null : (string) $v;
    }

    /**
     * @inheritdoc
     */
    public function setToolContext(?string $toolContext): void
    {
        $this->setData('tool_context', $toolContext);
    }
}
