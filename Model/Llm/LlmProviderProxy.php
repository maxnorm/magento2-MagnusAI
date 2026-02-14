<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Llm;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Proxy for LLM provider that lazy-loads the actual provider based on configuration.
 * Caches the provider instance until configuration changes.
 */
class LlmProviderProxy implements LlmProviderInterface
{
    private const XML_PATH_PROVIDER = 'magnus/assistant/provider';

    private ?LlmProviderInterface $provider = null;
    private ?string $cachedProviderType = null;

    public function __construct(
        private readonly LlmProviderFactory $providerFactory,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @inheritdoc
     */
    public function complete(array $messages, array $options = []): string
    {
        $provider = $this->getProvider();
        return $provider->complete($messages, $options);
    }

    /**
     * @inheritdoc
     */
    public function completeWithFunctions(array $messages, array $tools, array $options = []): array
    {
        $provider = $this->getProvider();
        return $provider->completeWithFunctions($messages, $tools, $options);
    }

    /**
     * Get the configured provider instance, creating it if needed.
     *
     * @return LlmProviderInterface
     */
    private function getProvider(): LlmProviderInterface
    {
        $currentProviderType = $this->scopeConfig->getValue(self::XML_PATH_PROVIDER, 'default') ?? 'openai';

        // If provider type changed or not cached, create new instance
        if ($this->provider === null || $this->cachedProviderType !== $currentProviderType) {
            $this->provider = $this->providerFactory->create();
            $this->cachedProviderType = $currentProviderType;
        }

        return $this->provider;
    }
}
