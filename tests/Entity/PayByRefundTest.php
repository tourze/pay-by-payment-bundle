<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByOrder;
use Tourze\PayByPaymentBundle\Entity\PayByRefund;
use Tourze\PayByPaymentBundle\Enum\PayByRefundStatus;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(PayByRefund::class)]
class PayByRefundTest extends AbstractEntityTestCase
{
    private PayByRefund $refund;

    protected function setUp(): void
    {
        $this->refund = $this->createEntity();
    }

    protected function createEntity(): PayByRefund
    {
        return new PayByRefund();
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $properties = [
            'refundId' => 'test_refund_123',
            'merchantRefundNo' => 'merchant_refund_123',
            'refundReason' => 'Test refund reason',
            'notifyUrl' => 'https://example.com/notify',
        ];

        foreach ($properties as $property => $sampleValue) {
            yield $property => [$property, $sampleValue];
        }
    }

    public function testConstructor(): void
    {
        $refund = new PayByRefund();

        $this->assertSame(PayByRefundStatus::PENDING, $refund->getStatus());
        // TimestampableAware trait时间戳字段在实体持久化时由Doctrine监听器自动设置
        $this->assertNull($refund->getCreateTime());
        $this->assertNull($refund->getUpdateTime());
    }

    public function testRefundIdGetterAndSetter(): void
    {
        $refundId = 'refund-123456';
        $this->refund->setRefundId($refundId);

        $this->assertSame($refundId, $this->refund->getRefundId());
    }

    public function testMerchantRefundNoGetterAndSetter(): void
    {
        $merchantRefundNo = 'merchant-refund-123';
        $this->refund->setMerchantRefundNo($merchantRefundNo);

        $this->assertSame($merchantRefundNo, $this->refund->getMerchantRefundNo());
    }

    public function testOrderGetterAndSetter(): void
    {
        $order = $this->createMock(PayByOrder::class);
        $this->refund->setOrder($order);

        $this->assertSame($order, $this->refund->getOrder());
    }

    public function testRefundAmountGetterAndSetter(): void
    {
        $amount = new PayByAmount('50.25', 'USD');
        $this->refund->setRefundAmount($amount);

        $this->assertSame($amount, $this->refund->getRefundAmount());
    }

    public function testStatusGetterAndSetter(): void
    {
        $this->refund->setStatus(PayByRefundStatus::SUCCESS);

        $this->assertSame(PayByRefundStatus::SUCCESS, $this->refund->getStatus());
    }

    public function testRefundReasonGetterAndSetter(): void
    {
        $refundReason = 'Customer request';
        $this->refund->setRefundReason($refundReason);

        $this->assertSame($refundReason, $this->refund->getRefundReason());
    }

    public function testRefundReasonWithNull(): void
    {
        $this->refund->setRefundReason(null);

        $this->assertNull($this->refund->getRefundReason());
    }

    public function testNotifyUrlGetterAndSetter(): void
    {
        $notifyUrl = 'https://example.com/refund-notify';
        $this->refund->setNotifyUrl($notifyUrl);

        $this->assertSame($notifyUrl, $this->refund->getNotifyUrl());
    }

    public function testNotifyUrlWithNull(): void
    {
        $this->refund->setNotifyUrl(null);

        $this->assertNull($this->refund->getNotifyUrl());
    }

    public function testAccessoryContentGetterAndSetter(): void
    {
        $accessoryContent = ['key' => 'value', 'metadata' => ['refund' => true]];
        $this->refund->setAccessoryContent($accessoryContent);

        $this->assertSame($accessoryContent, $this->refund->getAccessoryContent());
    }

    public function testAccessoryContentWithNull(): void
    {
        $this->refund->setAccessoryContent(null);

        $this->assertNull($this->refund->getAccessoryContent());
    }

    public function testRefundTimeGetterAndSetter(): void
    {
        $refundTime = new \DateTimeImmutable('2023-01-01 14:00:00');
        $this->refund->setRefundTime($refundTime);

        $this->assertSame($refundTime, $this->refund->getRefundTime());
    }

    public function testRefundTimeWithNull(): void
    {
        $this->refund->setRefundTime(null);

        $this->assertNull($this->refund->getRefundTime());
    }

    public function testCreateTimeGetterAndSetter(): void
    {
        $createTime = new \DateTimeImmutable('2023-01-01 10:00:00');
        $this->refund->setCreateTime($createTime);

        $this->assertSame($createTime, $this->refund->getCreateTime());
    }

    public function testUpdateTimeGetterAndSetter(): void
    {
        $updateTime = new \DateTimeImmutable('2023-01-01 12:00:00');
        $this->refund->setUpdateTime($updateTime);

        $this->assertSame($updateTime, $this->refund->getUpdateTime());
    }

    public function testIsRefunded(): void
    {
        $this->refund->setStatus(PayByRefundStatus::PENDING);
        $this->assertFalse($this->refund->isRefunded());

        $this->refund->setStatus(PayByRefundStatus::PROCESSING);
        $this->assertFalse($this->refund->isRefunded());

        $this->refund->setStatus(PayByRefundStatus::SUCCESS);
        $this->assertTrue($this->refund->isRefunded());

        $this->refund->setStatus(PayByRefundStatus::FAILED);
        $this->assertFalse($this->refund->isRefunded());
    }

    public function testIsFinal(): void
    {
        $this->refund->setStatus(PayByRefundStatus::PENDING);
        $this->assertFalse($this->refund->isFinal());

        $this->refund->setStatus(PayByRefundStatus::PROCESSING);
        $this->assertFalse($this->refund->isFinal());

        $this->refund->setStatus(PayByRefundStatus::SUCCESS);
        $this->assertTrue($this->refund->isFinal());

        $this->refund->setStatus(PayByRefundStatus::FAILED);
        $this->assertTrue($this->refund->isFinal());

        $this->refund->setStatus(PayByRefundStatus::CANCELLED);
        $this->assertTrue($this->refund->isFinal());

        $this->refund->setStatus(PayByRefundStatus::UNKNOWN);
        $this->assertTrue($this->refund->isFinal());
    }

    public function testGetAmountFormatted(): void
    {
        $amount = new PayByAmount('75.50', 'EUR');
        $this->refund->setRefundAmount($amount);

        $this->assertSame('75.50 EUR', $this->refund->getAmountFormatted());
    }

    public function testDeprecatedCreatedAtMethods(): void
    {
        $createTime = new \DateTimeImmutable('2023-01-01 10:00:00');

        $this->refund->setCreateTime($createTime);
        $this->assertSame($createTime, $this->refund->getCreateTime());
    }

    public function testDeprecatedUpdatedAtMethods(): void
    {
        $updateTime = new \DateTimeImmutable('2023-01-01 12:00:00');

        $this->refund->setUpdateTime($updateTime);
        $this->assertSame($updateTime, $this->refund->getUpdateTime());
    }

    public function testToString(): void
    {
        $refundId = 'refund-123456';
        $merchantRefundNo = 'merchant-refund-123';

        $this->refund->setRefundId($refundId);
        $this->refund->setMerchantRefundNo($merchantRefundNo);

        $expected = sprintf('%s (%s)', $refundId, $merchantRefundNo);
        $this->assertSame($expected, (string) $this->refund);
        $this->assertSame($expected, $this->refund->__toString());
    }
}
