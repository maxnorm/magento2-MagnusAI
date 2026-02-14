<?php
/**
 * Magnus Assistant Module Registration
 *
 * @category  Magnus
 * @package   Magnus_Assistant
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Magnus_AIAssistant',
    __DIR__
);
