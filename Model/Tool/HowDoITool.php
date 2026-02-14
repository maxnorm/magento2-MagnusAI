<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Tool;

use Magnus\Assistant\Api\MessageRequestInterface;
use Magnus\Assistant\Api\ReplyResultInterface;
use Magnus\Assistant\Api\ToolInterface;
use Magnus\Assistant\Api\ToolMessageBuilderInterface;
use Magnus\Assistant\Helper\AdminUrl;
use Magnus\Assistant\Model\Chat\ReplyResult;
use Magnus\Assistant\Model\Instance\KnowledgeProvider;

/**
 * Tool for providing instance-aware procedural guidance ("How do I...?").
 */
class HowDoITool implements ToolInterface, ToolMessageBuilderInterface
{
    /**
     * @var array<string, array{steps: array<int, array{text: string, link_type: string|null, path?: string}>}>
     */
    private const PROCEDURES = [
        'add product' => [
            'steps' => [
                ['text' => 'Go to Catalog > Products', 'link_type' => 'product_grid'],
                ['text' => 'Click "Add Product" button', 'link_type' => null],
                ['text' => 'Fill in product details (name, SKU, price, etc.)', 'link_type' => null],
                ['text' => 'Configure product attributes', 'link_type' => null],
                ['text' => 'Set product visibility and status', 'link_type' => null],
                ['text' => 'Save the product', 'link_type' => null],
            ],
        ],
        'create category' => [
            'steps' => [
                ['text' => 'Go to Catalog > Categories', 'link_type' => 'category_grid'],
                ['text' => 'Click "Add Root Category" or select parent category', 'link_type' => null],
                ['text' => 'Enter category name and other details', 'link_type' => null],
                ['text' => 'Configure category settings (visibility, products, etc.)', 'link_type' => null],
                ['text' => 'Save the category', 'link_type' => null],
            ],
        ],
        'configure shipping' => [
            'steps' => [
                ['text' => 'Go to Stores > Configuration', 'link_type' => 'config', 'path' => 'carriers'],
                ['text' => 'Navigate to Sales > Shipping Methods', 'link_type' => null],
                ['text' => 'Select a shipping method (e.g., Flat Rate, Table Rates)', 'link_type' => null],
                ['text' => 'Configure the shipping method settings', 'link_type' => null],
                ['text' => 'Save the configuration', 'link_type' => null],
            ],
        ],
        'configure payment' => [
            'steps' => [
                ['text' => 'Go to Stores > Configuration', 'link_type' => 'config', 'path' => 'payment'],
                ['text' => 'Navigate to Sales > Payment Methods', 'link_type' => null],
                ['text' => 'Select a payment method to configure', 'link_type' => null],
                ['text' => 'Enable and configure the payment method', 'link_type' => null],
                ['text' => 'Save the configuration', 'link_type' => null],
            ],
        ],
        'set up tax' => [
            'steps' => [
                ['text' => 'Go to Stores > Configuration', 'link_type' => 'config', 'path' => 'tax'],
                ['text' => 'Navigate to Sales > Tax', 'link_type' => null],
                ['text' => 'Configure tax calculation settings', 'link_type' => null],
                ['text' => 'Set up tax rates and rules', 'link_type' => null],
                ['text' => 'Save the configuration', 'link_type' => null],
            ],
        ],
        'manage orders' => [
            'steps' => [
                ['text' => 'Go to Sales > Orders', 'link_type' => 'order_grid'],
                ['text' => 'View, filter, or search orders', 'link_type' => null],
                ['text' => 'Click an order to view details, invoice, ship, or cancel', 'link_type' => null],
            ],
        ],
        'manage customers' => [
            'steps' => [
                ['text' => 'Go to Customers > All Customers', 'link_type' => 'customer_grid'],
                ['text' => 'Search or filter customers', 'link_type' => null],
                ['text' => 'Click a customer to view or edit details, order history, and address book', 'link_type' => null],
            ],
        ],
        'create discount' => [
            'steps' => [
                ['text' => 'Go to Marketing > Promotions > Catalog Price Rule (or Cart Price Rule)', 'link_type' => 'promo_catalog'],
                ['text' => 'Click "Add New Rule"', 'link_type' => null],
                ['text' => 'Set rule conditions (e.g. SKU, category, cart total) and discount amount', 'link_type' => null],
                ['text' => 'Set date range and save', 'link_type' => null],
            ],
        ],
        'create coupon' => [
            'steps' => [
                ['text' => 'Go to Marketing > Promotions > Cart Price Rules', 'link_type' => 'promo_cart'],
                ['text' => 'Create or edit a rule, then open "Coupon Code" section', 'link_type' => null],
                ['text' => 'Generate a coupon code or set a specific code', 'link_type' => null],
                ['text' => 'Save the rule', 'link_type' => null],
            ],
        ],
    ];

