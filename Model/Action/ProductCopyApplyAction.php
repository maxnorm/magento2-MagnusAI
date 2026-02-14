<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Action;

use Magnus\Assistant\Api\ActionInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Apply suggested product copy (name, short description, description, meta title, meta description).
 * Suggest via get_product_for_copy → LLM proposes → user approves → execute.
 */
class ProductCopyApplyAction implements ActionInterface
{
    private const COPY_KEYS = ['name', 'short_description', 'description', 'meta_title', 'meta_description'];

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(array $params): ActionResult
    {
        $productId = (int) ($params['product_id'] ?? 0);
        if ($productId <= 0) {
            return new ActionResult(false, (string) __('Invalid product_id.'));
        }

        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            return new ActionResult(false, (string) __('Product with ID %1 was not found.', $productId));
        }

        $updated = [];
        foreach (self::COPY_KEYS as $key) {
            if (array_key_exists($key, $params)) {
                $value = $params[$key];
                if ($value !== null && $value !== '') {
                    $product->setData($key, (string) $value);
                    $updated[] = $key;
                }
            }
        }

        if (empty($updated)) {
            return new ActionResult(false, (string) __('No copy fields to update.'));
        }

        try {
            $this->productRepository->save($product);
            return new ActionResult(
                true,
                (string) __('Product copy updated for "%1" (ID: %2). Updated: %3.', $product->getName(), $productId, implode(', ', $updated)),
                ['product_id' => $productId, 'updated' => $updated]
            );
        } catch (\Throwable $e) {
            return new ActionResult(
                false,
                (string) __('Failed to save product copy: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function preview(array $params): ActionPreview
    {
        $productId = (int) ($params['product_id'] ?? 0);
        $summary = (string) __('Update product copy for product ID %1.', $productId);
        $details = ['product_id' => $productId];

        $diff = [];
        foreach (self::COPY_KEYS as $key) {
            if (array_key_exists($key, $params)) {
                $details[$key] = $params[$key];
                $diff[$key] = ['after' => $params[$key]];
            }
        }

        if ($productId > 0) {
            try {
                $product = $this->productRepository->getById($productId);
                $details['current_name'] = $product->getName();
                foreach (self::COPY_KEYS as $key) {
                    if (array_key_exists($key, $params)) {
                        $before = $product->getData($key);
                        if (isset($diff[$key])) {
                            $diff[$key]['before'] = $before !== null ? (string) $before : '';
                        }
                    }
                }
            } catch (NoSuchEntityException|\Throwable $e) {
                // Preview still valid; diff may be partial
            }
        }

        return new ActionPreview($summary, $details, $diff);
    }

    /**
     * @inheritdoc
     */
    public function validate(array $params): bool
    {
        $productId = (int) ($params['product_id'] ?? 0);
        if ($productId <= 0) {
            return false;
        }
        foreach (self::COPY_KEYS as $key) {
            if (array_key_exists($key, $params)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return 'Apply suggested product copy (name, short description, description, meta title, meta description) to a product. '
            . 'Use after get_product_for_copy and generating suggested text.';
    }

    /**
     * @inheritdoc
     */
    public function requiresApproval(): bool
    {
        return true;
    }
}
