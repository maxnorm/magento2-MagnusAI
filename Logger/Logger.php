<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Logger;

use Monolog\Logger as MonologLogger;

/**
 * Magnus Assistant dedicated logger. Implements PSR-3 LoggerInterface via Monolog.
 */
class Logger extends MonologLogger
{
}
