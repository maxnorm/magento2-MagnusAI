<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LlmProvider implements OptionSourceInterface
{
    public const OPENAI = 'openai';
    public const OPENROUTER = 'openrouter';

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::OPENAI, 'label' => __('OpenAI (GPT)')],
            ['value' => self::OPENROUTER, 'label' => __('OpenRouter (Free Models Available)')],
        ];
    }
}
