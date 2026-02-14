<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Instance;

/**
 * Retrieves relevant context chunks from the index for a user message.
 * Phase 1: keyword-based match. Used to minimize tokens sent to LLM.
 */
class ContextRetriever
{
    private const DEFAULT_MAX_CHUNKS = 10;
    private const MAX_CHARS = 3000;

    /**
     * Get relevant context (summary + chunks) for the user message.
     *
     * @param string $userMessage
     * @param array $index Index from KnowledgeProvider::getIndex()
     * @return array{summary: array, relevant_chunks: array}
     */
    public function getRelevantContext(string $userMessage, array $index): array
    {
        $summary = $index['summary'] ?? ['module_count' => 0, 'config_path_count' => 0];
        $chunks = $index['config_chunks'] ?? [];
        $moduleNames = $index['module_names'] ?? [];

        $words = $this->normalizeToWords($userMessage);
        if (empty($words)) {
            return [
                'summary' => $summary,
                'relevant_chunks' => [],
            ];
        }

        $scored = [];
        foreach ($chunks as $chunk) {
            $searchable = $this->normalizeToWords($chunk['searchable'] ?? $chunk['path'] . ' ' . ($chunk['label'] ?? ''));
            $score = 0;
            foreach ($words as $word) {
                if (strlen($word) < 2) {
                    continue;
                }
                foreach ($searchable as $s) {
                    if (str_contains($s, $word) || str_contains($word, $s)) {
                        $score++;
                        break;
                    }
                }
            }
            if ($score > 0) {
                $scored[] = ['score' => $score, 'chunk' => $chunk];
            }
        }

        usort($scored, static fn ($a, $b) => $b['score'] <=> $a['score']);
        $relevantChunks = array_slice(array_column(array_slice($scored, 0, self::DEFAULT_MAX_CHUNKS), 'chunk'), 0, self::DEFAULT_MAX_CHUNKS);

        $totalChars = 0;
        $resultChunks = [];
        foreach ($relevantChunks as $chunk) {
            $text = ($chunk['path'] ?? '') . ' ' . ($chunk['label'] ?? '') . ' ' . ($chunk['comment'] ?? '');
            if ($totalChars + strlen($text) > self::MAX_CHARS) {
                break;
            }
            $resultChunks[] = $chunk;
            $totalChars += strlen($text);
        }

        return [
            'summary' => $summary,
            'relevant_chunks' => $resultChunks,
        ];
    }

    /**
     * Get config value for a specific path from the index.
     *
     * @param array $index Index from KnowledgeProvider::getIndex()
     * @param string $path Config path
     * @param string|null $scope Scope type (default, website, store)
     * @return mixed|null
     */
    public function getConfigValue(array $index, string $path, ?string $scope = null)
    {
        $configValues = $index['config_values'] ?? [];
        foreach ($configValues as $configValue) {
            if ($configValue['path'] === $path) {
                if ($scope === null || $configValue['scope'] === $scope) {
                    return $configValue['value'];
                }
            }
        }
        return null;
    }

    /**
     * Find config chunks matching a path pattern.
     *
     * @param array $index Index from KnowledgeProvider::getIndex()
     * @param string $pathPattern Path pattern (can be partial)
     * @return array<int, array{path: string, label: string, comment: string, searchable: string}>
     */
    public function findConfigChunksByPath(array $index, string $pathPattern): array
    {
        $chunks = $index['config_chunks'] ?? [];
        $matched = [];
        $patternLower = mb_strtolower($pathPattern, 'UTF-8');
        
        foreach ($chunks as $chunk) {
            $pathLower = mb_strtolower($chunk['path'] ?? '', 'UTF-8');
            if (str_contains($pathLower, $patternLower) || str_contains($patternLower, $pathLower)) {
                $matched[] = $chunk;
            }
        }
        
        return $matched;
    }

    /**
     * @return string[]
     */
    private function normalizeToWords(string $text): array
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text) ?? $text;
        $words = array_filter(explode(' ', $text));
        return array_values(array_unique($words));
    }
}
