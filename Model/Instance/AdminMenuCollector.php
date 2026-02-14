<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Instance;

use Magento\Backend\Model\Menu;
use Magento\Backend\Model\Menu\Config as MenuConfig;
use Magento\Backend\Model\Menu\Item;
use Magento\Framework\App\State;

/**
 * Collects admin menu structure for discovery (admin areas the agent can open).
 * Must run in adminhtml area; uses emulateAreaCode when not.
 */
class AdminMenuCollector
{
    private const MAX_ITEMS = 100;

    public function __construct(
        private readonly MenuConfig $menuConfig,
        private readonly State $appState
    ) {
    }

    /**
     * Get flattened list of admin menu items (id, label, path, route).
     *
     * @return array<int, array{id: string, label: string, path: string, route: string|null}>
     */
    public function getItems(): array
    {
        $collect = function (): array {
            $menu = $this->menuConfig->getMenu();
            return $this->flattenMenu($menu, '');
        };

        try {
            if ($this->appState->getAreaCode() === 'adminhtml') {
                return $collect();
            }
            return $this->appState->emulateAreaCode('adminhtml', $collect);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Flatten menu tree into list of items with path prefix (e.g. "Catalog > Products").
     *
     * @param Menu $menu
     * @param string $pathPrefix
     * @return array<int, array{id: string, label: string, path: string, route: string|null}>
     */
    private function flattenMenu(Menu $menu, string $pathPrefix): array
    {
        $result = [];
        /** @var Item $item */
        foreach ($menu as $item) {
            $title = (string) $item->getTitle();
            $path = $pathPrefix !== '' ? $pathPrefix . ' > ' . $title : $title;
            $action = $item->getAction();
            $route = $action !== null ? $this->normalizeRoute($action) : null;

            $result[] = [
                'id' => $item->getId(),
                'label' => $title,
                'path' => $path,
                'route' => $route,
            ];

            if ($item->hasChildren() && count($result) < self::MAX_ITEMS) {
                $childItems = $this->flattenMenu($item->getChildren(), $path);
                $result = array_merge($result, $childItems);
            }

            if (count($result) >= self::MAX_ITEMS) {
                break;
            }
        }

        return $result;
    }

    private function normalizeRoute(string $action): string
    {
        // Action can be full route like "adminhtml/catalog/product/index" or path
        if (str_starts_with($action, 'adminhtml/')) {
            return $action;
        }
        return 'adminhtml/' . ltrim($action, '/');
    }
}
