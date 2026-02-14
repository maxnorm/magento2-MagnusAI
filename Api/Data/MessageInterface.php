<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Api\Data;

/**
 * Message entity interface.
 */
interface MessageInterface
{
    /**
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * @return int
     */
    public function getConversationId(): int;

    /**
     * @param int $conversationId
     * @return void
     */
    public function setConversationId(int $conversationId): void;

    /**
     * @return string
     */
    public function getRole(): string;

    /**
     * @param string $role
     * @return void
     */
    public function setRole(string $role): void;

    /**
     * @return string
     */
    public function getContent(): string;

    /**
     * @param string $content
     * @return void
     */
    public function setContent(string $content): void;

    /**
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * Get tool context (assistant + tool messages) for replay in prompt. Null if none.
     *
     * @return string|null JSON-encoded array of message shapes
     */
    public function getToolContext(): ?string;

    /**
     * Set tool context (JSON-encoded).
     *
     * @param string|null $toolContext
     * @return void
     */
    public function setToolContext(?string $toolContext): void;
}
