<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Api\Data;

/**
 * Action proposal interface: wraps an action with metadata for merchant approval.
 */
interface ActionProposalInterface
{
    /**
     * Get the action type identifier (e.g., "discount_create", "product_copy").
     *
     * @return string
     */
    public function getActionType(): string;

    /**
     * Get human-readable description of the proposed action.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get preview data (summary, details, diff) for display to merchant.
     *
     * @return array<string, mixed>
     */
    public function getPreview(): array;

    /**
     * Get conversation ID this proposal belongs to.
     *
     * @return int
     */
    public function getConversationId(): int;

    /**
     * Get action parameters to pass to execute().
     *
     * @return array<string, mixed>
     */
    public function getParams(): array;

    /**
     * Get the action instance.
     *
     * @return \Magnus\Assistant\Api\ActionInterface
     */
    public function getAction(): \Magnus\Assistant\Api\ActionInterface;
}
