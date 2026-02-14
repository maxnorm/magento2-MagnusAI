<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Instance;

use Magento\Config\Model\Config\Structure;
use Magento\Config\Model\Config\Structure\Element\Field;

/**
 * Collects config schema (path, label, comment) from system config structure.
 * Excludes secret/encrypted fields; no actual values stored.
 */
class ConfigSchemaCollector
{
    private const BACKEND_MODEL_ENCRYPTED = 'Magento\Config\Model\Config\Backend\Encrypted';

    public function __construct(
        private readonly Structure $structure
    ) {
    }

    /**
     * Get config chunks: each with path, label, comment, searchable text.
     *
     * @return array<int, array{path: string, label: string, comment: string, searchable: string}>
     */
    public function getChunks(): array
    {
        $chunks = [];
        $tabs = $this->structure->getTabs();
        foreach ($tabs as $tab) {
            $sections = $tab->getChildren();
            if (!$sections) {
                continue;
            }
            foreach ($sections as $section) {
                if (!$section->isVisible()) {
                    continue;
                }
                $groups = $section->getChildren();
                if (!$groups) {
                    continue;
                }
                foreach ($groups as $group) {
                    $fields = $group->getChildren();
                    if (!$fields) {
                        continue;
                    }
                    foreach ($fields as $field) {
                        if (!$field instanceof Field || !$field->isVisible()) {
                            continue;
                        }
                        $backendModel = $field->getAttribute('backend_model');
                        if ($backendModel && str_contains((string) $backendModel, 'Encrypted')) {
                            continue;
                        }
                        $path = $field->getPath();
                        $label = (string) $field->getLabel();
                        $commentAttr = $field->getAttribute('comment');
                        $comment = is_string($commentAttr) ? $commentAttr : '';
                        $searchable = implode(' ', array_filter([$path, $label, $comment]));
                        $chunks[] = [
                            'path' => $path,
                            'label' => $label,
                            'comment' => $comment,
                            'searchable' => $searchable,
                        ];
                    }
                }
            }
        }
        return $chunks;
    }
}
