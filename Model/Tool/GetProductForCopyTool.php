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
use Magnus\Assistant\Model\Chat\ReplyResult;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\ModuleListInterface;

/**
 * Read-only tool: returns copy-relevant product attributes for LLM to suggest name, descriptions, meta.
 */
class GetProductForCopyTool implements ToolInterface, ToolMessageBuilderInterface
{
    private const COPY_ATTRIBUTES = ['name', 'sku', 'short_description', 'description', 'meta_title', 'meta_description'];

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ModuleListInterface $moduleList
    ) {
    }

    /** @inheritdoc */
    public function supports(string $intent, array $context = []): bool
    {
        return $intent === 'get_product_for_copy';
    }

    /** @inheritdoc */
    public function getToolName(): string
    {
        return 'get_product_for_copy';
    }

    /** @inheritdoc */
    public function getToolDescription(): string
    {
        return 'Get product attributes needed to write or improve product copy (name, short description, long description, meta title, meta description). '
            . 'Use this before proposing product_copy_apply so you have current values to improve.';
    }

    /** @inheritdoc */
    public function getToolParameters(): array
    {
        return [
            'product_id' => [
                'type' => 'integer',
                'description' => 'Product entity ID (from search_products or context)',
            ],
        ];
    }

    /** @inheritdoc */
    public function buildMessageFromArguments(array $arguments): string
    {
        $id = $arguments['product_id'] ?? 0;
        return "Get product copy attributes for product ID {$id}";
    }

    /** @inheritdoc */
    public function getToolExamples(): array
    {
        return [
            'Get product details for writing copy for product 42',
            'Fetch product 123 for meta description',
        ];
    }

    /** @inheritdoc */
    public function execute(MessageRequestInterface $request): ReplyResultInterface
    {
        if (!$this->moduleList->isEnabled('Magento_Catalog')) {
            return new ReplyResult('Catalog is not enabled. Product copy data is not available.');
        }

        $args = $request->getContext()['tool_arguments'] ?? [];
        $productId = isset($args['product_id']) ? (int) $args['product_id'] : 0;
        if ($productId <= 0) {
            return new ReplyResult('Please provide a valid product_id (integer).');
        }

        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            return new ReplyResult("Product with ID {$productId} was not found.");
        } catch (\Throwable $e) {
            return new ReplyResult('An error occurred while loading the product. Please try again.');
        }

        $data = [];
        foreach (self::COPY_ATTRIBUTES as $attr) {
            $value = $product->getData($attr);
            $data[$attr] = $value !== null ? (string) $value : '';
        }

        $reply = "Product **{$data['name']}** (SKU: {$data['sku']}, ID: {$productId}). "
            . "Use these current values to suggest improved name, short description, description, meta title, and meta description, "
            . "then propose product_copy_apply with the suggested text.";

        return new ReplyResult(
            $reply,
            null,
            [
                'type' => 'key_value',
                'product_id' => $productId,
                'copy_attributes' => $data,
            ]
        );
    }
}
