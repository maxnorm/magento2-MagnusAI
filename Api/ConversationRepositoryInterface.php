<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Api;

use Magnus\Assistant\Api\Data\ConversationInterface;

/**
 * Repository for Magnus conversations.
 */
interface ConversationRepositoryInterface
{
    /**
     * Get conversation by id. Caller must verify admin_user_id ownership.
     *
     * @param int $conversationId
     * @return ConversationInterface|null
     */
    public function getById(int $conversationId): ?ConversationInterface;

    /**
     * Create a new conversation for the given admin user.
     *
     * @param int $adminUserId
     * @return ConversationInterface
     */
    public function createForUser(int $adminUserId): ConversationInterface;

    /**
     * Save conversation.
     *
     * @param ConversationInterface $conversation
     * @return ConversationInterface
     */
    public function save(ConversationInterface $conversation): ConversationInterface;

    /**
     * Get recent conversation ids for admin user (for history list).
     *
     * @param int $adminUserId
     * @param int $limit
     * @return int[]
     */
    public function getRecentIdsForUser(int $adminUserId, int $limit = 20): array;

    /**
     * Get recent conversations for admin user with id and dates (for list endpoint).
     *
     * @param int $adminUserId
     * @param int $limit
     * @return array<int, array{id: int, updated_at: string, created_at: string}>
     */
    public function getRecentForUser(int $adminUserId, int $limit = 20): array;
}
