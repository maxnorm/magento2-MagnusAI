<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\ResourceModel\PendingAction;

use Magento\Framework\ObjectManagerInterface;

class CollectionFactory
{
    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        private readonly ObjectManagerInterface $objectManager
    ) {
    }

    /**
     * Create collection instance.
     *
     * @return Collection
     */
    public function create(): Collection
    {
        return $this->objectManager->create(Collection::class);
    }
}
