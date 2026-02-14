<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Action;

use Magnus\Assistant\Api\ActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Updates a single system configuration path (path, value, scope).
 */
class ConfigUpdateAction implements ActionInterface
{
    private const SCOPE_DEFAULT = 'default';
    private const SCOPE_WEBSITE = 'website';
    private const SCOPE_STORE = 'store';
    private const VALID_SCOPES = [self::SCOPE_DEFAULT, self::SCOPE_WEBSITE, self::SCOPE_STORE];

    public function __construct(
        private readonly WriterInterface $configWriter,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(array $params): ActionResult
    {
        $path = trim((string) ($params['path'] ?? ''));
        $value = $params['value'] ?? '';
        $scopeParam = (string) ($params['scope'] ?? self::SCOPE_DEFAULT);
        $scope = $this->normalizeScopeForSave($scopeParam);
        $scopeId = $scopeParam === self::SCOPE_DEFAULT ? 0 : (int) ($params['scope_id'] ?? 0);

        try {
            $this->configWriter->save($path, (string) $value, $scope, $scopeId);
            return new ActionResult(
                true,
                (string) __("Config path %1 set to %2.", $path, $value)
            );
        } catch (\Throwable $e) {
            return new ActionResult(
                false,
                (string) __('Failed to save config: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function preview(array $params): ActionPreview
    {
        $path = trim((string) ($params['path'] ?? ''));
        $value = $params['value'] ?? '';
        $scope = (string) ($params['scope'] ?? self::SCOPE_DEFAULT);
        $scopeId = (int) ($params['scope_id'] ?? 0);

        $summary = (string) __("Set config path %1 to %2 (scope: %3).", $path, $value, $scope);
        $details = ['path' => $path, 'value' => $value, 'scope' => $scope, 'scope_id' => $scopeId];

        $current = null;
        if ($path !== '') {
            $scopeType = $this->scopeTypeForPreview($scope);
            $scopeCode = $scope !== self::SCOPE_DEFAULT && $scopeId > 0 ? (string) $scopeId : null;
            $current = $this->scopeConfig->getValue($path, $scopeType, $scopeCode);
        }
        if ($current !== null) {
            $details['current_value'] = $current;
            return new ActionPreview($summary, $details, ['before' => $current, 'after' => $value]);
        }

        return new ActionPreview($summary, $details);
    }

    /**
     * @inheritdoc
     */
    public function validate(array $params): bool
    {
        $path = trim((string) ($params['path'] ?? ''));
        if ($path === '') {
            return false;
        }
        $parts = explode('/', $path);
        if (count($parts) !== 3) {
            return false;
        }
        $scope = (string) ($params['scope'] ?? self::SCOPE_DEFAULT);
        if (!in_array($scope, self::VALID_SCOPES, true)) {
            return false;
        }
        if ($scope !== self::SCOPE_DEFAULT) {
            $scopeId = (int) ($params['scope_id'] ?? 0);
            if ($scopeId <= 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return 'Update a single system configuration path (path, value, scope).';
    }

    /**
     * @inheritdoc
     */
    public function requiresApproval(): bool
    {
        return true;
    }

    /**
     * Map user-facing scope to Magento config storage scope and scopeId.
     */
    private function normalizeScopeForSave(string $scope): string
    {
        return match ($scope) {
            self::SCOPE_WEBSITE => 'websites',
            self::SCOPE_STORE => 'stores',
            default => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        };
    }

    /**
     * Scope type for ScopeConfigInterface::getValue (default, website, store).
     */
    private function scopeTypeForPreview(string $scope): string
    {
        return match ($scope) {
            self::SCOPE_WEBSITE => 'website',
            self::SCOPE_STORE => 'store',
            default => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        };
    }
}
