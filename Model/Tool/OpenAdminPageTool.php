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

/**
 * Tool to build deep links to admin pages; returns URL + label for UI (e.g. "Open: Products").
 */
class OpenAdminPageTool implements ToolInterface, ToolMessageBuilderInterface
{
    /** Targets supported via AdminUrl helper */
    private const TARGET_ROUTES = [
        'product_list' => ['url' => 'getProductUrl', 'idParam' => null, 'label' => 'Products'],
        'product_edit' => ['url' => 'getProductUrl', 'idParam' => 'product_id', 'label' => 'Edit Product'],
        'order_list' => ['url' => 'getOrderUrl', 'idParam' => null, 'label' => 'Orders'],
        'order_view' => ['url' => 'getOrderUrl', 'idParam' => 'order_id', 'label' => 'View Order'],
        'category_list' => ['url' => 'getCategoryUrl', 'idParam' => null, 'label' => 'Categories'],
        'category_edit' => ['url' => 'getCategoryUrl', 'idParam' => 'category_id', 'label' => 'Edit Category'],
        'customer_list' => ['url' => 'getCustomerUrl', 'idParam' => null, 'label' => 'Customers'],
        'customer_edit' => ['url' => 'getCustomerUrl', 'idParam' => 'customer_id', 'label' => 'Edit Customer'],
        'config' => ['url' => 'getConfigUrl', 'idParam' => 'config_path', 'label' => 'Configuration'],
        'cms_pages' => ['url' => 'getCmsPageUrl', 'idParam' => null, 'label' => 'CMS Pages'],
        'cms_page_edit' => ['url' => 'getCmsPageUrl', 'idParam' => 'page_id', 'label' => 'Edit CMS Page'],
        'cms_blocks' => ['url' => 'getCmsBlockUrl', 'idParam' => null, 'label' => 'CMS Blocks'],
        'cms_block_edit' => ['url' => 'getCmsBlockUrl', 'idParam' => 'block_id', 'label' => 'Edit CMS Block'],
        'promo_catalog' => ['url' => 'getPromoCatalogUrl', 'idParam' => null, 'label' => 'Catalog Price Rules'],
        'promo_cart' => ['url' => 'getPromoCartUrl', 'idParam' => null, 'label' => 'Cart Price Rules'],
    ];

    public function __construct(
        private readonly AdminUrl $adminUrlHelper
    ) {
    }

    /** @inheritdoc */
    public function supports(string $intent, array $context = []): bool
    {
        return $intent === 'open_admin_page';
    }

    /** @inheritdoc */
    public function getToolName(): string
    {
        return 'open_admin_page';
    }

    /** @inheritdoc */
    public function getToolDescription(): string
    {
        return 'Builds a deep link to an admin page. Use when the user wants to open a specific admin area (products, orders, configuration, categories, customers, CMS, promotions). Returns a link the UI can display.';
    }

    /** @inheritdoc */
    public function getToolParameters(): array
    {
        return [
            'target' => [
                'type' => 'string',
                'description' => 'Admin area: product_list, product_edit, order_list, order_view, category_list, category_edit, customer_list, customer_edit, config, cms_pages, cms_page_edit, cms_blocks, cms_block_edit, promo_catalog, promo_cart',
            ],
            'id' => [
                'type' => 'integer',
                'description' => 'Optional entity ID for edit/view (product_id, order_id, category_id, customer_id, page_id, block_id)',
            ],
            'config_path' => [
                'type' => 'string',
                'description' => 'Optional config path when target is "config" (e.g. catalog/search, payment)',
            ],
        ];
    }

    /** @inheritdoc */
    public function buildMessageFromArguments(array $arguments): string
    {
        $target = $arguments['target'] ?? '';
        $id = $arguments['id'] ?? null;
        $configPath = $arguments['config_path'] ?? '';
        $msg = "Open admin page: {$target}";
        if ($id !== null) {
            $msg .= " id {$id}";
        }
        if ($configPath !== '') {
            $msg .= " config {$configPath}";
        }
        return $msg;
    }

