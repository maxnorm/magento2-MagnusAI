<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Action;

/**
 * Result of executing an action.
 */
class ActionResult
{
    /**
     * @param bool $success Whether the action succeeded
     * @param string $message Human-readable result message
     * @param array<string, mixed> $data Additional result data
     */
    public function __construct(
        private readonly bool $success,
        private readonly string $message,
        private readonly array $data = []
    ) {
    }

    /**
     * Whether the action succeeded.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Get human-readable result message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get additional result data.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
