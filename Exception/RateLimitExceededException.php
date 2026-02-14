<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Exception;

use Magento\Framework\Exception\LocalizedException;

/**
 * Exception thrown when rate limit is exceeded.
 */
class RateLimitExceededException extends LocalizedException
{
}
