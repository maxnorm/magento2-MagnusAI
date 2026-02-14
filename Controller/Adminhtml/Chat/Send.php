<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Controller\Adminhtml\Chat;

use Magnus\Assistant\Exception\RateLimitExceededException;
use Magnus\Assistant\Model\Chat\ChatService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;

class Send extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Magnus_Assistant::assistant';

    private const MAX_MESSAGE_LENGTH = 10000;

    public function __construct(
        Context $context,
        private readonly ChatService $chatService,
        private readonly State $appState,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $body = $this->getRequest()->getContent();
            $data = $body ? json_decode($body, true) : [];
            if (!is_array($data)) {
                $data = [];
            }

            // Also check POST params as fallback (in case Magento modifies the request)
            if (empty($data) || !isset($data['message'])) {
                $postData = $this->getRequest()->getPostValue();
                if (is_array($postData) && isset($postData['message'])) {
                    $data = $postData;
                }
            }

            $message = isset($data['message']) ? trim((string) $data['message']) : '';
            if ($message === '') {
                $result->setHttpResponseCode(400);
                $result->setData(['error' => (string) __('Message is required.')]);
                return $result;
            }

            if (strlen($message) > self::MAX_MESSAGE_LENGTH) {
                $result->setHttpResponseCode(400);
                $result->setData(['error' => (string) __('Message is too long.')]);
                return $result;
            }

            $conversationId = isset($data['conversation_id']) ? (int) $data['conversation_id'] : null;
            $context = isset($data['context']) && is_array($data['context']) ? $data['context'] : [];

            $adminUserId = (int) $this->_auth->getUser()->getId();

            $response = $this->chatService->send($message, $adminUserId, $conversationId, $context);
            $result->setData($response);
            return $result;
        } catch (RateLimitExceededException $e) {
            $result->setHttpResponseCode(429);
            $errorMessage = trim((string) $e->getMessage());
            if ($errorMessage === '') {
                $errorMessage = (string) __('Rate limit exceeded. Please try again later.');
            }
            $errorData = ['error' => $errorMessage];
            if ($this->isDeveloperMode()) {
                $errorData['dev_details'] = $this->getErrorDetails($e);
            }
            $result->setData($errorData);
            return $result;
        } catch (LocalizedException $e) {
            $result->setHttpResponseCode(500);
            $errorMessage = trim((string) $e->getMessage());
            if ($errorMessage === '') {
                $errorMessage = (string) __('An error occurred (%1). Please try again.', get_class($e));
            }
            $errorData = ['error' => $errorMessage];
            if ($this->isDeveloperMode()) {
                $errorData['dev_details'] = $this->getErrorDetails($e);
            }
            $result->setData($errorData);
            return $result;
        } catch (\Throwable $e) {
            $result->setHttpResponseCode(500);
            $errorMessage = trim((string) $e->getMessage());
            if ($errorMessage === '') {
                $errorMessage = (string) __('An error occurred (%1). Please try again.', get_class($e));
            }
            $errorData = ['error' => $errorMessage];
            if ($this->isDeveloperMode()) {
                $errorData['dev_details'] = $this->getErrorDetails($e);
            }
            $result->setData($errorData);
            return $result;
        }
    }

    /**
     * Check if dev error details should be enabled.
     * Checks both module configuration and Magento developer mode.
     */
    private function isDeveloperMode(): bool
    {
        // Check module configuration first
        $configEnabled = $this->scopeConfig->isSetFlag(
            'magnus/assistant/enable_dev_error_details',
            ScopeInterface::SCOPE_TYPE_DEFAULT
        );
        
        if ($configEnabled) {
            return true;
        }
        
        // Fallback to Magento developer mode check
        try {
            return $this->appState->getMode() === State::MODE_DEVELOPER;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get detailed error information for developer mode.
     *
     * @param \Throwable $e
     * @return array
     */
    private function getErrorDetails(\Throwable $e): array
    {
        $details = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'code' => $e->getCode(),
        ];

        if ($e->getPrevious()) {
            $details['previous'] = [
                'message' => $e->getPrevious()->getMessage(),
                'file' => $e->getPrevious()->getFile(),
                'line' => $e->getPrevious()->getLine(),
            ];
        }

        $trace = $e->getTraceAsString();
        if ($trace) {
            $details['trace'] = $trace;
        }

        return $details;
    }
}
