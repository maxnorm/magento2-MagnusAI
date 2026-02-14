<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Chat;

use Magnus\Assistant\Api\MessageRequestInterface;

/**
 * Simple value object for message request.
 */
class MessageRequest implements MessageRequestInterface
{
    public function __construct(
        private readonly string $message,
        private readonly array $context,
        private readonly ?int $conversationId,
        private readonly int $adminUserId
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @inheritdoc
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @inheritdoc
     */
    public function getConversationId(): ?int
    {
        return $this->conversationId;
    }

    /**
     * @inheritdoc
     */
    public function getAdminUserId(): int
    {
        return $this->adminUserId;
    }
}
