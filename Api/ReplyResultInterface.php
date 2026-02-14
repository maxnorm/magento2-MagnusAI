<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Api;

use Magnus\Assistant\Api\Data\ActionProposalInterface;

/**
 * Result of a tool or LLM: reply text and optional action proposal.
 */
interface ReplyResultInterface
{
    /**
     * Assistant reply text.
     */
    public function getReply(): string;

    /**
     * Get action proposal if present.
     *
     * @return ActionProposalInterface|null
     */
    public function getActionProposal(): ?ActionProposalInterface;

    /**
     * Whether this result contains an action proposal.
     *
     * @return bool
     */
    public function hasActionProposal(): bool;

    /**
     * Get structured data (tables, steps, links) if present.
     *
     * @return array<string, mixed>|null Structured data with 'type' key and type-specific data
     */
    public function getStructuredData(): ?array;

    /**
     * Whether this result contains structured data.
     *
     * @return bool
     */
    public function hasStructuredData(): bool;

    /**
     * Get tool context (assistant + tool messages) to persist for multi-turn replay. Null if none.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public function getToolContext(): ?array;
}
