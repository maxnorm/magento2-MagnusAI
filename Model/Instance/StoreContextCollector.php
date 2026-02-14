<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Instance;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Collects store scope context (websites, stores, default currency/locale) for discovery.
 * No PII; used so the agent knows single vs multi-store, currency, etc.
 */
class StoreContextCollector
{
    private const XML_PATH_CURRENCY = 'currency/options/base';
    private const XML_PATH_LOCALE = 'general/locale/code';

    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Get store context for prompt (website count, store count, default currency, default locale).
     *
     * @return array{website_count: int, store_count: int, default_currency: string, default_locale: string}
     */
    public function getContext(): array
    {
        try {
            $websites = $this->storeManager->getWebsites();
            $stores = $this->storeManager->getStores();
            $websiteCount = $websites ? count($websites) : 0;
            $storeCount = $stores ? count($stores) : 0;
        } catch (\Throwable $e) {
            $websiteCount = 0;
            $storeCount = 0;
        }

        $defaultCurrency = (string) $this->scopeConfig->getValue(
            self::XML_PATH_CURRENCY,
            ScopeInterface::SCOPE_STORE
        ) ?: 'USD';
        $defaultLocale = (string) $this->scopeConfig->getValue(
            self::XML_PATH_LOCALE,
            ScopeInterface::SCOPE_STORE
        ) ?: 'en_US';

        return [
            'website_count' => $websiteCount,
            'store_count' => $storeCount,
            'default_currency' => $defaultCurrency,
            'default_locale' => $defaultLocale,
        ];
    }
}
