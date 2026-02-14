<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Cache\Type;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

/**
 * Cache type for Magnus instance knowledge (modules, config schema).
 * Uses default frontend when not in typeFrontendMap.
 */
class Knowledge extends TagScope
{
    public const TYPE_IDENTIFIER = 'magnus_assistant_knowledge';
    public const CACHE_TAG = 'MAGNUS_KNOWLEDGE';

    /**
     * @param FrontendPool $cacheFrontendPool
     */
    public function __construct(FrontendPool $cacheFrontendPool)
    {
        parent::__construct($cacheFrontendPool->get(self::TYPE_IDENTIFIER), self::CACHE_TAG);
    }
}
