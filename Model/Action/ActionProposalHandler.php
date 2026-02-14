<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Action;

use Magnus\Assistant\Api\ActionInterface;
use Magnus\Assistant\Api\Data\ActionProposalInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * Handles action proposals from LLM function calls.
 */
class ActionProposalHandler
{
    /**
     * @param ObjectManagerInterface $objectManager
     * @param Json $json
     * @param LoggerInterface $logger
     * @param ActionTypeRegistry $actionTypeRegistry
     */
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly Json $json,
        private readonly LoggerInterface $logger,
        private readonly ActionTypeRegistry $actionTypeRegistry
    ) {
    }

    /**
     * Handle action proposal from function call.
     *
     * @param string $argumentsJson JSON string of function arguments
     * @param int $conversationId Conversation ID
     * @return array{success: bool, proposal: ActionProposalInterface|null, message: string}
     */
    public function handleProposal(string $argumentsJson, int $conversationId): array
    {
        try {
            // Parse function arguments
            $arguments = $this->json->unserialize($argumentsJson);
            if (!is_array($arguments)) {
                return [
                    'success' => false,
                    'proposal' => null,
                    'message' => 'Invalid arguments format.',
                ];
            }

            $actionType = $arguments['action_type'] ?? '';
            $params = $arguments['params'] ?? [];
            $description = $arguments['description'] ?? '';
            $previewData = $arguments['preview_data'] ?? [];

            if (empty($actionType) || empty($description)) {
                return [
                    'success' => false,
                    'proposal' => null,
                    'message' => 'Missing required fields: action_type and description are required.',
                ];
            }

            // Try to instantiate action
            $action = $this->instantiateAction($actionType);
            if ($action === null) {
                return [
                    'success' => false,
                    'proposal' => null,
                    'message' => "Action type '{$actionType}' is not available. Available actions will be listed in the system prompt.",
                ];
            }

            // Validate parameters
            if (!$action->validate($params)) {
                return [
                    'success' => false,
                    'proposal' => null,
                    'message' => 'Action validation failed. Please check the parameters.',
                ];
            }

            // Generate preview
            $preview = $action->preview($params);

            // Merge LLM-provided preview data if available
            if (!empty($previewData)) {
                $preview = new ActionPreview(
                    $previewData['summary'] ?? $preview->getSummary(),
                    array_merge($preview->getDetails(), $previewData['details'] ?? []),
                    $previewData['diff'] ?? $preview->getDiff()
                );
            }

            // Create proposal
            $proposal = new ActionProposal(
                $action,
                $description,
                $preview,
                $conversationId,
                $params
            );

            return [
                'success' => true,
                'proposal' => $proposal,
                'message' => 'Action proposal created successfully.',
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Magnus: Action proposal handling failed: ' . $e->getMessage());
            return [
                'success' => false,
                'proposal' => null,
                'message' => 'Failed to create action proposal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Instantiate action by type (registry type id or full class name).
     *
     * @param string $actionType Action type id (e.g. product_create) or ActionInterface class name
     * @return ActionInterface|null
     */
    private function instantiateAction(string $actionType): ?ActionInterface
    {
        $class = $this->actionTypeRegistry->get($actionType);
        if ($class === null && class_exists($actionType) && is_subclass_of($actionType, ActionInterface::class)) {
            $class = $actionType;
        }
        if ($class === null) {
            return null;
        }
        try {
            return $this->objectManager->get($class);
        } catch (\Throwable $e) {
            $this->logger->warning("Magnus: Could not instantiate action {$actionType}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get available action types for LLM (type id + description for prompt).
     *
     * @return array<int, array{type: string, description: string}>
     */
    public function getAvailableActions(): array
    {
        $result = [];
        foreach ($this->actionTypeRegistry->getTypeIds() as $typeId) {
            $action = $this->instantiateAction($typeId);
            if ($action !== null) {
                $result[] = [
                    'type' => $typeId,
                    'description' => $action->getDescription(),
                ];
            }
        }
        return $result;
    }
}
