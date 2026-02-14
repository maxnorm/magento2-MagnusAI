<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Instance;

use Magento\Framework\Module\ModuleListInterface;

/**
 * Collects enabled module list for instance knowledge.
 */
class ModuleCollector
{
    public function __construct(
        private readonly ModuleListInterface $moduleList
    ) {
    }

    /**
     * Get enabled module names.
     *
     * @return string[]
     */
    public function getModuleNames(): array
    {
        return $this->moduleList->getNames();
    }
}
