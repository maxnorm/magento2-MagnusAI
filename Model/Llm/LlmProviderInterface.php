<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Llm;

/**
 * LLM provider interface: send messages, get completion with or without function calling.
 */
interface LlmProviderInterface
{
    /**
     * Simple chat completion (no tools). Use when function calling is disabled.
     *
     * @param array<int, array{role: string, content: string|null}> $messages
     * @param array<string, mixed> $options
     * @return string Reply text
     */
    public function complete(array $messages, array $options = []): string;

    /**
     * Complete chat with function calling support.
     *
     * @param array<int, array{role: string, content: string|null, tool_calls?: array, tool_call_id?: string, name?: string}> $messages
     * @param array<int, array{type: string, function: array{name: string, description: string, parameters: array}}> $tools
     * @param array<string, mixed> $options
     * @return array{reply: string, tool_calls: array<int, array{id: string, type: string, function: array{name: string, arguments: string}}>, finish_reason: string}
     */
    public function completeWithFunctions(array $messages, array $tools, array $options = []): array;
}
