<?php
/**
 * Copyright © Magnus. All rights reserved.
 */

declare(strict_types=1);

namespace Magnus\Assistant\Test\Unit\Model\Action;

use Magnus\Assistant\Model\Action\ProductCopyApplyAction;
use Magnus\Assistant\Model\Action\ActionResult;
use Magnus\Assistant\Model\Action\ActionPreview;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ProductCopyApplyActionTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var ProductCopyApplyAction
     */
    private $action;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->action = new ProductCopyApplyAction($this->productRepository);
    }

    public function testValidateRequiresProductId(): void
    {
        $this->assertFalse($this->action->validate(['name' => 'Test']));
        $this->assertFalse($this->action->validate(['product_id' => 0, 'name' => 'Test']));
    }

    public function testValidateRequiresAtLeastOneCopyField(): void
    {
        $this->assertFalse($this->action->validate(['product_id' => 42]));
        $this->assertTrue($this->action->validate(['product_id' => 42, 'name' => 'New Name']));
        $this->assertTrue($this->action->validate(['product_id' => 42, 'meta_title' => 'Meta']));
    }

    public function testPreviewReturnsSummaryAndDetails(): void
    {
        $params = ['product_id' => 99, 'name' => 'Updated Name'];
        $preview = $this->action->preview($params);
        $this->assertInstanceOf(ActionPreview::class, $preview);
        $this->assertStringContainsString('99', $preview->getSummary());
        $this->assertSame(99, $preview->getDetails()['product_id']);
        $this->assertSame('Updated Name', $preview->getDetails()['name']);
    }

    public function testExecuteFailsWhenProductNotFound(): void
    {
        $this->productRepository->method('getById')
            ->with(999)
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException(__('Not found')));

        $result = $this->action->execute(['product_id' => 999, 'name' => 'X']);
        $this->assertInstanceOf(ActionResult::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('not found', $result->getMessage());
    }

    public function testExecuteSavesProductWithProvidedCopy(): void
    {
        $product = $this->getMockBuilder(ProductInterface::class)
            ->addMethods(['setData', 'getData'])
            ->getMockForAbstractClass();
        $product->method('getId')->willReturn(42);
        $product->method('getName')->willReturn('Old Name');
        $product->expects($this->atLeastOnce())->method('setData')->withAnyParameters();

        $this->productRepository->expects($this->once())->method('getById')->with(42)->willReturn($product);
        $this->productRepository->expects($this->once())->method('save')->with($product)->willReturn($product);

        $result = $this->action->execute([
            'product_id' => 42,
            'name' => 'New Name',
            'meta_title' => 'New Meta',
        ]);

        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString('Product copy updated', $result->getMessage());
        $this->assertArrayHasKey('updated', $result->getData());
    }

    public function testGetDescriptionAndRequiresApproval(): void
    {
        $this->assertNotEmpty($this->action->getDescription());
        $this->assertTrue($this->action->requiresApproval());
    }
}
