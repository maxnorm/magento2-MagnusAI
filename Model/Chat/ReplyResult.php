<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Chat;

use Magnus\Assistant\Api\Data\ActionProposalInterface;
use Magnus\Assistant\Api\ReplyResultInterface;

/**
 * Value object for reply result with optional action proposal and tool context.
 */
class ReplyResult implements ReplyResultInterface
{
    /**
     * @param string $reply Assistant reply text
     * @param ActionProposalInterface|null $actionProposal Optional action proposal
     * @param array<string, mixed>|null $structuredData Optional structured data (tables, steps, links)
     * @param array<int, array<string, mixed>>|null $toolContext Optional tool-call/tool-result messages for persistence
     */
    public function __construct(
        private readonly string $reply,
        private readonly ?ActionProposalInterface $actionProposal = null,
        private readonly ?array $structuredData = null,
        private readonly ?array $toolContext = null
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getReply(): string
    {
        return $this->reply;
    }

    /**
     * @inheritdoc
     */
    public function getActionProposal(): ?ActionProposalInterface
    {
        return $this->actionProposal;
    }

    /**
     * @inheritdoc
     */
    public function hasActionProposal(): bool
    {
        return $this->actionProposal !== null;
    }

    /**
     * @inheritdoc
     */
    public function getStructuredData(): ?array
    {
        return $this->structuredData;
    }

    /**
     * @inheritdoc
     */
    public function hasStructuredData(): bool
    {
        return $this->structuredData !== null && !empty($this->structuredData);
    }

    /**
     * @inheritdoc
     */
    public function getToolContext(): ?array
    {
        return $this->toolContext;
    }
}
