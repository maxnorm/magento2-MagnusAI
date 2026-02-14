<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Llm;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\ClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * OpenRouter API provider (OpenAI-compatible chat completions).
 * Provides access to 400+ models including free options.
 */
class OpenRouterProvider implements LlmProviderInterface
{
    private const DEFAULT_URL = 'https://openrouter.ai/api/v1/chat/completions';
    private const XML_PATH_API_KEY = 'magnus/assistant/api_key';
    private const XML_PATH_MODEL = 'magnus/assistant/openrouter_model';
    private const DEFAULT_MODEL = 'google/gemini-flash-1.5';

    public function __construct(
        private readonly ClientFactory $httpClientFactory,
        private readonly Json $json,
        private readonly LoggerInterface $logger,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
        private readonly string $baseUrl = self::DEFAULT_URL
    ) {
    }

    private const OPENROUTER_PRIVACY_URL = 'https://openrouter.ai/settings/privacy';

    /**
     * Build user-facing reply when API returns no choices (error or empty).
     * Parses OpenRouter/OpenAI error format and logs when needed.
     */
    private function replyWhenNoChoices(array $data, string $context): string
    {
        $errorMessage = is_string($data['error']['message'] ?? null) ? $data['error']['message'] : '';

        if ($errorMessage !== '') {
            $this->logger->warning('Magnus OpenRouter API error: ' . $errorMessage, ['context' => $context]);

            // OpenRouter data policy / free-model privacy: guide user to fix in account settings
            if (stripos($errorMessage, 'data policy') !== false
                || stripos($errorMessage, 'Free model publication') !== false
                || stripos($errorMessage, 'privacy') !== false
            ) {
                return (string) __(
                    'OpenRouter requires you to set your data policy for free models. Open your privacy settings and enable "Free model publication" (or allow free models): %1',
                    self::OPENROUTER_PRIVACY_URL
                );
            }

            // Generic "Provider returned error" = upstream model provider (e.g. Meta, Google) had an issue
            if (stripos($errorMessage, 'Provider returned error') !== false) {
                return (string) __(
                    'The model provider had a temporary issue. Please try again in a moment. If it keeps failing, try another model in Stores → Configuration → Magnus AI Assistant → OpenRouter Model (e.g. google/gemini-flash-1.5 or another from https://openrouter.ai/models).'
                );
            }

            return (string) __('The assistant reported an error: %1', $errorMessage);
        }

        if (isset($data['error'])) {
            $this->logger->warning('Magnus OpenRouter API error response', ['data' => $data, 'context' => $context]);
            $code = $data['error']['code'] ?? '';
            return (string) __('The assistant reported an error (code %1). Check your OpenRouter account and model.', $code);
        }

        $this->logger->warning('Magnus OpenRouter returned empty choices', ['context' => $context]);
        return (string) __(
            'The model returned no reply. Check your OpenRouter credits and model access, or try another model in Magnus settings.'
        );
    }

    /**
     * @inheritdoc
     */
    public function complete(array $messages, array $options = []): string
    {
        $rawKey = $this->scopeConfig->getValue(self::XML_PATH_API_KEY, 'default');
        $apiKey = $rawKey ? $this->encryptor->decrypt($rawKey) : null;
        if (empty($apiKey)) {
            $this->logger->warning('Magnus: OpenRouter API key not configured');
            return (string) __(
                'Magnus is not configured with an LLM API key. Please set it in Stores > Configuration > Magnus AI Assistant.'
            );
        }

        $configuredModel = $this->scopeConfig->getValue(self::XML_PATH_MODEL, 'default');
        $model = $options['model'] ?? ($configuredModel ?: self::DEFAULT_MODEL);

        $body = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'] ?? 1024,
        ];

        $client = $this->httpClientFactory->create();
        $client->addHeader('Content-Type', 'application/json');
        $client->addHeader('Authorization', 'Bearer ' . $apiKey);
        $client->setOption(CURLOPT_TIMEOUT, 60);

        try {
            $client->post($this->baseUrl, $this->json->serialize($body));
            $responseBody = $client->getBody();
            $data = $this->json->unserialize($responseBody);
        } catch (\Throwable $e) {
            $this->logger->error('Magnus OpenRouter complete request failed: ' . $e->getMessage());
            return (string) __('Sorry, I could not get a response from the assistant. Please try again.');
        }

        $choices = $data['choices'] ?? [];
        if (empty($choices)) {
            return $this->replyWhenNoChoices($data, 'complete');
        }
        $content = $choices[0]['message']['content'] ?? '';
        return trim((string) $content);
    }

    /**
     * @inheritdoc
     */
    public function completeWithFunctions(array $messages, array $tools, array $options = []): array
    {
        $rawKey = $this->scopeConfig->getValue(self::XML_PATH_API_KEY, 'default');
        $apiKey = $rawKey ? $this->encryptor->decrypt($rawKey) : null;
        if (empty($apiKey)) {
            $this->logger->warning('Magnus: OpenRouter API key not configured');
            return [
                'reply' => (string) __('Magnus is not configured with an LLM API key. Please set it in Stores > Configuration > Magnus AI Assistant > Settings.'),
                'tool_calls' => [],
                'finish_reason' => 'stop',
            ];
        }

        $configuredModel = $this->scopeConfig->getValue(self::XML_PATH_MODEL, 'default');
        $model = $options['model'] ?? ($configuredModel ?: self::DEFAULT_MODEL);

        $body = [
            'model' => $model,
            'messages' => $messages,
            'tools' => $tools,
            'tool_choice' => $options['tool_choice'] ?? 'auto',
            'max_tokens' => $options['max_tokens'] ?? 1024,
        ];

        $client = $this->httpClientFactory->create();
        $client->addHeader('Content-Type', 'application/json');
        $client->addHeader('Authorization', 'Bearer ' . $apiKey);
        $client->setOption(CURLOPT_TIMEOUT, 60);

        try {
            $client->post($this->baseUrl, $this->json->serialize($body));
            $responseBody = $client->getBody();
            $data = $this->json->unserialize($responseBody);
        } catch (\Throwable $e) {
            $this->logger->error('Magnus OpenRouter function calling request failed: ' . $e->getMessage());
            return [
                'reply' => (string) __('Sorry, I could not get a response from the assistant. Please try again.'),
                'tool_calls' => [],
                'finish_reason' => 'stop',
            ];
        }

        $choices = $data['choices'] ?? [];
        if (empty($choices)) {
            $reply = $this->replyWhenNoChoices($data, 'completeWithFunctions');
            return [
                'reply' => $reply,
                'tool_calls' => [],
                'finish_reason' => 'stop',
            ];
        }

        $choice = $choices[0];
        $message = $choice['message'] ?? [];
        $finishReason = $choice['finish_reason'] ?? 'stop';
        $content = $message['content'] ?? '';
        $toolCalls = [];

        // Parse tool calls if present
        if (isset($message['tool_calls']) && is_array($message['tool_calls'])) {
            foreach ($message['tool_calls'] as $toolCall) {
                $toolCalls[] = [
                    'id' => $toolCall['id'] ?? '',
                    'type' => $toolCall['type'] ?? 'function',
                    'function' => [
                        'name' => $toolCall['function']['name'] ?? '',
                        'arguments' => $toolCall['function']['arguments'] ?? '',
                    ],
                ];
            }
        }

        $reply = trim((string) $content);
        if ($reply === '' && !empty($toolCalls)) {
            $reply = '';
        }
        return [
            'reply' => $reply,
            'tool_calls' => $toolCalls,
            'finish_reason' => $finishReason,
        ];
    }
}
