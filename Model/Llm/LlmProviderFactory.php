<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Llm;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;

/**
 * Factory for creating LLM provider instances based on configuration.
 */
class LlmProviderFactory
{
    private const XML_PATH_PROVIDER = 'magnus/assistant/provider';

    /**
     * @var array<string, string>
     */
    private array $providerMap = [
        'openai' => OpenAIProvider::class,
        'openrouter' => OpenRouterProvider::class,
    ];

    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Create LLM provider instance based on configured provider.
     *
     * @return LlmProviderInterface
     * @throws LocalizedException
     */
    public function create(): LlmProviderInterface
    {
        $provider = $this->scopeConfig->getValue(self::XML_PATH_PROVIDER, 'default') ?? 'openai';

        if (!isset($this->providerMap[$provider])) {
            throw new LocalizedException(
                __('Unknown LLM provider: %1', $provider)
            );
        }

        $providerClass = $this->providerMap[$provider];
        $instance = $this->objectManager->create($providerClass);

        if (!$instance instanceof LlmProviderInterface) {
            throw new LocalizedException(
                __('Provider class %1 does not implement LlmProviderInterface', $providerClass)
            );
        }

        return $instance;
    }
}
