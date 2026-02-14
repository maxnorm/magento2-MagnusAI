<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Tool;

use Magnus\Assistant\Api\MessageRequestInterface;
use Magnus\Assistant\Api\ReplyResultInterface;
use Magnus\Assistant\Api\ToolInterface;
use Magnus\Assistant\Api\ToolMessageBuilderInterface;
use Magnus\Assistant\Helper\AdminUrl;
use Magnus\Assistant\Model\Chat\ReplyResult;
use Magnus\Assistant\Model\Instance\ContextRetriever;
use Magnus\Assistant\Model\Instance\KnowledgeProvider;

/**
 * Tool to get current value for a config path (read-only).
 */
class GetConfigValueTool implements ToolInterface, ToolMessageBuilderInterface
{
    public function __construct(
        private readonly KnowledgeProvider $knowledgeProvider,
        private readonly ContextRetriever $contextRetriever,
        private readonly AdminUrl $adminUrlHelper
    ) {
    }

    /** @inheritdoc */
    public function supports(string $intent, array $context = []): bool
    {
        return $intent === 'get_config_value';
    }

    /** @inheritdoc */
    public function getToolName(): string
    {
        return 'get_config_value';
    }

    /** @inheritdoc */
    public function getToolDescription(): string
    {
        return 'Returns the current value for a configuration path. Use when the user asks what a setting is set to, or to check a specific config path. Read-only.';
    }

    /** @inheritdoc */
    public function getToolParameters(): array
    {
        return [
            'path' => [
                'type' => 'string',
                'description' => 'The configuration path (e.g. catalog/search/engine, currency/options/base)',
            ],
        ];
    }

    /** @inheritdoc */
    public function buildMessageFromArguments(array $arguments): string
    {
        $path = $arguments['path'] ?? '';
        return $path !== '' ? "Get config value for {$path}" : 'Get config value';
    }

    /** @inheritdoc */
    public function getToolExamples(): array
    {
        return [
            'What is catalog search engine set to?',
            'Get config value for currency base',
        ];
    }

    /** @inheritdoc */
    public function execute(MessageRequestInterface $request): ReplyResultInterface
    {
        $args = $request->getContext()['tool_arguments'] ?? [];
        $path = isset($args['path']) ? trim((string) $args['path']) : null;
        if ($path === null || $path === '') {
            $path = $this->extractPathFromMessage($request->getMessage());
        }

        if ($path === null || $path === '') {
            return new ReplyResult(
                "Please provide a config path (e.g. catalog/search/engine or payment/paypal/active)."
            );
        }

        $index = $this->knowledgeProvider->getIndex();
        $value = $this->contextRetriever->getConfigValue($index, $path);

        $formatted = $this->formatValue($value);
        $reply = "**Config path:** `{$path}`\n**Current value:** " . $formatted;

        $configUrl = $this->adminUrlHelper->getConfigUrl($path);
        return new ReplyResult(
            $reply,
            null,
            [
                'type' => 'links',
                'links' => [
                    ['text' => 'Open Configuration', 'url' => $configUrl],
                ],
            ]
        );
    }

    private function extractPathFromMessage(string $message): ?string
    {
        $message = trim($message);
        if (preg_match('/config\s+path\s+["\']?([a-z0-9_\/]+)["\']?/i', $message, $m)) {
            return $m[1];
        }
        if (preg_match('/path\s+["\']?([a-z0-9_\/]+)["\']?/i', $message, $m)) {
            return $m[1];
        }
        return null;
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return 'not set (using default)';
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value)) {
            return json_encode($value);
        }
        if (is_string($value) && strlen($value) > 200) {
            return substr($value, 0, 200) . '...';
        }
        return (string) $value;
    }
}
