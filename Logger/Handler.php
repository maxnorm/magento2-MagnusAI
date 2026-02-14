<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Log handler for Magnus Assistant module. Writes to var/log/magnus_assistant.log.
 */
class Handler extends Base
{
    /**
     * @var string
     */
    protected $fileName = 'var/log/magnus_assistant.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;
}
