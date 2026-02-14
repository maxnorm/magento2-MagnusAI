<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Helper;

use Magento\Backend\Model\UrlInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Helper for generating admin URLs for deep linking.
 */
class AdminUrl
{
    /**
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private readonly UrlInterface $urlBuilder
    ) {
    }

    /**
     * Get URL to system configuration page, optionally scoped to a specific path.
     *
     * @param string|null $path Config path (e.g., 'catalog/search')
     * @param string $scopeType Scope type (default, website, store)
     * @param string|null $scopeCode Scope code
     * @return string
     */
    public function getConfigUrl(?string $path = null, string $scopeType = ScopeInterface::SCOPE_TYPE_DEFAULT, ?string $scopeCode = null): string
    {
        $params = ['section' => 'system'];
        
        if ($path) {
            $parts = explode('/', $path);
            if (count($parts) >= 2) {
                $params['section'] = $parts[0];
                if (isset($parts[1])) {
                    $params['group'] = $parts[1];
                }
            }
        }
        
        if ($scopeType === ScopeInterface::SCOPE_TYPE_WEBSITE && $scopeCode) {
            $params['website'] = $scopeCode;
        } elseif ($scopeType === ScopeInterface::SCOPE_TYPE_STORE && $scopeCode) {
            $params['store'] = $scopeCode;
        }
        
        return $this->urlBuilder->getUrl('adminhtml/system_config/edit', $params);
    }

    /**
     * Get URL to product grid or edit page.
     *
     * @param int|null $productId Product ID (if provided, links to edit page)
     * @return string
     */
    public function getProductUrl(?int $productId = null): string
    {
        if ($productId) {
            return $this->urlBuilder->getUrl('catalog/product/edit', ['id' => $productId]);
        }
        return $this->urlBuilder->getUrl('catalog/product/index');
    }

    /**
     * Get URL to category grid or edit page.
     *
     * @param int|null $categoryId Category ID (if provided, links to edit page)
     * @return string
     */
    public function getCategoryUrl(?int $categoryId = null): string
    {
        if ($categoryId) {
            return $this->urlBuilder->getUrl('catalog/category/edit', ['id' => $categoryId]);
        }
        return $this->urlBuilder->getUrl('catalog/category/index');
    }

    /**
     * Get URL to customer grid or edit page.
     *
     * @param int|null $customerId Customer ID (if provided, links to edit page)
     * @return string
     */
    public function getCustomerUrl(?int $customerId = null): string
    {
        if ($customerId) {
            return $this->urlBuilder->getUrl('customer/index/edit', ['id' => $customerId]);
        }
        return $this->urlBuilder->getUrl('customer/index/index');
    }

    /**
     * Get URL to catalog price rule grid or add.
     *
     * @return string
     */
    public function getPromoCatalogUrl(): string
    {
        return $this->urlBuilder->getUrl('catalog_rule/promo_catalog/index');
    }

    /**
     * Get URL to cart price rule grid or add.
     *
     * @return string
     */
    public function getPromoCartUrl(): string
    {
        return $this->urlBuilder->getUrl('sales_rule/promo_quote/index');
    }

    /**
     * Get URL to order grid or view page.
     *
     * @param int|null $orderId Order ID (if provided, links to view page)
     * @return string
     */
    public function getOrderUrl(?int $orderId = null): string
    {
        if ($orderId) {
            return $this->urlBuilder->getUrl('sales/order/view', ['order_id' => $orderId]);
        }
        return $this->urlBuilder->getUrl('sales/order/index');
    }

    /**
     * Get URL to CMS page grid or edit page.
     *
     * @param int|null $pageId CMS page ID (if provided, links to edit page)
     * @return string
     */
    public function getCmsPageUrl(?int $pageId = null): string
    {
        if ($pageId) {
            return $this->urlBuilder->getUrl('cms/page/edit', ['page_id' => $pageId]);
        }
        return $this->urlBuilder->getUrl('cms/page/index');
    }

    /**
     * Get URL to CMS block grid or edit page.
     *
     * @param int|null $blockId CMS block ID (if provided, links to edit page)
     * @return string
     */
    public function getCmsBlockUrl(?int $blockId = null): string
    {
        if ($blockId) {
            return $this->urlBuilder->getUrl('cms/block/edit', ['block_id' => $blockId]);
        }
        return $this->urlBuilder->getUrl('cms/block/index');
    }

    /**
     * Generic URL builder for admin routes.
     *
     * @param string $route Route path (e.g., 'catalog/product/index')
     * @param array $params URL parameters
     * @return string
     */
    public function getUrl(string $route, array $params = []): string
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}
