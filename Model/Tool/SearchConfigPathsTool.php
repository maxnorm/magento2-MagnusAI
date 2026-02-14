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
use Magnus\Assistant\Model\Chat\ReplyResult;
use Magnus\Assistant\Model\Instance\KnowledgeProvider;

/**
 * Tool to search config index by keywords; returns matching path + label so the LLM can choose one.
 */
class SearchConfigPathsTool implements ToolInterface, ToolMessageBuilderInterface
{
    private const MAX_RESULTS = 15;

    public function __construct(
        private readonly KnowledgeProvider $knowledgeProvider
    ) {
    }

    /** @inheritdoc */
    public function supports(string $intent, array $context = []): bool
    {
        return $intent === 'search_config_paths';
    }

    /** @inheritdoc */
    public function getToolName(): string
    {
        return 'search_config_paths';
    }

    /** @inheritdoc */
    public function getToolDescription(): string
    {
        return 'Search configuration paths by keywords. Use when the user asks where a setting is, or to find config related to a topic (e.g. payment, shipping, catalog). Returns a list of matching path and label.';
    }

    /** @inheritdoc */
    public function getToolParameters(): array
    {
        return [
            'keywords' => [
                'type' => 'string',
                'description' => 'Keywords to search for (e.g. payment, shipping, catalog search)',
            ],
        ];
    }

    /** @inheritdoc */
    public function buildMessageFromArguments(array $arguments): string
    {
        $keywords = $arguments['keywords'] ?? '';
        return $keywords !== '' ? "Search config paths: {$keywords}" : 'Search config paths';
    }

    /** @inheritdoc */
    public function getToolExamples(): array
    {
        return [
            'Where is payment configured?',
            'Find config for shipping',
            'Search config for catalog',
        ];
    }

    /** @inheritdoc */
    public function execute(MessageRequestInterface $request): ReplyResultInterface
    {
        $args = $request->getContext()['tool_arguments'] ?? [];
        $keywords = isset($args['keywords']) ? trim((string) $args['keywords']) : null;
        if ($keywords === null || $keywords === '') {
            $keywords = $request->getMessage();
        }

        $words = $this->normalizeToWords($keywords);
        if (empty($words)) {
            return new ReplyResult(
                "Please provide keywords to search for (e.g. payment, shipping, catalog)."
            );
        }

        $index = $this->knowledgeProvider->getIndex();
        $chunks = $index['config_chunks'] ?? [];
        $scored = [];

        foreach ($chunks as $chunk) {
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
            if ($score > 0) {
                $scored[] = ['score' => $score, 'path' => $chunk['path'] ?? '', 'label' => $chunk['label'] ?? ''];
            }
        }

        usort($scored, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
        $results = array_slice($scored, 0, self::MAX_RESULTS);

        if (empty($results)) {
            return new ReplyResult("No configuration paths matched \"{$keywords}\". Try different keywords.");
        }

        $lines = [];
        foreach ($results as $r) {
            $lines[] = '- **' . $r['label'] . '** — `' . $r['path'] . '`';
        }
        $reply = "Configuration paths matching \"{$keywords}\":\n\n" . implode("\n", $lines);
        $reply .= "\n\nYou can use get_config_value with a path, or explain_config for details.";

        return new ReplyResult($reply);
    }

    /** @return string[] */
    private function normalizeToWords(string $text): array
    {
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text) ?? $text;
        $words = array_filter(explode(' ', mb_strtolower(trim($text), 'UTF-8')));
        return array_values(array_unique(array_filter($words, fn(string $w): bool => strlen($w) >= 2)));
    }
}
