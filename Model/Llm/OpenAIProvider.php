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
 * OpenAI-compatible API provider (chat completions).
 */
class OpenAIProvider implements LlmProviderInterface
{
    private const DEFAULT_URL = 'https://api.openai.com/v1/chat/completions';
    private const XML_PATH_API_KEY = 'magnus/assistant/api_key';

    public function __construct(
        private readonly ClientFactory $httpClientFactory,
        private readonly Json $json,
        private readonly LoggerInterface $logger,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
        private readonly string $baseUrl = self::DEFAULT_URL
    ) {
    }

    /**
     * @inheritdoc
     */
    public function complete(array $messages, array $options = []): string
    {
        $rawKey = $this->scopeConfig->getValue(self::XML_PATH_API_KEY, 'default');
        $apiKey = $rawKey ? $this->encryptor->decrypt($rawKey) : null;
        if (empty($apiKey)) {
            $this->logger->warning('Magnus: OpenAI API key not configured');
            return (string) __('Magnus is not configured with an LLM API key. Please set it in Stores > Configuration > Advanced > Magnus AI Assistant.');
        }

        $body = [
            'model' => $options['model'] ?? 'gpt-4o-mini',
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
            $this->logger->error('Magnus OpenAI request failed: ' . $e->getMessage());
            return (string) __('Sorry, I could not get a response from the assistant. Please try again.');
        }

        $choices = $data['choices'] ?? [];
        if (empty($choices)) {
            return $this->replyWhenNoChoices($data, 'complete');
        }
        $content = $choices[0]['message']['content'] ?? '';
        $text = trim((string) $content);
        if ($text === '' && !empty($choices[0]['message']['tool_calls'] ?? [])) {
            return '';
        }
        return $text;
    }

    /**
     * Build user-facing reply when API returns no choices (error or empty).
     */
    private function replyWhenNoChoices(array $data, string $context): string
    {
        if (is_string($data['error']['message'] ?? null)) {
            $this->logger->warning('Magnus OpenAI API error: ' . $data['error']['message'], ['context' => $context]);
            return (string) __('The assistant reported an error: %1', $data['error']['message']);
        }
        if (isset($data['error'])) {
            $this->logger->warning('Magnus OpenAI API error response', ['data' => $data, 'context' => $context]);
            $code = $data['error']['code'] ?? $data['error']['type'] ?? '';
            return (string) __('The assistant reported an error (code %1). Check your OpenAI account and model.', $code);
        }
        $this->logger->warning('Magnus OpenAI returned empty choices', ['context' => $context]);
        return (string) __(
            'The model returned no reply. Check your API key and model in Magnus settings.'
        );
    }

    /**
     * @inheritdoc
     */
    public function completeWithFunctions(array $messages, array $tools, array $options = []): array
    {
        $rawKey = $this->scopeConfig->getValue(self::XML_PATH_API_KEY, 'default');
        $apiKey = $rawKey ? $this->encryptor->decrypt($rawKey) : null;
        if (empty($apiKey)) {
            $this->logger->warning('Magnus: OpenAI API key not configured');
            return [
                'reply' => (string) __('Magnus is not configured with an LLM API key. Please set it in Stores > Configuration > Advanced > Magnus AI Assistant.'),
                'tool_calls' => [],
                'finish_reason' => 'stop',
            ];
        }

        $body = [
            'model' => $options['model'] ?? 'gpt-4o-mini',
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
            $this->logger->error('Magnus OpenAI function calling request failed: ' . $e->getMessage());
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
