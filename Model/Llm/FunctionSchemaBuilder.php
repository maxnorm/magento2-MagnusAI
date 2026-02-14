<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Llm;

use Magnus\Assistant\Api\ToolInterface;

/**
 * Builds OpenAI function calling schemas from tools.
 */
class FunctionSchemaBuilder
{
    /**
     * @param ToolInterface[] $tools
     */
    public function __construct(
        private readonly array $tools = []
    ) {
    }

    /**
     * Build function schemas for all available tools.
     *
     * @return array<int, array{type: string, function: array{name: string, description: string, parameters: array}}>
     */
    public function buildToolSchemas(): array
    {
        $schemas = [];
        foreach ($this->tools as $tool) {
            $schemas[] = $this->buildSchemaFromTool($tool);
        }
        return $schemas;
    }

    /**
     * Build function schema for a single tool.
     *
     * @param ToolInterface $tool
     * @return array{type: string, function: array{name: string, description: string, parameters: array}}
     */
    private function buildSchemaFromTool(ToolInterface $tool): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $tool->getToolName(),
                'description' => $tool->getToolDescription(),
                'parameters' => [
                    'type' => 'object',
                    'properties' => $tool->getToolParameters(),
                    'required' => $this->getRequiredParameters($tool->getToolParameters()),
                ],
            ],
        ];
    }

    /**
     * Get required parameters from parameter schema.
     *
     * @param array<string, array{type: string, description: string}> $parameters
     * @return string[]
     */
    private function getRequiredParameters(array $parameters): array
    {
        return array_keys($parameters);
    }

    /**
     * Build action proposal function schema.
     *
     * @return array{type: string, function: array{name: string, description: string, parameters: array}}
     */
    public function buildActionProposalSchema(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'propose_action',
                'description' => 'Propose an action to be executed in the Magento store. Use this when the user wants to perform a task that modifies data (create product, update config, create discount, etc.)',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'action_type' => [
                            'type' => 'string',
                            'description' => 'The type of action to perform (e.g., "product_create", "config_update", "discount_create")',
                        ],
                        'params' => [
                            'type' => 'object',
                            'description' => 'Parameters for the action',
                            'additionalProperties' => true,
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Human-readable description of what this action will do',
                        ],
                        'preview_data' => [
                            'type' => 'object',
                            'description' => 'Preview data to show the user before execution',
                            'additionalProperties' => true,
                        ],
                    ],
                    'required' => ['action_type', 'params', 'description'],
                ],
            ],
        ];
    }

    /**
     * Build all function schemas (tools + actions).
     *
     * @return array<int, array{type: string, function: array{name: string, description: string, parameters: array}}>
     */
    public function buildAllSchemas(): array
    {
        $schemas = $this->buildToolSchemas();
        $schemas[] = $this->buildActionProposalSchema();
        return $schemas;
    }
}
