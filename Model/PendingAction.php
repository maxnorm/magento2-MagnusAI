<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model;

use Magnus\Assistant\Model\ResourceModel\PendingAction as PendingActionResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Pending action model.
 */
class PendingAction extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'magnus_assistant_pending_action';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(PendingActionResource::class);
    }

    /**
     * Get conversation ID.
     *
     * @return int
     */
    public function getConversationId(): int
    {
        return (int) $this->getData('conversation_id');
    }

    /**
     * Set conversation ID.
     *
     * @param int $conversationId
     * @return void
     */
    public function setConversationId(int $conversationId): void
    {
        $this->setData('conversation_id', $conversationId);
    }

    /**
     * Get action type.
     *
     * @return string
     */
    public function getActionType(): string
    {
        return (string) $this->getData('action_type');
    }

    /**
     * Set action type.
     *
     * @param string $actionType
     * @return void
     */
    public function setActionType(string $actionType): void
    {
        $this->setData('action_type', $actionType);
    }

    /**
     * Get action parameters (JSON decoded).
     *
     * @return array<string, mixed>
     */
    public function getActionParams(): array
    {
        $params = $this->getData('action_params');
        if (is_string($params)) {
            $decoded = json_decode($params, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($params) ? $params : [];
    }

    /**
     * Set action parameters (JSON encoded).
     *
     * @param array<string, mixed> $params
     * @return void
     */
    public function setActionParams(array $params): void
    {
        $this->setData('action_params', json_encode($params));
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return (string) $this->getData('description');
    }

    /**
     * Set description.
     *
     * @param string $description
     * @return void
     */
    public function setDescription(string $description): void
    {
        $this->setData('description', $description);
    }

    /**
     * Get preview data (JSON decoded).
     *
     * @return array<string, mixed>
     */
    public function getPreviewData(): array
    {
        $preview = $this->getData('preview_data');
        if (is_string($preview)) {
            $decoded = json_decode($preview, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($preview) ? $preview : [];
    }

    /**
     * Set preview data (JSON encoded).
     *
     * @param array<string, mixed> $preview
     * @return void
     */
    public function setPreviewData(array $preview): void
    {
        $this->setData('preview_data', json_encode($preview));
    }

    /**
     * Get created at timestamp.
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return (string) $this->getData('created_at');
    }
}
