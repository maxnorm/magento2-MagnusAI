<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Api\Data;

/**
 * Conversation entity interface.
 */
interface ConversationInterface
{
    /**
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * @param int|string|null $id
     * @return void
     */
    public function setId(mixed $id): void;

    /**
     * @return int
     */
    public function getAdminUserId(): int;

    /**
     * @param int $adminUserId
     * @return void
     */
    public function setAdminUserId(int $adminUserId): void;

    /**
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * @return string
     */
    public function getUpdatedAt(): string;

    /**
     * @param string $updatedAt
     * @return void
     */
    public function setUpdatedAt(string $updatedAt): void;
}
