<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Report;

/**
 * Result from a report handler for conversion to ReplyResult.
 */
class ReportResult
{
    /**
     * @param string $reply Human-readable reply text
     * @param array<string, mixed>|null $structuredData Optional table or key_value for UI
     */
    public function __construct(
        private readonly string $reply,
        private readonly ?array $structuredData = null
    ) {
    }

    public function getReply(): string
    {
        return $this->reply;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getStructuredData(): ?array
    {
        return $this->structuredData;
    }
}
