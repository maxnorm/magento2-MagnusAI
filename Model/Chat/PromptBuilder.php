<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Chat;

use Magnus\Assistant\Api\Data\MessageInterface;
use Magnus\Assistant\Model\Instance\DiscoveryProvider;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Builds LLM messages array from instance context and conversation history.
 * Includes function calling tools and action proposals.
 */
class PromptBuilder
{
    private const SYSTEM_PROMPT_BASE = 'You are Magnus, a helpful AI assistant for Magento store administrators and merchants. '
        . 'Your goal is to help them run their store: answer questions, fix problems, find where settings are, and guide them step-by-step. '
        . 'You have access to tools—use them whenever they fit the question. '
        . 'how_do_i: step-by-step procedures (add product, configure shipping, manage orders, create discount). '
        . 'explain_config: what a setting does and its current value. '
        . 'search_config_paths: find config paths by topic (payment, shipping, catalog). '
        . 'get_config_value: get current value for a config path. '
        . 'open_admin_page: build a link to open an admin page (products, orders, config, etc.). '
        . 'run_report: data questions (top products, revenue, sales, total product count). '
        . 'search_products: find products by name or SKU, then open_admin_page to open one. '
        . 'get_order_summary: get status, total, customer for one order by order number or ID. '
        . 'get_product_for_copy: fetch product name, descriptions, meta before proposing product_copy_apply. '
        . 'list_admin_areas: list which admin pages you can open. '
        . 'list_modules: what modules or extensions are installed. '
        . 'greeting: only for hellos or empty messages. '
        . 'You may combine tools (e.g. search config then open page, search_products then open product edit, get_order_summary then open order). Prefer using a tool over guessing. '
        . 'When users describe a problem (e.g. "orders not showing", "product not visible"), help them troubleshoot with the right tools. '
        . 'Be concise, practical, and merchant-friendly. If you do not know something, say so and suggest Magento docs or their developer. '
        . 'When listing tools, summarizing, or grouping information, use Markdown section titles: ## for main sections (e.g. ## Product Tools) and ### for subsections. '
        . 'Structure longer replies with ## headings so they are easy to scan.';

    private const TIER_SENTENCE = 'Read-only and navigation tools run automatically. '
        . 'Any change to data (create/update/delete) must be proposed with propose_action and requires the user to approve before execution.';

    private const MAX_CONTEXT_CHARS = 4000;
    private const MAX_ADMIN_AREAS_IN_PROMPT = 25;

    /**
     * @param \Magnus\Assistant\Model\Llm\FunctionSchemaBuilder|null $functionSchemaBuilder
     * @param \Magnus\Assistant\Model\Action\ActionProposalHandler|null $actionProposalHandler
     * @param DiscoveryProvider|null $discoveryProvider
     * @param Json|null $json
     */
    public function __construct(
        private readonly ?\Magnus\Assistant\Model\Llm\FunctionSchemaBuilder $functionSchemaBuilder = null,
        private readonly ?\Magnus\Assistant\Model\Action\ActionProposalHandler $actionProposalHandler = null,
        private readonly ?DiscoveryProvider $discoveryProvider = null,
        private readonly ?Json $json = null
    ) {
    }

