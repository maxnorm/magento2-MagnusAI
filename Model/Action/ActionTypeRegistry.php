<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Action;

use Magnus\Assistant\Api\ActionInterface;

/**
 * Registry mapping action_type_id to ActionInterface implementation class.
 * Configured via di.xml so third-party modules can register actions.
 */
class ActionTypeRegistry
{
    /**
     * @param array<string, string> $actionMap Map of action_type_id => ActionInterface class name
     */
    public function __construct(
        private readonly array $actionMap = []
    ) {
    }

    /**
     * Get ActionInterface class for a type id, or null if not registered.
     *
     * @param string $actionTypeId
     * @return class-string<ActionInterface>|null
     */
    public function get(string $actionTypeId): ?string
    {
        $class = $this->actionMap[$actionTypeId] ?? null;
        if ($class === null || !is_string($class)) {
            return null;
        }
        if (!class_exists($class) || !is_subclass_of($class, ActionInterface::class)) {
            return null;
        }
        return $class;
    }

    /**
     * Get all registered action type ids.
     *
     * @return string[]
     */
    public function getTypeIds(): array
    {
        return array_keys($this->actionMap);
    }
}
