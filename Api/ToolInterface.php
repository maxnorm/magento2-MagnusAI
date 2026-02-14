<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Api;

/**
 * Tool interface: callable by the LLM via function calling.
 * Each tool exposes a name, description, parameters schema, and execute handler.
 */
interface ToolInterface
{
    /**
     * Whether this tool supports the given intent/context.
     *
     * @param string $intent Normalized or raw intent (e.g. "list_modules", or user message).
     * @param array $context Optional context (route, entity_type, entity_id, etc.).
     * @return bool
     */
    public function supports(string $intent, array $context = []): bool;

    /**
     * Execute the tool and return a reply.
     *
     * @param MessageRequestInterface $request
     * @return ReplyResultInterface
     */
    public function execute(MessageRequestInterface $request): ReplyResultInterface;

    /**
     * Get the tool name for function calling (e.g., "how_do_i", "explain_config").
     *
     * @return string
     */
    public function getToolName(): string;

    /**
     * Get the tool description for the LLM.
     *
     * @return string
     */
    public function getToolDescription(): string;

    /**
     * Get JSON schema for tool parameters.
     *
     * @return array<string, array{type: string, description: string}>
     */
    public function getToolParameters(): array;

    /**
     * Get example use cases for this tool.
     *
     * @return array<int, string>
     */
    public function getToolExamples(): array;
}
