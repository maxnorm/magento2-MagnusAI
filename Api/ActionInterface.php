<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Api;

use Magnus\Assistant\Model\Action\ActionResult;
use Magnus\Assistant\Model\Action\ActionPreview;

/**
 * Action interface: defines executable actions that can be proposed and approved.
 */
interface ActionInterface
{
    /**
     * Execute the action with given parameters.
     *
     * @param array<string, mixed> $params
     * @return ActionResult
     */
    public function execute(array $params): ActionResult;

    /**
     * Generate preview of what the action will do.
     *
     * @param array<string, mixed> $params
     * @return ActionPreview
     */
    public function preview(array $params): ActionPreview;

    /**
     * Validate action parameters before execution.
     *
     * @param array<string, mixed> $params
     * @return bool True if valid, false otherwise
     */
    public function validate(array $params): bool;

    /**
     * Get human-readable description of this action.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Whether this action requires merchant approval before execution.
     *
     * @return bool
     */
    public function requiresApproval(): bool;
}
