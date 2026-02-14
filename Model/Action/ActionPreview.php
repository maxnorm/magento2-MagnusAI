<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Action;

/**
 * Preview of what an action will do (shown to merchant before approval).
 */
class ActionPreview
{
    /**
     * @param string $summary Short summary of the action
     * @param array<string, mixed> $details Detailed information
     * @param array<string, mixed>|null $diff Diff/changes if applicable
     */
    public function __construct(
        private readonly string $summary,
        private readonly array $details = [],
        private readonly ?array $diff = null
    ) {
    }

    /**
     * Get short summary of the action.
     *
     * @return string
     */
    public function getSummary(): string
    {
        return $this->summary;
    }

    /**
     * Get detailed information about the action.
     *
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Get diff/changes if applicable (e.g., before/after values).
     *
     * @return array<string, mixed>|null
     */
    public function getDiff(): ?array
    {
        return $this->diff;
    }
}
