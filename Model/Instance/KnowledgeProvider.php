<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Instance;

use Magnus\Assistant\Model\Cache\Type\Knowledge as KnowledgeCache;

/**
 * Builds and caches instance knowledge index (summary, modules, config chunks).
 */
class KnowledgeProvider
{
    private const CACHE_KEY = 'instance_knowledge';
    private const CACHE_LIFETIME = 86400;

    public function __construct(
        private readonly ModuleCollector $moduleCollector,
        private readonly ConfigSchemaCollector $configSchemaCollector,
        private readonly ConfigValueCollector $configValueCollector,
        private readonly KnowledgeCache $cache
    ) {
    }

    /**
     * Get full index (from cache or build and store).
     *
     * @return array{summary: array{module_count: int, config_path_count: int, config_value_count: int}, module_names: array, config_chunks: array, config_values: array}
     */
    public function getIndex(): array
    {
        $cached = $this->cache->load(self::CACHE_KEY);
        if ($cached !== false) {
            $decoded = json_decode($cached, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        $index = $this->buildIndex();
        $this->cache->save(json_encode($index), self::CACHE_KEY, [], self::CACHE_LIFETIME);
        return $index;
    }

    /**
     * Build index from collectors.
     *
     * @return array{summary: array{module_count: int, config_path_count: int, config_value_count: int}, module_names: array, config_chunks: array, config_values: array}
     */
    private function buildIndex(): array
    {
        $moduleNames = $this->moduleCollector->getModuleNames();
        $configChunks = $this->configSchemaCollector->getChunks();
        $configValues = $this->configValueCollector->getValues();
        return [
            'summary' => [
                'module_count' => count($moduleNames),
                'config_path_count' => count($configChunks),
                'config_value_count' => count($configValues),
            ],
            'module_names' => $moduleNames,
            'config_chunks' => $configChunks,
            'config_values' => $configValues,
        ];
    }

    /**
     * Invalidate cached index (e.g. on config save).
     */
    public function invalidate(): void
    {
        $this->cache->remove(self::CACHE_KEY);
    }
}
