<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Api;

use Magnus\Assistant\Api\Data\MessageInterface;

/**
 * Repository for Magnus messages.
 */
interface MessageRepositoryInterface
{
    /**
     * Get messages for a conversation, optionally limited and ordered by created_at asc.
     *
     * @param int $conversationId
     * @param int|null $limit Last N messages (null = all)
     * @return MessageInterface[]
     */
    public function getByConversationId(int $conversationId, ?int $limit = null): array;

    /**
     * Save message.
     *
     * @param MessageInterface $message
     * @return MessageInterface
     */
    public function save(MessageInterface $message): MessageInterface;

    /**
     * Get first user message content for a conversation, truncated (for list title/preview).
     *
     * @param int $conversationId
     * @param int $maxLength
     * @return string|null
     */
    public function getFirstUserMessagePreview(int $conversationId, int $maxLength = 50): ?string;

    /**
     * Create a new message (user or assistant).
     *
     * @param int $conversationId
     * @param string $role
     * @param string $content
     * @param array<int, array<string, mixed>>|null $toolContext Optional tool-call/tool-result messages for replay
     * @return MessageInterface
     */
    public function create(
        int $conversationId,
        string $role,
        string $content,
        ?array $toolContext = null
    ): MessageInterface;
}
