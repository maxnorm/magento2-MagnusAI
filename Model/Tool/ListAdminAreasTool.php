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
use Magnus\Assistant\Model\Instance\DiscoveryProvider;

/**
 * Lists admin areas the agent can open (from discovery). Optional tool for "what can you open?".
 */
class ListAdminAreasTool implements ToolInterface, ToolMessageBuilderInterface
{
    private const MAX_ITEMS = 50;

    public function __construct(
        private readonly DiscoveryProvider $discoveryProvider
    ) {
    }

    /** @inheritdoc */
    public function supports(string $intent, array $context = []): bool
    {
        return $intent === 'list_admin_areas';
    }

    /** @inheritdoc */
    public function getToolName(): string
    {
        return 'list_admin_areas';
    }

    /** @inheritdoc */
    public function getToolDescription(): string
    {
        return 'List admin areas (menu items) that can be opened. Use when the user asks what pages you can open, or where they can go in the admin.';
    }

    /** @inheritdoc */
    public function getToolParameters(): array
    {
        return [
            'filter' => [
                'type' => 'string',
                'description' => 'Optional keyword to filter the list (e.g. catalog, order)',
            ],
        ];
    }

    /** @inheritdoc */
    public function buildMessageFromArguments(array $arguments): string
    {
        return 'List admin areas';
    }

    /** @inheritdoc */
    public function getToolExamples(): array
    {
        return [
            'What admin pages can you open?',
            'List available admin areas',
        ];
    }

    /** @inheritdoc */
    public function execute(MessageRequestInterface $request): ReplyResultInterface
    {
        try {
            $discovery = $this->discoveryProvider->getDiscoveryPayload();
            $adminAreas = array_slice($discovery['admin_areas'] ?? [], 0, self::MAX_ITEMS);

            $args = $request->getContext()['tool_arguments'] ?? [];
            $filter = isset($args['filter']) ? trim(mb_strtolower((string) $args['filter'], 'UTF-8')) : '';

            if ($filter !== '') {
                $adminAreas = array_values(array_filter(
                    $adminAreas,
                    fn(string $path): bool => str_contains(mb_strtolower($path, 'UTF-8'), $filter)
                ));
                $adminAreas = array_slice($adminAreas, 0, self::MAX_ITEMS);
            }

            if (empty($adminAreas)) {
                return new ReplyResult(
                    $filter !== ''
                        ? "No admin areas matched \"{$filter}\"."
                        : "No admin menu items are available."
                );
            }

            $lines = array_map(fn(string $path): string => '- ' . $path, $adminAreas);
            $reply = "Admin areas you can open:\n\n" . implode("\n", $lines);
            $reply .= "\n\nUse open_admin_page with the appropriate target (e.g. product_list, order_list) to get a link.";

            return new ReplyResult($reply);
        } catch (\Throwable $e) {
            return new ReplyResult('Could not load admin areas. Please try again.');
        }
    }
}
