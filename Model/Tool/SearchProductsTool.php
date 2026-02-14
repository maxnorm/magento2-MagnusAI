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
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Module\ModuleListInterface;

/**
 * Read-only product search by name or SKU. Guarded by Magento_Catalog.
 */
class SearchProductsTool implements ToolInterface, ToolMessageBuilderInterface
{
    private const DEFAULT_LIMIT = 10;
    private const MAX_LIMIT = 50;

    public function __construct(
        private readonly ModuleListInterface $moduleList,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly AdminUrl $adminUrlHelper
    ) {
    }

    /** @inheritdoc */
    public function supports(string $intent, array $context = []): bool
    {
        return $intent === 'search_products';
    }

    /** @inheritdoc */
    public function getToolName(): string
    {
        return 'search_products';
    }

    /** @inheritdoc */
    public function getToolDescription(): string
    {
        return 'Search products by name or SKU (read-only). Use when the user wants to find products, then you can open the product edit page with open_admin_page. Returns product id, name, SKU.';
    }

    /** @inheritdoc */
    public function getToolParameters(): array
    {
        return [
            'query' => [
                'type' => 'string',
                'description' => 'Search query (product name or SKU, partial match)',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Maximum number of results (default 10, max 50)',
            ],
        ];
    }

    /** @inheritdoc */
    public function buildMessageFromArguments(array $arguments): string
    {
        $query = $arguments['query'] ?? '';
        return $query !== '' ? "Search products: {$query}" : 'Search products';
    }

    /** @inheritdoc */
    public function getToolExamples(): array
    {
        return [
            'Find products named shirt',
            'Search for SKU containing ABC',
        ];
    }

    /** @inheritdoc */
    public function execute(MessageRequestInterface $request): ReplyResultInterface
    {
        if (!$this->moduleList->isEnabled('Magento_Catalog')) {
            return new ReplyResult('Catalog is not enabled on this store. Product search is not available.');
        }

        $args = $request->getContext()['tool_arguments'] ?? [];
        $query = isset($args['query']) ? trim((string) $args['query']) : '';
        $limit = isset($args['limit']) ? (int) $args['limit'] : self::DEFAULT_LIMIT;
        if ($limit < 1 || $limit > self::MAX_LIMIT) {
            $limit = self::DEFAULT_LIMIT;
        }

        if ($query === '') {
            return new ReplyResult('Please provide a search query (product name or SKU).');
        }

        try {
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect(['name', 'sku', 'entity_id']);
            $collection->addAttributeToFilter('name', ['like' => '%' . $query . '%']);
            $collection->setPageSize($limit);
            $collection->setCurPage(1);

            $items = [];
            foreach ($collection as $product) {
                $items[] = [
                    'id' => (int) $product->getId(),
                    'name' => $product->getName(),
                    'sku' => $product->getSku(),
                    'url' => $this->adminUrlHelper->getProductUrl((int) $product->getId()),
                ];
            }

            if (empty($items)) {
                $collection2 = $this->productCollectionFactory->create();
                $collection2->addAttributeToSelect(['name', 'sku', 'entity_id']);
                $collection2->addAttributeToFilter('sku', ['like' => '%' . $query . '%']);
                $collection2->setPageSize($limit);
                $collection2->setCurPage(1);
                foreach ($collection2 as $product) {
                    $items[] = [
                        'id' => (int) $product->getId(),
                        'name' => $product->getName(),
                        'sku' => $product->getSku(),
                        'url' => $this->adminUrlHelper->getProductUrl((int) $product->getId()),
                    ];
                }
            }

            if (empty($items)) {
                return new ReplyResult("No products found matching \"{$query}\".");
            }

            $lines = [];
            foreach ($items as $row) {
                $lines[] = "- **{$row['name']}** (SKU: {$row['sku']}) — ID {$row['id']}";
            }
            $reply = "Found " . count($items) . " product(s) matching \"{$query}\":\n\n" . implode("\n", $lines);
            $reply .= "\n\nUse open_admin_page with target product_edit and id to open a product.";

            $rows = array_map(function (array $r): array {
                return [$r['id'], $r['name'], $r['sku']];
            }, $items);

            $links = array_map(function (array $r): array {
                return ['text' => 'Open ' . $r['name'], 'url' => $r['url']];
            }, $items);

            return new ReplyResult(
                $reply,
                null,
                [
                    'type' => 'table',
                    'table' => [
                        'headers' => ['ID', 'Name', 'SKU'],
                        'rows' => $rows,
                    ],
                    'links' => $links,
                ]
            );
        } catch (\Throwable $e) {
            return new ReplyResult('An error occurred while searching products. Please try again.');
        }
    }
}
