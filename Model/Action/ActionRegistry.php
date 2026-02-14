<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Model\Action;

use Magnus\Assistant\Api\ActionRegistryInterface;
use Magnus\Assistant\Api\Data\ActionProposalInterface;
use Magnus\Assistant\Model\PendingActionFactory;
use Magnus\Assistant\Model\ResourceModel\PendingAction\CollectionFactory;
use Magnus\Assistant\Model\ResourceModel\PendingAction as PendingActionResource;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Registry for storing and retrieving pending actions.
 */
class ActionRegistry implements ActionRegistryInterface
{
    private const EXPIRY_HOURS = 1;

    /**
     * @param PendingActionFactory $pendingActionFactory
     * @param CollectionFactory $collectionFactory
     * @param PendingActionResource $resource
     * @param ObjectManagerInterface $objectManager
     * @param Json $json
     */
    public function __construct(
        private readonly PendingActionFactory $pendingActionFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly PendingActionResource $resource,
        private readonly ObjectManagerInterface $objectManager,
        private readonly Json $json
    ) {
    }

    /**
     * @inheritdoc
     */
    public function storePendingAction(int $conversationId, ActionProposalInterface $proposal): string
    {
        $pendingAction = $this->pendingActionFactory->create();
        $pendingAction->setConversationId($conversationId);
        $pendingAction->setActionType($proposal->getActionType());
        $pendingAction->setActionParams($proposal->getParams());
        $pendingAction->setDescription($proposal->getDescription());
        $pendingAction->setPreviewData($proposal->getPreview());

        $this->resource->save($pendingAction);

        return (string) $pendingAction->getId();
    }

    /**
     * @inheritdoc
     */
    public function getPendingAction(int $conversationId, string $actionId): ?ActionProposalInterface
    {
        $pendingAction = $this->pendingActionFactory->create();
        $this->resource->load($pendingAction, $actionId);

        if (!$pendingAction->getId() || $pendingAction->getConversationId() !== $conversationId) {
            return null;
        }

        // Check expiry
        $createdAt = strtotime($pendingAction->getCreatedAt());
        if ($createdAt === false || (time() - $createdAt) > (self::EXPIRY_HOURS * 3600)) {
            $this->clearPendingAction($conversationId, $actionId);
            return null;
        }

        return $this->reconstructProposal($pendingAction, $conversationId);
    }

    /**
     * @inheritdoc
     */
    public function clearPendingAction(int $conversationId, string $actionId): void
    {
        // Handle 'latest' special case
        if ($actionId === 'latest') {
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('conversation_id', $conversationId);
            $collection->setOrder('created_at', 'DESC');
            $collection->setPageSize(1);
            $pendingAction = $collection->getFirstItem();
            if ($pendingAction->getId()) {
                $this->resource->delete($pendingAction);
            }
            return;
        }

        $pendingAction = $this->pendingActionFactory->create();
        $this->resource->load($pendingAction, $actionId);

        if ($pendingAction->getId() && $pendingAction->getConversationId() === $conversationId) {
            $this->resource->delete($pendingAction);
        }
    }

    /**
     * @inheritdoc
     */
    public function getPendingActionsForConversation(int $conversationId): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('conversation_id', $conversationId);
        $collection->setOrder('created_at', 'DESC');

        $proposals = [];
        foreach ($collection as $pendingAction) {
            $proposal = $this->reconstructProposal($pendingAction, $conversationId);
            if ($proposal !== null) {
                $proposals[] = $proposal;
            }
        }

        return $proposals;
    }

    /**
     * @inheritdoc
     */
    public function getLatestPendingAction(int $conversationId): ?ActionProposalInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('conversation_id', $conversationId);
        $collection->setOrder('created_at', 'DESC');
        $collection->setPageSize(1);

        $pendingAction = $collection->getFirstItem();
        if (!$pendingAction->getId()) {
            return null;
        }

        // Check expiry
        $createdAt = strtotime($pendingAction->getCreatedAt());
        if ($createdAt === false || (time() - $createdAt) > (self::EXPIRY_HOURS * 3600)) {
            $this->clearPendingAction($conversationId, (string) $pendingAction->getId());
            return null;
        }

        return $this->reconstructProposal($pendingAction, $conversationId);
    }

    /**
     * @inheritdoc
     */
    public function cleanupExpired(): int
    {
        $expiryTime = date('Y-m-d H:i:s', time() - (self::EXPIRY_HOURS * 3600));
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('created_at', ['lt' => $expiryTime]);

        $count = $collection->getSize();
        foreach ($collection as $pendingAction) {
            $this->resource->delete($pendingAction);
        }

        return $count;
    }

    /**
     * Reconstruct ActionProposal from stored data.
     *
     * @param \Magnus\Assistant\Model\PendingAction $pendingAction
     * @param int $conversationId
     * @return ActionProposalInterface|null
     */
    private function reconstructProposal(
        \Magnus\Assistant\Model\PendingAction $pendingAction,
        int $conversationId
    ): ?ActionProposalInterface {
        try {
            $actionType = $pendingAction->getActionType();
            if (!class_exists($actionType) || !is_subclass_of($actionType, \Magnus\Assistant\Api\ActionInterface::class)) {
                return null;
            }

            $action = $this->objectManager->get($actionType);
            $params = $pendingAction->getActionParams();
            $previewData = $pendingAction->getPreviewData();

            $preview = new ActionPreview(
                $previewData['summary'] ?? '',
                $previewData['details'] ?? [],
                $previewData['diff'] ?? null
            );

            return new ActionProposal(
                $action,
                $pendingAction->getDescription(),
                $preview,
                $conversationId,
                $params
            );
        } catch (\Throwable $e) {
            return null;
        }
    }
}
