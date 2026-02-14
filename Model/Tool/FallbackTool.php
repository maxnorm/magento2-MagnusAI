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

/**
 * Handles greeting / empty message.
 */
class FallbackTool implements ToolInterface, ToolMessageBuilderInterface
{
    private const GREETING = "I'm Magnus, your Magento admin assistant. I can help you with:\n"
        . "• **How-to steps** — e.g. add a product, configure shipping, manage orders, create a discount\n"
        . "• **Configuration** — what a setting does and where to find it\n"
        . "• **Reports** — e.g. top products by revenue\n"
        . "• **Installed modules** — what extensions are enabled\n"
        . 'Describe a problem (e.g. "orders not showing", "product not visible") and I\'ll help you troubleshoot.';

    /** @inheritdoc */
    public function supports(string $intent, array $context = []): bool
    {
        return $intent === 'greeting';
    }

    /** @inheritdoc */
    public function getToolName(): string
    {
        return 'greeting';
    }

    /** @inheritdoc */
    public function getToolDescription(): string
    {
        return 'Use only for greetings, empty messages, or when the user says hello/hi. Returns a short overview of what Magnus can do. Do not use for actual questions.';
    }

    /** @inheritdoc */
    public function getToolParameters(): array
    {
        return [];
    }

    /** @inheritdoc */
    public function buildMessageFromArguments(array $arguments): string
    {
        return '';
    }

    /** @inheritdoc */
    public function getToolExamples(): array
    {
        return [
            'Hello',
            'Hi',
            '',
        ];
    }

    /** @inheritdoc */
    public function execute(MessageRequestInterface $request): ReplyResultInterface
    {
        return new ReplyResult(self::GREETING);
    }
}
