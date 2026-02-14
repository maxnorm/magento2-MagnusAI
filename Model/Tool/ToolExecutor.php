<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Tool;

use Magnus\Assistant\Api\MessageRequestInterface;
use Magnus\Assistant\Api\ToolInterface;
use Magnus\Assistant\Api\ToolMessageBuilderInterface;
use Magnus\Assistant\Model\Chat\MessageRequest;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Executes tools when called by the LLM via function calling.
 */
class ToolExecutor
{
    /**
     * @param ToolInterface[] $tools
     * @param Json $json
     */
    public function __construct(
        private readonly array $tools,
        private readonly Json $json
    ) {
    }

    /**
     * Execute a tool by name with function arguments.
     *
     * @param string $toolName Tool name (from getToolName())
     * @param string $argumentsJson JSON string of function arguments
     * @param int $conversationId Conversation ID
     * @param int $adminUserId Admin user ID
     * @param array $context Additional context
     * @return array{success: bool, result: string, structured_data?: array}
     */
    public function executeTool(
        string $toolName,
        string $argumentsJson,
        int $conversationId,
        int $adminUserId,
        array $context = []
    ): array {
        $tool = $this->findToolByName($toolName);
        if ($tool === null) {
            return [
                'success' => false,
                'result' => "Tool '{$toolName}' not found.",
            ];
        }

        try {
            $arguments = $this->json->unserialize($argumentsJson);
            if (!is_array($arguments)) {
                $arguments = [];
            }

            $message = $this->buildMessageFromArguments($toolName, $arguments);
            $contextWithArgs = array_merge($context, ['tool_arguments' => $arguments]);
            $request = new MessageRequest($message, $contextWithArgs, $conversationId, $adminUserId);
            $result = $tool->execute($request);

            $response = [
                'success' => true,
                'result' => $result->getReply(),
            ];
            if ($result->hasStructuredData()) {
                $response['structured_data'] = $result->getStructuredData();
            }
            return $response;
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'result' => "Error executing tool '{$toolName}': " . $e->getMessage(),
            ];
        }
    }

    private function findToolByName(string $toolName): ?ToolInterface
    {
        foreach ($this->tools as $tool) {
            if ($tool->getToolName() === $toolName) {
                return $tool;
            }
        }
        return null;
    }

    /**
     * Build user message from function arguments.
     * Uses ToolMessageBuilderInterface when implemented; otherwise generic fallback.
     *
     * @param string $toolName
     * @param array<string, mixed> $arguments
     * @return string
     */
    private function buildMessageFromArguments(string $toolName, array $arguments): string
    {
        $tool = $this->findToolByName($toolName);
        if ($tool instanceof ToolMessageBuilderInterface) {
            return $tool->buildMessageFromArguments($arguments);
        }
        return $this->buildGenericMessage($toolName, $arguments);
    }

    /**
     * Generic fallback when tool does not implement ToolMessageBuilderInterface.
     *
     * @param string $toolName
     * @param array<string, mixed> $arguments
     * @return string
     */
    private function buildGenericMessage(string $toolName, array $arguments): string
    {
        if (!empty($arguments)) {
            $first = reset($arguments);
            if (is_string($first)) {
                return $first;
            }
            return $this->json->serialize($arguments);
        }
        return $toolName;
    }
}
