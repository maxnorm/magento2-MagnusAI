<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Instance;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Collects non-secret config values from ScopeConfig for allowed paths.
 * Uses ConfigSchemaCollector to determine safe paths (excludes encrypted fields).
 */
class ConfigValueCollector
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigSchemaCollector $configSchemaCollector
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ConfigSchemaCollector $configSchemaCollector
    ) {
    }

    /**
     * Get config values for all safe paths.
     * Returns values for default scope (website/store values inherit from default).
     *
     * @return array<int, array{path: string, scope: string, scope_code: string|null, value: mixed}>
     */
    public function getValues(): array
    {
        $chunks = $this->configSchemaCollector->getChunks();
        $values = [];

        foreach ($chunks as $chunk) {
            $path = $chunk['path'];
            if (empty($path)) {
                continue;
            }

            // Get value for default scope
            $value = $this->scopeConfig->getValue($path, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
            
            // Only store non-null values to reduce index size
            if ($value !== null) {
                $values[] = [
                    'path' => $path,
                    'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    'scope_code' => null,
                    'value' => $this->normalizeValue($value),
                ];
            }
        }

        return $values;
    }

    /**
     * Get config value for a specific path and scope.
     *
     * @param string $path Config path
     * @param string $scopeType Scope type (default, website, store)
     * @param string|null $scopeCode Scope code (website code or store code)
     * @return mixed|null
     */
    public function getValue(string $path, string $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, ?string $scopeCode = null): mixed
    {
        return $this->scopeConfig->getValue($path, $scopeType, $scopeCode);
    }

    /**
     * Normalize value for storage (convert arrays/objects to JSON strings).
     *
     * @param mixed $value
     * @return mixed
     */
    private function normalizeValue(mixed $value): mixed
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        return $value;
    }
}
