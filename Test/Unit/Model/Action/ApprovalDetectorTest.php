<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Test\Unit\Model\Action;

use Magnus\Assistant\Api\ActionRegistryInterface;
use Magnus\Assistant\Api\Data\ActionProposalInterface;
use Magnus\Assistant\Model\Action\ApprovalDetector;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ApprovalDetectorTest extends TestCase
{
    /**
     * @var ActionRegistryInterface|MockObject
     */
    private $actionRegistry;

    /**
     * @var ApprovalDetector
     */
    private $detector;

    protected function setUp(): void
    {
        $this->actionRegistry = $this->createMock(ActionRegistryInterface::class);
        $this->detector = new ApprovalDetector($this->actionRegistry);
    }

    public function testDetectApprovalWithYes(): void
    {
        $proposal = $this->createMock(ActionProposalInterface::class);
        $this->actionRegistry->expects($this->once())
            ->method('getLatestPendingAction')
            ->with(123)
            ->willReturn($proposal);

        $result = $this->detector->detectApproval('yes', 123);
        $this->assertEquals('latest', $result);
    }

    public function testDetectApprovalWithApprove(): void
    {
        $proposal = $this->createMock(ActionProposalInterface::class);
        $this->actionRegistry->expects($this->once())
            ->method('getLatestPendingAction')
            ->with(123)
            ->willReturn($proposal);

        $result = $this->detector->detectApproval('approve', 123);
        $this->assertEquals('latest', $result);
    }

    public function testDetectApprovalCaseInsensitive(): void
    {
        $proposal = $this->createMock(ActionProposalInterface::class);
        $this->actionRegistry->expects($this->once())
            ->method('getLatestPendingAction')
            ->with(123)
            ->willReturn($proposal);

        $result = $this->detector->detectApproval('YES', 123);
        $this->assertEquals('latest', $result);
    }

    public function testDetectApprovalNoPendingAction(): void
    {
        $this->actionRegistry->expects($this->once())
            ->method('getLatestPendingAction')
            ->with(123)
            ->willReturn(null);

        $result = $this->detector->detectApproval('yes', 123);
        $this->assertNull($result);
    }

    public function testDetectApprovalWithDenial(): void
    {
        $proposal = $this->createMock(ActionProposalInterface::class);
        $this->actionRegistry->expects($this->once())
            ->method('getLatestPendingAction')
            ->with(123)
            ->willReturn($proposal);

        $result = $this->detector->detectApproval('no', 123);
        $this->assertNull($result);
    }

    public function testDetectApprovalWithCancel(): void
    {
        $proposal = $this->createMock(ActionProposalInterface::class);
        $this->actionRegistry->expects($this->once())
            ->method('getLatestPendingAction')
            ->with(123)
            ->willReturn($proposal);

        $result = $this->detector->detectApproval('cancel', 123);
        $this->assertNull($result);
    }

    public function testDetectApprovalEmptyMessage(): void
    {
        $this->actionRegistry->expects($this->never())
            ->method('getLatestPendingAction');

        $result = $this->detector->detectApproval('', 123);
        $this->assertNull($result);
    }
}
