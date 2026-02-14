<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Api;

/**
 * Request for a tool or LLM: message and context.
 */
interface MessageRequestInterface
{
    /**
     * User message text.
     */
    public function getMessage(): string;

    /**
     * Optional context: route, entity_type, entity_id.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array;

    /**
     * Conversation ID if continuing a thread.
     */
    public function getConversationId(): ?int;

    /**
     * Admin user ID (for ownership and rate limit).
     */
    public function getAdminUserId(): int;
}
