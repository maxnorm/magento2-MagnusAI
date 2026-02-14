<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Api;

/**
 * Optional interface for tools that need custom message building from function arguments.
 * When implemented, ToolExecutor uses this instead of the generic fallback.
 */
interface ToolMessageBuilderInterface
{
    /**
     * Build a context message string from the tool's function arguments.
     *
     * @param array<string, mixed> $arguments Decoded JSON arguments from the LLM function call
     * @return string Message passed to the tool's execute() as the request message
     */
    public function buildMessageFromArguments(array $arguments): string;
}
