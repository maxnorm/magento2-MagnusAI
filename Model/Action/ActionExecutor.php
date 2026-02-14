<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Action;

use Magnus\Assistant\Api\ActionExecutorInterface;
use Magnus\Assistant\Api\ActionRegistryInterface;
use Magnus\Assistant\Api\Data\ActionProposalInterface;
use Magnus\Assistant\Model\Action\ActionResult;
use Magnus\Assistant\Model\AuditLogger;

/**
 * Executes approved actions.
 */
class ActionExecutor implements ActionExecutorInterface
{
    /**
     * @param ActionRegistryInterface $actionRegistry
     * @param AuditLogger $auditLogger
     */
    public function __construct(
        private readonly ActionRegistryInterface $actionRegistry,
        private readonly AuditLogger $auditLogger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(int $conversationId, string $actionId, int $adminUserId): ActionResult
    {
        // Get pending action
        $proposal = null;
        if ($actionId === 'latest') {
            $proposal = $this->actionRegistry->getLatestPendingAction($conversationId);
        } else {
            $proposal = $this->actionRegistry->getPendingAction($conversationId, $actionId);
        }

        if ($proposal === null) {
            return new ActionResult(
                false,
                (string) __('No pending action found to execute.')
            );
        }

        $action = $proposal->getAction();
        $params = $proposal->getParams();

        // Validate action
        if (!$action->validate($params)) {
            return new ActionResult(
                false,
                (string) __('Action validation failed.')
            );
        }

        try {
            // Execute action
            $result = $action->execute($params);

            // Log to audit if successful
            if ($result->isSuccess()) {
                $this->auditLogger->logAction(
                    $adminUserId,
                    $proposal->getActionType(),
                    $params
                );

                // Clear pending action - use the actionId we have
                $this->actionRegistry->clearPendingAction($conversationId, $actionId);
            }

            return $result;
        } catch (\Throwable $e) {
            return new ActionResult(
                false,
                (string) __('Action execution failed: %1', $e->getMessage())
            );
        }
    }

}
