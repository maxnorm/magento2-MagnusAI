<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Chat;

use Magnus\Assistant\Api\Data\ActionProposalInterface;
use Magnus\Assistant\Model\Action\ActionProposalHandler;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Parses LLM responses to extract reply, tool calls, and action proposals.
 */
class LlmResponseParser
{
    /**
     * @param ActionProposalHandler $actionProposalHandler
     * @param Json $json
     */
    public function __construct(
        private readonly ActionProposalHandler $actionProposalHandler,
        private readonly Json $json
    ) {
    }

    /**
     * Parse LLM response with function calling support.
     *
     * @param array{reply: string, tool_calls: array, finish_reason: string} $llmResponse
     * @param int $conversationId
     * @return array{reply: string, tool_calls: array, action_proposal: ActionProposalInterface|null, finish_reason: string}
     */
    public function parse(array $llmResponse, int $conversationId): array
    {
        $reply = $llmResponse['reply'] ?? '';
        $toolCalls = $llmResponse['tool_calls'] ?? [];
        $finishReason = $llmResponse['finish_reason'] ?? 'stop';
        $actionProposal = null;

        // Check for action proposals in tool calls
        foreach ($toolCalls as $toolCall) {
            $functionName = $toolCall['function']['name'] ?? '';
            $arguments = $toolCall['function']['arguments'] ?? '';

            if ($functionName === 'propose_action') {
                // Handle action proposal
                $result = $this->actionProposalHandler->handleProposal($arguments, $conversationId);
                if ($result['success'] && $result['proposal'] !== null) {
                    $actionProposal = $result['proposal'];
                }
            }
        }

        return [
            'reply' => $reply,
            'tool_calls' => $toolCalls,
            'action_proposal' => $actionProposal,
            'finish_reason' => $finishReason,
        ];
    }

    /**
     * Format tool execution result for LLM.
     *
     * @param array{success: bool, result: string, structured_data?: array} $toolResult
     * @return string
     */
    public function formatToolResult(array $toolResult): string
    {
        $result = $toolResult['result'] ?? '';
        
        if (isset($toolResult['structured_data'])) {
            $structuredData = $toolResult['structured_data'];
            $result .= "\n\nStructured data: " . $this->json->serialize($structuredData);
        }

        return $result;
    }
}