    /**
     * Build messages for LLM: system (with instance context) + conversation.
     *
     * @param array $relevantContext From ContextRetriever: summary, relevant_chunks
     * @param bool $useInstanceKnowledge
     * @param MessageInterface[] $conversationMessages Last N messages (user/assistant)
     * @return array<int, array{role: string, content: string}>
     */
    public function build(
        array $relevantContext,
        bool $useInstanceKnowledge,
        array $conversationMessages
    ): array {
        $systemContent = self::SYSTEM_PROMPT_BASE;

        // Add instance knowledge and discovery (store context)
        if ($useInstanceKnowledge) {
            $summary = $relevantContext['summary'] ?? [];
            $moduleCount = $summary['module_count'] ?? 0;
            $configCount = $summary['config_path_count'] ?? 0;
            $systemContent .= "\n\nThis store has {$moduleCount} enabled modules and {$configCount} config paths.";

            if ($this->discoveryProvider !== null) {
                try {
                    $discovery = $this->discoveryProvider->getDiscoveryPayload();
                    $capabilities = $discovery['capabilities'] ?? [];
                    $storeScope = $discovery['store_scope'] ?? [];
                    $adminAreas = array_slice($discovery['admin_areas'] ?? [], 0, self::MAX_ADMIN_AREAS_IN_PROMPT);
                    if (!empty($capabilities)) {
                        $systemContent .= "\nCapabilities: " . implode(', ', $capabilities) . '.';
                    }
                    if (!empty($storeScope)) {
                        $w = $storeScope['website_count'] ?? 0;
                        $s = $storeScope['store_count'] ?? 0;
                        $currency = $storeScope['default_currency'] ?? 'USD';
                        $systemContent .= "\nStore scope: {$w} website(s), {$s} store view(s), default currency {$currency}.";
                    }
                    if (!empty($adminAreas)) {
                        $systemContent .= "\nAdmin areas you can open: " . implode(', ', $adminAreas) . '.';
                    }
                } catch (\Throwable $e) {
                    // Non-fatal: continue without discovery block
                }
            }

            $chunks = $relevantContext['relevant_chunks'] ?? [];
            if (!empty($chunks)) {
                $contextText = '';
                foreach ($chunks as $chunk) {
                    $line = ($chunk['path'] ?? '') . ': ' . ($chunk['label'] ?? '') . "\n";
                    if (strlen($contextText) + strlen($line) > self::MAX_CONTEXT_CHARS) {
                        break;
                    }
                    $contextText .= $line;
                }
                if ($contextText !== '') {
                    $systemContent .= "\n\nRelevant config context (use only when answering about these):\n" . $contextText;
                }
            }
        }

        // Add tool descriptions if available
        if ($this->functionSchemaBuilder !== null) {
            $systemContent .= "\n\nAvailable tools:";
            $toolSchemas = $this->functionSchemaBuilder->buildToolSchemas();
            foreach ($toolSchemas as $schema) {
                $function = $schema['function'] ?? [];
                $name = $function['name'] ?? '';
                $description = $function['description'] ?? '';
                if ($name && $description) {
                    $systemContent .= "\n- {$name}: {$description}";
                }
            }
        }

        // Tier and action proposal guidance
        $systemContent .= "\n\n" . self::TIER_SENTENCE;
        $systemContent .= "\n\nWhen users want to change data (create products, update config, create discounts, etc.), "
            . "use propose_action; the action will require user approval before execution.";

        if ($this->actionProposalHandler !== null) {
            $availableActions = $this->actionProposalHandler->getAvailableActions();
            if (!empty($availableActions)) {
                $actionLines = array_map(
                    fn(array $a): string => '- ' . ($a['type'] ?? '') . ': ' . ($a['description'] ?? ''),
                    $availableActions
                );
                $systemContent .= "\n\nAvailable write actions (use these as action_type in propose_action):\n"
                    . implode("\n", $actionLines);
                $systemContent .= "\n\nFor product copy: use get_product_for_copy to fetch product attributes, "
                    . "then propose product_copy_apply with the suggested name, descriptions, and meta.";
            }
        }

        $messages = [['role' => 'system', 'content' => $systemContent]];

        foreach ($conversationMessages as $msg) {
            $role = $msg->getRole() === 'user' ? 'user' : 'assistant';
            $content = $msg->getContent();

            if ($role === 'assistant' && $this->json !== null) {
                $toolContextRaw = $msg->getToolContext();
                if ($toolContextRaw !== null && $toolContextRaw !== '') {
                    try {
                        $decoded = $this->json->unserialize($toolContextRaw);
                        if (is_array($decoded)) {
                            foreach ($decoded as $entry) {
                                if (!is_array($entry)) {
                                    continue;
                                }
                                $replayed = $this->replayToolContextEntry($entry);
                                if ($replayed !== null) {
                                    $messages[] = $replayed;
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        // Non-fatal: fall back to content-only message
                    }
                }
            }

            $message = ['role' => $role];
            if ($content !== null && $content !== '') {
                $message['content'] = $content;
            }
            $messages[] = $message;
        }

        return $messages;
    }

    /**
     * Build one LLM message from a persisted tool-context entry.
     *
     * @param array<string, mixed> $entry
     * @return array<string, mixed>|null
     */
    private function replayToolContextEntry(array $entry): ?array
    {
        $entryRole = $entry['role'] ?? '';
        if ($entryRole === 'assistant') {
            $m = ['role' => 'assistant'];
            if (isset($entry['content']) && $entry['content'] !== null && $entry['content'] !== '') {
                $m['content'] = $entry['content'];
            }
            if (!empty($entry['tool_calls']) && is_array($entry['tool_calls'])) {
                $m['tool_calls'] = $entry['tool_calls'];
            }
            return $m;
        }
        if ($entryRole === 'tool') {
            return [
                'role' => 'tool',
                'tool_call_id' => (string) ($entry['tool_call_id'] ?? ''),
                'content' => (string) ($entry['content'] ?? ''),
            ];
        }
        return null;
    }
}
