<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Action;

use Magnus\Assistant\Api\ActionInterface;
use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Model\Rule;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\CatalogRule\Model\Rule\Condition\Combine;
use Magento\CatalogRule\Model\Rule\Condition\Product;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Create a catalog price rule (name, discount type and amount, dates, optional category or all products).
 */
class CatalogPriceRuleCreateAction implements ActionInterface
{
    private const SIMPLE_ACTION_BY_PERCENT = 'by_percent';
    private const SIMPLE_ACTION_BY_FIXED = 'by_fixed';
    private const VALID_SIMPLE_ACTIONS = [self::SIMPLE_ACTION_BY_PERCENT, self::SIMPLE_ACTION_BY_FIXED];

    public function __construct(
        private readonly CatalogRuleRepositoryInterface $ruleRepository,
        private readonly RuleFactory $ruleFactory,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(array $params): ActionResult
    {
        $name = trim((string) ($params['name'] ?? ''));
        $description = trim((string) ($params['description'] ?? ''));
        $discountAmount = (float) ($params['discount_amount'] ?? 0);
        $simpleAction = (string) ($params['simple_action'] ?? self::SIMPLE_ACTION_BY_PERCENT);
        $fromDate = (string) ($params['from_date'] ?? '');
        $toDate = isset($params['to_date']) && $params['to_date'] !== '' ? (string) $params['to_date'] : null;
        $websiteIds = $this->normalizeWebsiteIds($params['website_ids'] ?? null);
        $customerGroupIds = $this->normalizeCustomerGroupIds($params['customer_group_ids'] ?? null);
        $categoryIds = $this->normalizeCategoryIds($params['category_ids'] ?? null);

        $rule = $this->ruleFactory->create();
        $rule->setName($name);
        $rule->setDescription($description);
        $rule->setIsActive(1);
        $rule->setDiscountAmount($discountAmount);
        $rule->setSimpleAction($simpleAction);
        $rule->setFromDate($fromDate);
        if ($toDate !== null) {
            $rule->setToDate($toDate);
        }
        $rule->setWebsiteIds($websiteIds);
        $rule->setCustomerGroupIds($customerGroupIds);
        $rule->setStopRulesProcessing(0);

        $conditions = $this->buildConditionsArray($categoryIds);
        $rule->getConditions()->setConditions([])->loadArray($conditions);

        try {
            $saved = $this->ruleRepository->save($rule);
            $ruleId = (int) $saved->getRuleId();
            return new ActionResult(
                true,
                (string) __('Catalog price rule "%1" created (ID: %2).', $name, $ruleId),
                ['rule_id' => $ruleId, 'name' => $name]
            );
        } catch (\Throwable $e) {
            return new ActionResult(
                false,
                (string) __('Failed to create catalog price rule: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function preview(array $params): ActionPreview
    {
        $name = trim((string) ($params['name'] ?? ''));
        $discountAmount = (float) ($params['discount_amount'] ?? 0);
        $simpleAction = (string) ($params['simple_action'] ?? self::SIMPLE_ACTION_BY_PERCENT);
        $fromDate = (string) ($params['from_date'] ?? '');
        $toDate = $params['to_date'] ?? null;
        $categoryIds = $this->normalizeCategoryIds($params['category_ids'] ?? null);

        $discountLabel = $simpleAction === self::SIMPLE_ACTION_BY_PERCENT
            ? $discountAmount . '% off'
            : '$' . number_format((float) $discountAmount, 2) . ' off';
        $scopeLabel = !empty($categoryIds)
            ? 'for category IDs: ' . implode(', ', $categoryIds)
            : 'for all products';

        $summary = sprintf(
            'Create catalog price rule: %s, %s, from %s to %s, %s.',
            $name,
            $discountLabel,
            $fromDate,
            $toDate !== null && $toDate !== '' ? $toDate : 'no end date',
            $scopeLabel
        );
        $details = [
            'name' => $name,
            'discount_amount' => $discountAmount,
            'simple_action' => $simpleAction,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'category_ids' => $categoryIds,
        ];
        return new ActionPreview($summary, $details);
    }

    /**
     * @inheritdoc
     */
    public function validate(array $params): bool
    {
        $name = trim((string) ($params['name'] ?? ''));
        if ($name === '') {
            return false;
        }
        $discountAmount = $params['discount_amount'] ?? null;
        if ($discountAmount === null || $discountAmount === '') {
            return false;
        }
        if (is_numeric($discountAmount)) {
            $amount = (float) $discountAmount;
            $simpleAction = (string) ($params['simple_action'] ?? self::SIMPLE_ACTION_BY_PERCENT);
            if (!in_array($simpleAction, self::VALID_SIMPLE_ACTIONS, true)) {
                return false;
            }
            if ($simpleAction === self::SIMPLE_ACTION_BY_PERCENT && ($amount < 0 || $amount > 100)) {
                return false;
            }
            if ($simpleAction === self::SIMPLE_ACTION_BY_FIXED && $amount < 0) {
                return false;
            }
        } else {
            return false;
        }
        $fromDate = (string) ($params['from_date'] ?? '');
        if ($fromDate === '') {
            return false;
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return 'Create a catalog price rule (name, discount type and amount, dates, optional category or all products).';
    }

    /**
     * @inheritdoc
     */
    public function requiresApproval(): bool
    {
        return true;
    }

    private function buildConditionsArray(?array $categoryIds): array
    {
        $base = [
            'type' => Combine::class,
            'aggregator' => 'all',
            'value' => '1',
            'conditions' => [],
        ];
        if (!empty($categoryIds)) {
            $base['conditions'] = [
                [
                    'type' => Product::class,
                    'attribute' => 'category_ids',
                    'operator' => '()',
                    'value' => implode(',', $categoryIds),
                ],
            ];
        }
        return $base;
    }

    private function normalizeWebsiteIds(mixed $websiteIds): string
    {
        if ($websiteIds === null || $websiteIds === '') {
            $ids = [];
            foreach ($this->storeManager->getWebsites() as $website) {
                $ids[] = $website->getId();
            }
            return implode(',', $ids);
        }
        if (is_array($websiteIds)) {
            return implode(',', array_map('intval', $websiteIds));
        }
        return (string) $websiteIds;
    }

    private function normalizeCustomerGroupIds(mixed $customerGroupIds): string
    {
        if ($customerGroupIds === null || $customerGroupIds === '') {
            return '0'; // All customer groups
        }
        if (is_array($customerGroupIds)) {
            return implode(',', array_map('intval', $customerGroupIds));
        }
        return (string) $customerGroupIds;
    }

    private function normalizeCategoryIds(mixed $categoryIds): ?array
    {
        if ($categoryIds === null || $categoryIds === '' || $categoryIds === []) {
            return null;
        }
        if (is_array($categoryIds)) {
            return array_values(array_map('intval', $categoryIds));
        }
        $parts = array_map('trim', explode(',', (string) $categoryIds));
        $filtered = array_filter($parts, fn(string $p): bool => $p !== '' && is_numeric($p));
        return $filtered !== [] ? array_map('intval', $filtered) : null;
    }
}
