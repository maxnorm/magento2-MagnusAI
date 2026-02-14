<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

/**
 * Block for suggested questions in chat UI.
 */
class SuggestedQuestions extends Template
{
    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get suggested questions.
     *
     * @return array<int, array{text: string, intent: string}>
     */
    public function getSuggestedQuestions(): array
    {
        return [
            [
                'text' => 'What are my top products?',
                'intent' => 'report_query',
            ],
            [
                'text' => 'How do I add a product?',
                'intent' => 'how_do_i',
            ],
            [
                'text' => 'How do I configure shipping?',
                'intent' => 'how_do_i',
            ],
            [
                'text' => 'What does catalog search do?',
                'intent' => 'config_explanation',
            ],
            [
                'text' => 'How do I create a discount or coupon?',
                'intent' => 'how_do_i',
            ],
            [
                'text' => 'Where do I manage orders?',
                'intent' => 'how_do_i',
            ],
            [
                'text' => 'What modules are installed?',
                'intent' => 'list_modules',
            ],
        ];
    }
}
