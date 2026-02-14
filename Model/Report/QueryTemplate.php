<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Report;

/**
 * Query template definition for safe read-only reports.
 */
class QueryTemplate
{
    /**
     * @param string $id
     * @param string $pattern
     * @param string $handler
     * @param array<string, mixed> $defaultParams
     */
    public function __construct(
        private readonly string $id,
        private readonly string $pattern,
        private readonly string $handler,
        private readonly array $defaultParams = []
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getHandler(): string
    {
        return $this->handler;
    }

    public function getDefaultParams(): array
    {
        return $this->defaultParams;
    }

    /**
     * Match message against pattern and extract parameters.
     *
     * @param string $message
     * @return array{matched: bool, params: array}
     */
    public function match(string $message): array
    {
        if (preg_match($this->pattern, $message, $matches)) {
            $params = $this->defaultParams;
            
            // Extract limit (first capture group)
            if (isset($matches[1]) && $matches[1] !== '') {
                $params['limit'] = (int) $matches[1];
            }
            
            // Extract days (second capture group)
            if (isset($matches[2]) && $matches[2] !== '') {
                $params['days'] = (int) $matches[2];
            }
            
            return ['matched' => true, 'params' => $params];
        }
        
        return ['matched' => false, 'params' => $this->defaultParams];
    }
}
