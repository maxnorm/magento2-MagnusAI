<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Chat;

use Magnus\Assistant\Api\ActionExecutorInterface;
use Magnus\Assistant\Api\ActionRegistryInterface;
use Magnus\Assistant\Api\ConversationRepositoryInterface;
use Magnus\Assistant\Api\Data\ActionProposalInterface;
use Magnus\Assistant\Api\Data\ConversationInterface;
use Magnus\Assistant\Api\MessageRepositoryInterface;
use Magnus\Assistant\Api\ReplyResultInterface;
use Magnus\Assistant\Exception\RateLimitExceededException;
use Magnus\Assistant\Model\Action\ApprovalDetector;
use Magnus\Assistant\Model\Action\ActionProposalHandler;
use Magnus\Assistant\Model\Action\ActionResult;
use Magnus\Assistant\Model\Instance\ContextRetriever;
use Magnus\Assistant\Model\Instance\KnowledgeProvider;
use Magnus\Assistant\Model\Llm\FunctionSchemaBuilder;
use Magnus\Assistant\Model\Llm\LlmProviderInterface;
use Magnus\Assistant\Model\RateLimiter;
use Magnus\Assistant\Model\Tool\ToolExecutor;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

/**
 * Orchestrates: retrieve context, route to tool or LLM, persist messages.
 */
class ChatService
{
    private const XML_PATH_ENABLED = 'magnus/assistant/enabled';
    private const XML_PATH_PROVIDER = 'magnus/assistant/provider';
    private const XML_PATH_USE_INSTANCE_KNOWLEDGE = 'magnus/assistant/use_instance_knowledge';
    private const XML_PATH_USE_FUNCTION_CALLING = 'magnus/assistant/use_function_calling';
    private const CONVERSATION_HISTORY_LIMIT = 10;
    private const MAX_FUNCTION_CALLING_TURNS = 5;

