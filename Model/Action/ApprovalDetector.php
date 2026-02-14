<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Action;

use Magnus\Assistant\Api\ActionRegistryInterface;

/**
 * Detects approval intent in user messages.
 */
class ApprovalDetector
{
    /**
     * Approval keywords (case-insensitive).
     */
    private const APPROVAL_KEYWORDS = [
        'yes',
        'approve',
        'confirm',
        'go ahead',
        'do it',
        'execute',
        'ok',
        'okay',
        'sure',
        'proceed',
        'continue',
        'accept',
    ];

    /**
     * Denial keywords (case-insensitive).
     */
    private const DENIAL_KEYWORDS = [
        'no',
        'cancel',
        'abort',
        'stop',
        'reject',
        'decline',
    ];

    /**
     * @param ActionRegistryInterface $actionRegistry
     */
    public function __construct(
        private readonly ActionRegistryInterface $actionRegistry
    ) {
    }

    /**
     * Detect if message contains approval intent.
     *
     * @param string $message User message
     * @param int $conversationId Conversation ID
     * @return string|null Action ID if approved, null otherwise
     */
    public function detectApproval(string $message, int $conversationId): ?string
    {
        // Check if there's a pending action for this conversation
        $pendingAction = $this->actionRegistry->getLatestPendingAction($conversationId);
        if ($pendingAction === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($message), 'UTF-8');
        if ($normalized === '') {
            return null;
        }

        // Check for denial first
        foreach (self::DENIAL_KEYWORDS as $keyword) {
            if ($this->matchesKeyword($normalized, $keyword)) {
                return null; // Explicit denial
            }
        }

        // Check for approval
        foreach (self::APPROVAL_KEYWORDS as $keyword) {
            if ($this->matchesKeyword($normalized, $keyword)) {
                // Return 'latest' to indicate approval of the most recent pending action
                return 'latest';
            }
        }

        return null;
    }

    /**
     * Check if message matches keyword (word boundary aware).
     *
     * @param string $message Normalized message
     * @param string $keyword Keyword to match
     * @return bool
     */
    private function matchesKeyword(string $message, string $keyword): bool
    {
        // Exact match
        if ($message === $keyword) {
            return true;
        }

        // Word boundary match
        $pattern = '/\b' . preg_quote($keyword, '/') . '\b/u';
        return (bool) preg_match($pattern, $message);
    }
}
