<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Api;

use Magnus\Assistant\Model\Action\ActionResult;

/**
 * Executes approved actions.
 */
interface ActionExecutorInterface
{
    /**
     * Execute an approved action.
     *
     * @param int $conversationId
     * @param string $actionId Action ID or 'latest' for most recent
     * @param int $adminUserId Admin user executing the action
     * @return ActionResult
     */
    public function execute(int $conversationId, string $actionId, int $adminUserId): ActionResult;
}