    public function __construct(
        private readonly KnowledgeProvider $knowledgeProvider,
        private readonly ContextRetriever $contextRetriever,
        private readonly PromptBuilder $promptBuilder,
        private readonly LlmProviderInterface $llmProvider,
        private readonly ConversationRepositoryInterface $conversationRepository,
        private readonly MessageRepositoryInterface $messageRepository,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly RateLimiter $rateLimiter,
        private readonly ApprovalDetector $approvalDetector,
        private readonly ActionExecutorInterface $actionExecutor,
        private readonly ActionRegistryInterface $actionRegistry,
        private readonly FunctionSchemaBuilder $functionSchemaBuilder,
        private readonly ToolExecutor $toolExecutor,
        private readonly LlmResponseParser $responseParser,
        private readonly ActionProposalHandler $actionProposalHandler,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    /**
     * Send message and return reply. Creates conversation if needed; persists user and assistant messages.
     *
     * @param string $message
     * @param int $adminUserId
     * @param int|null $conversationId
     * @param array $context
     * @return array{reply: string, conversation_id: int, message_id: int, action_proposal: array|null, structured_data: array|null}
     */
    public function send(string $message, int $adminUserId, ?int $conversationId = null, array $context = []): array
    {
        // Check rate limit
        try {
            $this->rateLimiter->checkAndRecord($adminUserId);
        } catch (RateLimitExceededException $e) {
            return [
                'reply' => $e->getMessage(),
                'conversation_id' => $conversationId ?? 0,
                'message_id' => 0,
                'action_proposal' => null,
                'structured_data' => null,
            ];
        }

        $request = new MessageRequest($message, $context, $conversationId, $adminUserId);
        $conversation = null;

        try {
            $conversation = $this->resolveConversation($adminUserId, $conversationId);
            $this->messageRepository->create($conversation->getId(), 'user', $message);

            // Check for approval intent first
            $approvalActionId = $this->approvalDetector->detectApproval($message, $conversation->getId());
            if ($approvalActionId !== null) {
                // Execute approved action
                $result = $this->actionExecutor->execute($conversation->getId(), $approvalActionId, $adminUserId);
                $reply = $result->isSuccess()
                    ? $result->getMessage()
                    : (string) __('Action failed: %1', $result->getMessage());
                $assistantMessage = $this->messageRepository->create($conversation->getId(), 'assistant', $reply);

                $conversation->setUpdatedAt(date('Y-m-d H:i:s'));
                $this->conversationRepository->save($conversation);

                return [
                    'reply' => $reply,
                    'conversation_id' => $conversation->getId(),
                    'message_id' => (int) $assistantMessage->getId(),
                    'action_proposal' => null,
                    'structured_data' => null,
                ];
            }

            // Normal flow: route to tool or LLM (use resolved conversation ID so first message history is loaded)
            try {
                $result = $this->getReply($request, (int) $conversation->getId());
                $reply = $result->getReply();
            } catch (\Throwable $e) {
                if ($this->logger) {
                    $this->logger->error('Magnus ChatService getReply error: ' . $e->getMessage(), [
                        'exception' => $e,
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
                $reply = (string) __('I encountered an error processing your request. Please try again or rephrase your question.');
                $result = new ReplyResult($reply, null, null);
            }
            $toolContext = $result->getToolContext();
            $assistantMessage = $this->messageRepository->create(
                $conversation->getId(),
                'assistant',
                $reply,
                $toolContext
            );

            // Handle action proposal if present
            $actionProposalData = null;
            if ($result->hasActionProposal()) {
                $proposal = $result->getActionProposal();
                if ($proposal !== null) {
                    $actionId = $this->actionRegistry->storePendingAction($conversation->getId(), $proposal);
                    $actionProposalData = [
                        'action_id' => $actionId,
                        'description' => $proposal->getDescription(),
                        'preview' => $proposal->getPreview(),
                        'requires_approval' => $proposal->getAction()->requiresApproval(),
                    ];
                }
            }

            $structuredData = null;
            if ($result->hasStructuredData()) {
                $structuredData = $result->getStructuredData();
            }

            $conversation->setUpdatedAt(date('Y-m-d H:i:s'));
            $this->conversationRepository->save($conversation);

            return [
                'reply' => $reply,
                'conversation_id' => $conversation->getId(),
                'message_id' => (int) $assistantMessage->getId(),
                'action_proposal' => $actionProposalData,
                'structured_data' => $structuredData,
            ];
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('Magnus ChatService send error: ' . $e->getMessage(), [
                    'exception' => $e,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            $errorReply = trim((string) $e->getMessage()) !== ''
                ? $e->getMessage()
                : (string) __('An error occurred. Please try again.');
            if ($conversation !== null) {
                try {
                    $assistantMessage = $this->messageRepository->create($conversation->getId(), 'assistant', $errorReply);
                    $conversation->setUpdatedAt(date('Y-m-d H:i:s'));
                    $this->conversationRepository->save($conversation);
                    return [
                        'reply' => $errorReply,
                        'conversation_id' => $conversation->getId(),
                        'message_id' => (int) $assistantMessage->getId(),
                        'action_proposal' => null,
                        'structured_data' => null,
                    ];
                } catch (\Throwable $inner) {
                    if ($this->logger) {
                        $this->logger->error('Magnus ChatService failed to persist error reply: ' . $inner->getMessage());
                    }
                }
            }
            return [
                'reply' => $errorReply,
                'conversation_id' => $conversationId ?? 0,
                'message_id' => 0,
                'action_proposal' => null,
                'structured_data' => null,
            ];
        }
    }

    private function resolveConversation(int $adminUserId, ?int $conversationId): ConversationInterface
    {
        if ($conversationId !== null) {
            $existing = $this->conversationRepository->getById($conversationId);
            if ($existing !== null && $existing->getAdminUserId() === $adminUserId) {
                return $existing;
            }
        }
        return $this->conversationRepository->createForUser($adminUserId);
    }

    private function getReply(MessageRequest $request, int $resolvedConversationId): ReplyResultInterface
    {
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED)) {
            return new ReplyResult(
                (string) __('Magnus is disabled. Enable it in Stores > Configuration > Advanced > Magnus AI Assistant.')
            );
        }

        // When function calling is disabled, use simple completion (no tools). Works with any model.
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_USE_FUNCTION_CALLING)) {
            return $this->getReplyWithoutTools($request, $resolvedConversationId);
        }

        // Build context and messages (use resolved conversation ID so first message is included)
        $index = $this->knowledgeProvider->getIndex();
        $relevantContext = $this->contextRetriever->getRelevantContext($request->getMessage(), $index);
        $useKnowledge = $this->scopeConfig->isSetFlag(self::XML_PATH_USE_INSTANCE_KNOWLEDGE);

        $conversationMessages = $this->messageRepository->getByConversationId(
            $resolvedConversationId,
            self::CONVERSATION_HISTORY_LIMIT
        );

        // Build function schemas
        $tools = $this->functionSchemaBuilder->buildAllSchemas();

        // Build initial prompt
        $messages = $this->promptBuilder->build($relevantContext, $useKnowledge, $conversationMessages);

        // Function calling loop
        $finalReply = '';
        $actionProposal = null;
        $structuredData = null;
        $toolContextForPersistence = [];
        $turnCount = 0;

        while ($turnCount < self::MAX_FUNCTION_CALLING_TURNS) {
            // Call LLM with function calling
            $llmResponse = $this->llmProvider->completeWithFunctions($messages, $tools, []);

            $reply = $llmResponse['reply'] ?? '';
            $toolCalls = $llmResponse['tool_calls'] ?? [];
            $finishReason = $llmResponse['finish_reason'] ?? 'stop';

            // Accumulate reply text
            if ($reply !== '') {
                $finalReply .= ($finalReply !== '' ? "\n\n" : '') . $reply;
            }

            // If no tool calls, we're done
            if (empty($toolCalls) || $finishReason === 'stop') {
                break;
            }

            // Execute tool calls
            $toolCallMessages = [];
            foreach ($toolCalls as $toolCall) {
                $toolId = $toolCall['id'] ?? '';
                $functionName = $toolCall['function']['name'] ?? '';
                $arguments = $toolCall['function']['arguments'] ?? '';

                if ($functionName === 'propose_action') {
                    // Handle action proposal directly
                    $result = $this->actionProposalHandler->handleProposal($arguments, $resolvedConversationId);
                    if ($result['success'] && $result['proposal'] !== null) {
                        $actionProposal = $result['proposal'];
                    }
                    $toolResult = [
                        'success' => $result['success'],
                        'result' => $result['message'],
                    ];
                } else {
                    // Execute tool
                    $toolResult = $this->toolExecutor->executeTool(
                        $functionName,
                        $arguments,
                        $resolvedConversationId,
                        $request->getAdminUserId(),
                        $request->getContext()
                    );

                    // Extract structured data from tool result
                    if (isset($toolResult['structured_data'])) {
                        $structuredData = $toolResult['structured_data'];
                    }
                }

                // Add tool result to messages for next turn
                $toolCallMessages[] = [
                    'role' => 'tool',
                    'content' => $this->responseParser->formatToolResult($toolResult),
                    'tool_call_id' => $toolId,
                ];
            }

            // Add assistant message with tool calls
            if (!empty($toolCalls)) {
                $assistantShape = [
                    'role' => 'assistant',
                    'content' => $reply ?: null,
                    'tool_calls' => array_map(function ($toolCall) {
                        return [
                            'id' => $toolCall['id'] ?? '',
                            'type' => 'function',
                            'function' => [
                                'name' => $toolCall['function']['name'] ?? '',
                                'arguments' => $toolCall['function']['arguments'] ?? '',
                            ],
                        ];
                    }, $toolCalls),
                ];
                $messages[] = $assistantShape;
                $toolContextForPersistence[] = $assistantShape;
                foreach ($toolCallMessages as $tm) {
                    $toolContextForPersistence[] = [
                        'role' => 'tool',
                        'tool_call_id' => $tm['tool_call_id'] ?? '',
                        'content' => $tm['content'] ?? '',
                    ];
                }
            }

            // Add tool results
            $messages = array_merge($messages, $toolCallMessages);

            $turnCount++;
        }

        // Parse final response for action proposal if not already set
        if ($actionProposal === null && !empty($llmResponse['tool_calls'])) {
            $parsed = $this->responseParser->parse($llmResponse, $resolvedConversationId);
            $actionProposal = $parsed['action_proposal'];
        }

        $toolContext = $toolContextForPersistence !== [] ? $toolContextForPersistence : null;
        return new ReplyResult(
            $finalReply ?: 'I apologize, but I could not generate a response.',
            $actionProposal,
            $structuredData,
            $toolContext
        );
    }

    /**
     * Simple reply path without tools. Use when "Use function calling" is disabled so any model works.
     */
    private function getReplyWithoutTools(MessageRequest $request, int $resolvedConversationId): ReplyResultInterface
    {
        $index = $this->knowledgeProvider->getIndex();
        $relevantContext = $this->contextRetriever->getRelevantContext($request->getMessage(), $index);
        $useKnowledge = $this->scopeConfig->isSetFlag(self::XML_PATH_USE_INSTANCE_KNOWLEDGE);

        $conversationMessages = $this->messageRepository->getByConversationId(
            $resolvedConversationId,
            self::CONVERSATION_HISTORY_LIMIT
        );

        $messages = $this->promptBuilder->build($relevantContext, $useKnowledge, $conversationMessages);
        $reply = $this->llmProvider->complete($messages, []);

        return new ReplyResult($reply ?: (string) __('I could not generate a response. Please try again.'), null, null);
    }

    /**
     * Get conversation with messages for history endpoint.
     *
     * @param int $conversationId
     * @param int $adminUserId
     * @return array{conversation_id: int, messages: array}|null
     */
    public function getHistory(int $conversationId, int $adminUserId): ?array
    {
        $conversation = $this->conversationRepository->getById($conversationId);
        if ($conversation === null || $conversation->getAdminUserId() !== $adminUserId) {
            return null;
        }
        $messages = $this->messageRepository->getByConversationId($conversationId, null);
        $list = [];
        foreach ($messages as $msg) {
            $list[] = [
                'role' => $msg->getRole(),
                'content' => $msg->getContent(),
                'created_at' => $msg->getCreatedAt(),
            ];
        }
        return [
            'conversation_id' => $conversation->getId(),
            'messages' => $list,
        ];
    }
}