    /** @inheritdoc */
    public function getToolExamples(): array
    {
        return [
            'Open the products page',
            'Take me to order 10001',
            'Open shipping configuration',
        ];
    }

    /** @inheritdoc */
    public function execute(MessageRequestInterface $request): ReplyResultInterface
    {
        $args = $request->getContext()['tool_arguments'] ?? [];
        $target = isset($args['target']) ? trim((string) $args['target']) : null;
        $id = isset($args['id']) ? (int) $args['id'] : null;
        $configPath = isset($args['config_path']) ? trim((string) $args['config_path']) : null;

        if ($target === null || $target === '') {
            $message = $request->getMessage();
            $target = $this->extractTarget($message);
            $id = $id ?? $this->extractId($message);
            $configPath = $configPath ?? $this->extractConfigPath($message);
        }

        if ($target === null || $target === '') {
            return new ReplyResult(
                "I can open admin pages such as: Products, Orders, Categories, Customers, Configuration, " .
                "CMS Pages, CMS Blocks, Catalog Price Rules, Cart Price Rules. " .
                "Try: 'Open the products page' or 'Take me to order 10001'."
            );
        }

        $targetKey = $this->normalizeTarget($target);
        if (!isset(self::TARGET_ROUTES[$targetKey])) {
            return new ReplyResult(
                "Unknown target '{$target}'. I can open: product_list, order_list, category_list, customer_list, " .
                "config, cms_pages, cms_blocks, promo_catalog, promo_cart (and edit/view with an ID)."
            );
        }

        $def = self::TARGET_ROUTES[$targetKey];
        $url = $this->buildUrl($def, $id, $configPath);
        $label = $def['label'];
        if ($id !== null) {
            $label .= ' #' . $id;
        }

        return new ReplyResult(
            "Here is the link to {$label}:",
            null,
            [
                'type' => 'links',
                'links' => [
                    ['text' => 'Open: ' . $label, 'url' => $url],
                ],
            ]
        );
    }

    private function extractTarget(string $message): ?string
    {
        $message = mb_strtolower($message, 'UTF-8');
        if (preg_match('/open\s+(?:the\s+)?(products?|orders?|categories?|customers?|config|configuration|cms\s+pages?|cms\s+blocks?|promo|catalog\s+price|cart\s+price)/', $message, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/take\s+me\s+to\s+(order|product|category|customer)/', $message, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function extractId(string $message): ?int
    {
        if (preg_match('/\b(?:order|product|category|customer|page|block)\s*#?\s*(\d+)/i', $message, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/\b(\d{4,})\b/', $message, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    private function extractConfigPath(string $message): ?string
    {
        if (preg_match('/config\s+(?:path\s+)?([a-z0-9_\/]+)/i', $message, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function normalizeTarget(string $target): string
    {
        $target = str_replace([' ', '-'], '_', mb_strtolower($target, 'UTF-8'));
        $map = [
            'product' => 'product_list',
            'products' => 'product_list',
            'order' => 'order_list',
            'orders' => 'order_list',
            'category' => 'category_list',
            'categories' => 'category_list',
            'customer' => 'customer_list',
            'customers' => 'customer_list',
            'configuration' => 'config',
            'cms_page' => 'cms_pages',
            'cms_pages' => 'cms_pages',
            'cms_block' => 'cms_blocks',
            'cms_blocks' => 'cms_blocks',
            'catalog_price' => 'promo_catalog',
            'cart_price' => 'promo_cart',
            'promo' => 'promo_cart',
        ];
        return $map[$target] ?? $target;
    }

    private function buildUrl(array $def, ?int $id, ?string $configPath): string
    {
        $method = $def['url'];
        $idParam = $def['idParam'] ?? null;

        if ($method === 'getConfigUrl') {
            return $this->adminUrlHelper->getConfigUrl($configPath);
        }
        if ($idParam === null) {
            return $this->adminUrlHelper->$method();
        }
        if ($id !== null) {
            return $this->adminUrlHelper->$method($id);
        }
        return $this->adminUrlHelper->$method();
    }
}
