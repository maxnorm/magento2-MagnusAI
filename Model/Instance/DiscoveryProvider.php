<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Instance;

use Magnus\Assistant\Model\Cache\Type\Knowledge as KnowledgeCache;

/**
 * Aggregates discovery payload for the agent: admin areas, store context, capabilities from modules.
 * Used by PromptBuilder to inject "Store context" so the agent adapts to the store.
 */
class DiscoveryProvider
{
    private const CACHE_KEY = 'magnus_discovery';
    private const CACHE_LIFETIME = 86400;
    private const MAX_ADMIN_AREAS_FOR_PROMPT = 30;

    /** Map module name to capability label for prompt */
    private const CAPABILITY_MAP = [
        'Magento_Catalog' => 'catalog (products, categories)',
        'Magento_Sales' => 'sales (orders)',
        'Magento_Customer' => 'customer',
        'Magento_Cms' => 'cms (pages, blocks)',
    ];

    public function __construct(
        private readonly KnowledgeProvider $knowledgeProvider,
        private readonly AdminMenuCollector $adminMenuCollector,
        private readonly StoreContextCollector $storeContextCollector,
        private readonly KnowledgeCache $cache
    ) {
    }

    /**
     * Get discovery payload: admin_areas (capped), store_scope, capabilities, summary.
     *
     * @return array{admin_areas: array, store_scope: array, capabilities: array, summary: array}
     */
    public function getDiscoveryPayload(): array
    {
        $cached = $this->cache->load(self::CACHE_KEY);
        if ($cached !== false) {
            $decoded = json_decode($cached, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $index = $this->knowledgeProvider->getIndex();
        $adminItems = $this->adminMenuCollector->getItems();
        $adminAreas = array_slice(
            array_map(
                fn(array $item): string => $item['path'],
                $adminItems
            ),
            0,
            self::MAX_ADMIN_AREAS_FOR_PROMPT
        );
        $storeScope = $this->storeContextCollector->getContext();
        $capabilities = $this->deriveCapabilities($index['module_names'] ?? []);
        $summary = $index['summary'] ?? [];

        $payload = [
            'admin_areas' => $adminAreas,
            'admin_items' => $adminItems,
            'store_scope' => $storeScope,
            'capabilities' => $capabilities,
            'summary' => $summary,
        ];

        $this->cache->save(json_encode($payload), self::CACHE_KEY, [], self::CACHE_LIFETIME);
        return $payload;
    }

    /**
     * Derive capability labels from enabled module names.
     *
     * @param string[] $moduleNames
     * @return string[]
     */
    private function deriveCapabilities(array $moduleNames): array
    {
        $capabilities = [];
        foreach (self::CAPABILITY_MAP as $module => $label) {
            if (in_array($module, $moduleNames, true)) {
                $capabilities[] = $label;
            }
        }
        return $capabilities;
    }

    /**
     * Invalidate discovery cache (e.g. when knowledge is invalidated).
     */
    public function invalidate(): void
    {
        $this->cache->remove(self::CACHE_KEY);
    }
}
