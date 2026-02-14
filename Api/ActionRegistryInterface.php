<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Api;

use Magnus\Assistant\Api\Data\ActionProposalInterface;

/**
 * Registry for storing and retrieving pending actions awaiting approval.
 */
interface ActionRegistryInterface
{
    /**
     * Store a pending action proposal.
     *
     * @param int $conversationId
     * @param ActionProposalInterface $proposal
     * @return string Action ID (for later retrieval)
     */
    public function storePendingAction(int $conversationId, ActionProposalInterface $proposal): string;

    /**
     * Get a pending action by ID.
     *
     * @param int $conversationId
     * @param string $actionId
     * @return ActionProposalInterface|null
     */
    public function getPendingAction(int $conversationId, string $actionId): ?ActionProposalInterface;

    /**
     * Clear/remove a pending action.
     *
     * @param int $conversationId
     * @param string $actionId
     * @return void
     */
    public function clearPendingAction(int $conversationId, string $actionId): void;

    /**
     * Get all pending actions for a conversation.
     *
     * @param int $conversationId
     * @return ActionProposalInterface[]
     */
    public function getPendingActionsForConversation(int $conversationId): array;

    /**
     * Get the most recent pending action for a conversation.
     *
     * @param int $conversationId
     * @return ActionProposalInterface|null
     */
    public function getLatestPendingAction(int $conversationId): ?ActionProposalInterface;

    /**
     * Clean up expired pending actions (older than 1 hour).
     *
     * @return int Number of actions cleaned up
     */
    public function cleanupExpired(): int;
}
