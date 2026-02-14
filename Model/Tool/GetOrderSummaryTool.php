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
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Read-only order summary by order ID (increment_id or entity_id). Guarded by Magento_Sales.
 */
class GetOrderSummaryTool implements ToolInterface, ToolMessageBuilderInterface
{
    public function __construct(
        private readonly ModuleListInterface $moduleList,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly AdminUrl $adminUrlHelper
    ) {
    }

    /** @inheritdoc */
    public function supports(string $intent, array $context = []): bool
    {
        return $intent === 'get_order_summary';
    }

    /** @inheritdoc */
    public function getToolName(): string
    {
        return 'get_order_summary';
    }

    /** @inheritdoc */
    public function getToolDescription(): string
    {
        return 'Get a short summary of an order by order number (increment_id) or order ID (entity_id). Use when the user asks about a specific order. Returns status, total, customer email, created_at. Read-only.';
    }

    /** @inheritdoc */
    public function getToolParameters(): array
    {
        return [
            'order_id' => [
                'type' => 'string',
                'description' => 'Order increment ID (e.g. 100000001) or entity ID (numeric)',
            ],
        ];
    }

    /** @inheritdoc */
    public function buildMessageFromArguments(array $arguments): string
    {
        $orderId = $arguments['order_id'] ?? '';
        return $orderId !== '' ? "Get order summary: {$orderId}" : 'Get order summary';
    }

    /** @inheritdoc */
    public function getToolExamples(): array
    {
        return [
            'Show me order 100000001',
            'What is the status of order 42?',
        ];
    }

    /** @inheritdoc */
    public function execute(MessageRequestInterface $request): ReplyResultInterface
    {
        if (!$this->moduleList->isEnabled('Magento_Sales')) {
            return new ReplyResult('Sales module is not enabled on this store. Order lookup is not available.');
        }

        $args = $request->getContext()['tool_arguments'] ?? [];
        $orderIdInput = isset($args['order_id']) ? trim((string) $args['order_id']) : '';
        if ($orderIdInput === '') {
            $orderIdInput = $request->getMessage();
            if (preg_match('/\b(\d+)\b/', $orderIdInput, $m)) {
                $orderIdInput = $m[1];
            }
        }

        if ($orderIdInput === '') {
            return new ReplyResult('Please provide an order number or order ID.');
        }

        try {
            $order = null;
            if (is_numeric($orderIdInput)) {
                $id = (int) $orderIdInput;
                if ($id < 100000000) {
                    try {
                        $order = $this->orderRepository->get($id);
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        // Try as increment_id below
                    }
                }
                if ($order === null) {
                    $criteria = $this->searchCriteriaBuilder
                        ->addFilter('increment_id', $orderIdInput, 'eq')
                        ->create();
                    $list = $this->orderRepository->getList($criteria);
                    $items = $list->getItems();
                    $order = !empty($items) ? reset($items) : null;
                }
            } else {
                $criteria = $this->searchCriteriaBuilder
                    ->addFilter('increment_id', $orderIdInput, 'eq')
                    ->create();
                $list = $this->orderRepository->getList($criteria);
                $items = $list->getItems();
                $order = !empty($items) ? reset($items) : null;
            }

            if ($order === null) {
                return new ReplyResult("Order \"{$orderIdInput}\" not found.");
            }

            $incrementId = $order->getIncrementId();
            $status = $order->getStatus();
            $total = $order->getGrandTotal();
            $currency = $order->getOrderCurrencyCode();
            $customerEmail = $order->getCustomerEmail();
            $createdAt = $order->getCreatedAt();

            $reply = "**Order {$incrementId}**\n";
            $reply .= "Status: {$status}\n";
            $reply .= "Total: {$currency} " . number_format((float) $total, 2) . "\n";
            $reply .= "Customer: {$customerEmail}\n";
            $reply .= "Created: {$createdAt}";

            $orderUrl = $this->adminUrlHelper->getOrderUrl((int) $order->getId());

            return new ReplyResult(
                $reply,
                null,
                [
                    'type' => 'links',
                    'links' => [
                        ['text' => 'Open Order ' . $incrementId, 'url' => $orderUrl],
                    ],
                ]
            );
        } catch (\Throwable $e) {
            return new ReplyResult('An error occurred while loading the order. Please try again.');
        }
    }
}
