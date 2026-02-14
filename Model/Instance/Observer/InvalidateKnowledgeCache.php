<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Instance\Observer;

use Magnus\Assistant\Model\Instance\DiscoveryProvider;
use Magnus\Assistant\Model\Instance\KnowledgeProvider;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Invalidates Magnus instance knowledge and discovery cache when Magnus config section is saved.
 */
class InvalidateKnowledgeCache implements ObserverInterface
{
    public function __construct(
        private readonly KnowledgeProvider $knowledgeProvider,
        private readonly DiscoveryProvider $discoveryProvider
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        $this->knowledgeProvider->invalidate();
        $this->discoveryProvider->invalidate();
    }
}