    public function __construct(
        private readonly KnowledgeProvider $knowledgeProvider,
        private readonly AdminUrl $adminUrlHelper
    ) {
    }

    /** @inheritdoc */
    public function supports(string $intent, array $context = []): bool
    {
        return $intent === 'how_do_i';
    }

    /** @inheritdoc */
    public function getToolName(): string
    {
        return 'how_do_i';
    }

    /** @inheritdoc */
    public function getToolDescription(): string
    {
        return 'Provides step-by-step procedural guidance for Magento admin tasks. Use this when users ask "how do I..." questions about performing tasks in the Magento admin panel.';
    }

    /** @inheritdoc */
    public function getToolParameters(): array
    {
        return [
            'task' => [
                'type' => 'string',
                'description' => 'The task the user wants to perform (e.g., "add product", "configure shipping", "create category", "set up tax", "configure payment")',
            ],
        ];
    }

    /** @inheritdoc */
    public function buildMessageFromArguments(array $arguments): string
    {
        $task = $arguments['task'] ?? '';
        return "How do I {$task}?";
    }

    /** @inheritdoc */
    public function getToolExamples(): array
    {
        return [
            'How do I add a product?',
            'How do I configure shipping?',
            'How do I create a category?',
            'How do I set up tax?',
        ];
    }

    /** @inheritdoc */
    public function execute(MessageRequestInterface $request): ReplyResultInterface
    {
        $message = mb_strtolower(trim($request->getMessage()), 'UTF-8');
        $task = $this->extractTask($message);

        if ($task === null) {
            return new ReplyResult(
                "I can help you with procedures like:\n" .
                "- Adding a product\n" .
                "- Creating a category\n" .
                "- Configuring shipping\n" .
                "- Configuring payment methods\n" .
                "- Setting up tax\n\n" .
                "Try asking: 'How do I add a product?'"
            );
        }

        if (!isset(self::PROCEDURES[$task])) {
            return new ReplyResult(
                "I don't have step-by-step instructions for '{$task}' yet. " .
                "Try asking about: adding products, creating categories, configuring shipping, payment, or tax."
            );
        }

        $procedure = self::PROCEDURES[$task];
        $index = $this->knowledgeProvider->getIndex();
        $steps = $this->customizeSteps($procedure['steps'], $task, $index);

        $reply = "Here's how to {$task}:\n\n";
        foreach ($steps as $idx => $step) {
            $reply .= ($idx + 1) . ". {$step['text']}\n";
        }

        $structuredSteps = [];
        foreach ($steps as $step) {
            $link = null;
            if ($step['link_type'] === 'product_grid') {
                $link = $this->adminUrlHelper->getProductUrl();
            } elseif ($step['link_type'] === 'category_grid') {
                $link = $this->adminUrlHelper->getCategoryUrl();
            } elseif ($step['link_type'] === 'config' && isset($step['path'])) {
                $link = $this->adminUrlHelper->getConfigUrl($step['path']);
            }

            $structuredSteps[] = [
                'text' => $step['text'],
                'link' => $link,
            ];
        }

        return new ReplyResult(
            $reply,
            null,
            [
                'type' => 'steps',
                'steps' => $structuredSteps,
            ]
        );
    }

    private function extractTask(string $message): ?string
    {
        $message = preg_replace('/\b(how\s+do\s+i|how\s+to|how\s+can\s+i|steps\s+to|procedure\s+for)\b/i', '', $message);
        $message = trim($message);

        foreach (array_keys(self::PROCEDURES) as $task) {
            if (str_contains($message, $task)) {
                return $task;
            }
        }

        if (preg_match('/\b(add|create|set\s+up|configure)\s+(product|category|shipping|payment|tax)\b/i', $message, $matches)) {
            $action = mb_strtolower($matches[1] ?? '', 'UTF-8');
            $object = mb_strtolower($matches[2] ?? '', 'UTF-8');

            if ($action === 'add' && $object === 'product') {
                return 'add product';
            }
            if ($action === 'create' && $object === 'category') {
                return 'create category';
            }
            if (($action === 'configure' || $action === 'set up') && $object === 'shipping') {
                return 'configure shipping';
            }
            if (($action === 'configure' || $action === 'set up') && $object === 'payment') {
                return 'configure payment';
            }
            if (($action === 'set up' || $action === 'configure') && $object === 'tax') {
                return 'set up tax';
            }
        }

        return null;
    }

    private function customizeSteps(array $steps, string $task, array $index): array
    {
        return $steps;
    }
}
