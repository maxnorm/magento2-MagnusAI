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
 * Lists enabled modules from instance knowledge.
 */
class DemoModuleListTool implements ToolInterface, ToolMessageBuilderInterface
{
    public function __construct(
        private readonly KnowledgeProvider $knowledgeProvider
    ) {
    }

    /** @inheritdoc */
    public function supports(string $intent, array $context = []): bool
    {
        return $intent === 'list_modules';
    }

    /** @inheritdoc */
    public function getToolName(): string
    {
        return 'list_modules';
    }

    /** @inheritdoc */
    public function getToolDescription(): string
    {
        return 'Lists all enabled modules/extensions in the Magento instance. Use this when users ask about installed modules, extensions, or what modules are available.';
    }

    /** @inheritdoc */
    public function getToolParameters(): array
    {
        return [];
    }

    /** @inheritdoc */
    public function buildMessageFromArguments(array $arguments): string
    {
        return 'What modules do I have?';
    }

    /** @inheritdoc */
    public function getToolExamples(): array
    {
        return [
            'What modules do I have?',
            'List installed extensions',
            'Show me enabled modules',
        ];
    }

    /** @inheritdoc */
    public function execute(MessageRequestInterface $request): ReplyResultInterface
    {
        $index = $this->knowledgeProvider->getIndex();
        $moduleNames = $index['module_names'] ?? [];
        $count = count($moduleNames);

        if ($count === 0) {
            return new ReplyResult('No enabled modules were found in this instance.');
        }

        $list = implode("\n", array_slice($moduleNames, 0, 100));
        if ($count > 100) {
            $list .= "\n... and " . ($count - 100) . " more.";
        }
        return new ReplyResult("This store has {$count} enabled modules:\n\n" . $list);
    }
}
