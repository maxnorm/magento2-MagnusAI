<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Action;

use Magnus\Assistant\Api\ActionInterface;
use Magnus\Assistant\Api\Data\ActionProposalInterface;

/**
 * Value object wrapping an action with metadata for merchant approval.
 */
class ActionProposal implements ActionProposalInterface
{
    /**
     * @param ActionInterface $action The action to execute
     * @param string $description Human-readable description
     * @param ActionPreview $preview Preview data
     * @param int $conversationId Conversation this proposal belongs to
     * @param array<string, mixed> $params Parameters to pass to execute()
     */
    public function __construct(
        private readonly ActionInterface $action,
        private readonly string $description,
        private readonly ActionPreview $preview,
        private readonly int $conversationId,
        private readonly array $params = []
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getActionType(): string
    {
        return get_class($this->action);
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @inheritdoc
     */
    public function getPreview(): array
    {
        return [
            'summary' => $this->preview->getSummary(),
            'details' => $this->preview->getDetails(),
            'diff' => $this->preview->getDiff(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getConversationId(): int
    {
        return $this->conversationId;
    }

    /**
     * @inheritdoc
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @inheritdoc
     */
    public function getAction(): ActionInterface
    {
        return $this->action;
    }
}
