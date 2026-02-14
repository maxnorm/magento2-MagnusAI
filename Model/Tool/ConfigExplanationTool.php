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
 * Tool for explaining config paths and showing current values.
 */
class ConfigExplanationTool implements ToolInterface, ToolMessageBuilderInterface
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
        return $intent === 'config_explanation';
    }

    /** @inheritdoc */
    public function getToolName(): string
    {
        return 'explain_config';
    }

    /** @inheritdoc */
    public function getToolDescription(): string
    {
        return 'Explains Magento configuration settings and shows current values. Use this when users ask about what a configuration setting does, what it means, or what its current value is.';
    }

    /** @inheritdoc */
    public function getToolParameters(): array
    {
        return [
            'config_path' => [
                'type' => 'string',
                'description' => 'The configuration path or keywords to search for (e.g., "catalog search", "payment methods", "shipping methods", "tax settings")',
            ],
        ];
    }

    /** @inheritdoc */
    public function buildMessageFromArguments(array $arguments): string
    {
        $configPath = $arguments['config_path'] ?? '';
        return "What does {$configPath} do?";
    }

    /** @inheritdoc */
    public function getToolExamples(): array
    {
        return [
            'What does catalog search do?',
            'Explain payment methods configuration',
            'What is the shipping configuration?',
        ];
    }

    /** @inheritdoc */
    public function execute(MessageRequestInterface $request): ReplyResultInterface
    {
        $message = mb_strtolower(trim($request->getMessage()), 'UTF-8');
        $index = $this->knowledgeProvider->getIndex();
        $configChunks = $index['config_chunks'] ?? [];
        $matchedChunk = null;
        $bestScore = 0;
        $words = $this->extractKeywords($message);

        foreach ($configChunks as $chunk) {
            $path = mb_strtolower($chunk['path'] ?? '', 'UTF-8');
            $label = mb_strtolower($chunk['label'] ?? '', 'UTF-8');
            $searchable = mb_strtolower($chunk['searchable'] ?? '', 'UTF-8');
            $score = 0;
            foreach ($words as $word) {
                if (strlen($word) < 2) {
                    continue;
                }
                if (str_contains($path, $word) || str_contains($label, $word) || str_contains($searchable, $word)) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $matchedChunk = $chunk;
            }
        }

        if ($matchedChunk === null || $bestScore === 0) {
            return new ReplyResult(
                "I couldn't find that configuration setting. Please try asking about a specific config path, " .
                "like 'catalog search' or 'payment methods'."
            );
        }

        $path = $matchedChunk['path'];
        $label = $matchedChunk['label'] ?? $path;
        $comment = $matchedChunk['comment'] ?? '';
        $value = $this->contextRetriever->getConfigValue($index, $path);

        $explanation = "**{$label}**\n\n";
        if (!empty($comment)) {
            $explanation .= "{$comment}\n\n";
        }
        $explanation .= "**Config Path:** `{$path}`\n\n";
        if ($value !== null) {
            $explanation .= "**Current Value:** " . $this->formatValue($value) . "\n";
            $explanation .= "**Scope:** Default (applies to all stores unless overridden)\n";
        } else {
            $explanation .= "**Current Value:** Not set (using default)\n";
        }

        $configUrl = $this->adminUrlHelper->getConfigUrl($path);

        return new ReplyResult(
            $explanation,
            null,
            [
                'type' => 'links',
                'links' => [
                    [
                        'text' => 'Open Configuration Page',
                        'url' => $configUrl,
                    ],
                ],
            ]
        );
    }

    private function extractKeywords(string $message): array
    {
        $message = preg_replace('/\b(what|does|do|is|tell|me|about|explain|how|does|this|setting|config|configuration)\b/i', '', $message);
        $message = trim($message);
        $words = preg_split('/[\s\/\-_]+/', $message);
        return array_filter(array_map('trim', $words), fn($w) => strlen($w) >= 2);
    }

    private function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value)) {
            return json_encode($value);
        }
        if (is_string($value) && strlen($value) > 100) {
            return substr($value, 0, 100) . '...';
        }
        return (string) $value;
    }
}
