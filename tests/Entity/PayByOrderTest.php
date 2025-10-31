<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByOrder;
use Tourze\PayByPaymentBundle\Entity\PayByRefund;
use Tourze\PayByPaymentBundle\Enum\PayByOrderStatus;
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;
use Tourze\PayByPaymentBundle\Enum\PayByRefundStatus;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(PayByOrder::class)]
class PayByOrderTest extends AbstractEntityTestCase
{
    private PayByOrder $order;

    protected function setUp(): void
    {
        $this->order = $this->createEntity();
    }

    protected function createEntity(): PayByOrder
    {
        return new PayByOrder();
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $properties = [
            'orderId' => 'test_order_123',
            'merchantOrderNo' => 'merchant_123',
            'subject' => 'Test Order Subject',
            'paymentMethod' => 'CREDIT_CARD',
            'qrCodeData' => 'test_qr_data',
            'qrCodeUrl' => 'https://example.com/qr',
            'paymentUrl' => 'https://example.com/pay',
            'notifyUrl' => 'https://example.com/notify',
            'returnUrl' => 'https://example.com/return',
        ];

        foreach ($properties as $property => $sampleValue) {
            yield $property => [$property, $sampleValue];
        }
    }

    public function testConstructor(): void
    {
        $order = new PayByOrder();

        $this->assertSame(PayByOrderStatus::PENDING, $order->getStatus());
        $this->assertInstanceOf(ArrayCollection::class, $order->getRefunds());
        $this->assertCount(0, $order->getRefunds());
        // TimestampableAware trait时间戳字段在实体持久化时由Doctrine监听器自动设置
        $this->assertNull($order->getCreateTime());
        $this->assertNull($order->getUpdateTime());
    }

    public function testOrderIdGetterAndSetter(): void
    {
        $orderId = 'order-123456';
        $this->order->setOrderId($orderId);

        $this->assertSame($orderId, $this->order->getOrderId());
    }

    public function testMerchantOrderNoGetterAndSetter(): void
    {
        $merchantOrderNo = 'merchant-order-123';
        $this->order->setMerchantOrderNo($merchantOrderNo);

        $this->assertSame($merchantOrderNo, $this->order->getMerchantOrderNo());
    }

    public function testSubjectGetterAndSetter(): void
    {
        $subject = 'Test payment order';
        $this->order->setSubject($subject);

        $this->assertSame($subject, $this->order->getSubject());
    }

    public function testTotalAmountGetterAndSetter(): void
    {
        $amount = new PayByAmount('100.50', 'USD');
        $this->order->setTotalAmount($amount);

        $this->assertSame($amount, $this->order->getTotalAmount());
    }

    public function testPaySceneCodeGetterAndSetter(): void
    {
        $paySceneCode = PayByPaySceneCode::DYNQR;
        $this->order->setPaySceneCode($paySceneCode);

        $this->assertSame($paySceneCode, $this->order->getPaySceneCode());
    }

    public function testStatusGetterAndSetter(): void
    {
        $this->order->setStatus(PayByOrderStatus::SUCCESS);

        $this->assertSame(PayByOrderStatus::SUCCESS, $this->order->getStatus());
        // TimestampableAware trait下updateTime由Doctrine监听器自动管理，不在setter中处理
    }

    public function testPaymentMethodGetterAndSetter(): void
    {
        $paymentMethod = 'credit_card';
        $this->order->setPaymentMethod($paymentMethod);

        $this->assertSame($paymentMethod, $this->order->getPaymentMethod());
    }

    public function testPaymentMethodWithNull(): void
    {
        $this->order->setPaymentMethod(null);

        $this->assertNull($this->order->getPaymentMethod());
    }

    public function testQrCodeDataGetterAndSetter(): void
    {
        $qrCodeData = 'qr-code-data-string';
        $this->order->setQrCodeData($qrCodeData);

        $this->assertSame($qrCodeData, $this->order->getQrCodeData());
    }

    public function testQrCodeUrlGetterAndSetter(): void
    {
        $qrCodeUrl = 'https://example.com/qr.png';
        $this->order->setQrCodeUrl($qrCodeUrl);

        $this->assertSame($qrCodeUrl, $this->order->getQrCodeUrl());
    }

    public function testPaymentUrlGetterAndSetter(): void
    {
        $paymentUrl = 'https://payment.example.com/pay';
        $this->order->setPaymentUrl($paymentUrl);

        $this->assertSame($paymentUrl, $this->order->getPaymentUrl());
    }

    public function testNotifyUrlGetterAndSetter(): void
    {
        $notifyUrl = 'https://example.com/notify';
        $this->order->setNotifyUrl($notifyUrl);

        $this->assertSame($notifyUrl, $this->order->getNotifyUrl());
    }

    public function testReturnUrlGetterAndSetter(): void
    {
        $returnUrl = 'https://example.com/return';
        $this->order->setReturnUrl($returnUrl);

        $this->assertSame($returnUrl, $this->order->getReturnUrl());
    }

    public function testAccessoryContentGetterAndSetter(): void
    {
        $accessoryContent = ['key' => 'value', 'metadata' => ['test' => true]];
        $this->order->setAccessoryContent($accessoryContent);

        $this->assertSame($accessoryContent, $this->order->getAccessoryContent());
    }

    public function testPayTimeGetterAndSetter(): void
    {
        $payTime = new \DateTimeImmutable('2023-01-01 10:00:00');
        $this->order->setPayTime($payTime);

        $this->assertSame($payTime, $this->order->getPayTime());
    }

    public function testCreateTimeGetterAndSetter(): void
    {
        $createTime = new \DateTimeImmutable('2023-01-01 09:00:00');
        $this->order->setCreateTime($createTime);

        $this->assertSame($createTime, $this->order->getCreateTime());
    }

    public function testUpdateTimeGetterAndSetter(): void
    {
        $updateTime = new \DateTimeImmutable('2023-01-01 11:00:00');
        $this->order->setUpdateTime($updateTime);

        $this->assertSame($updateTime, $this->order->getUpdateTime());
    }

    public function testAddRefund(): void
    {
        $refund = $this->createMock(PayByRefund::class);
        $refund->expects($this->once())
            ->method('setOrder')
            ->with($this->order)
        ;

        $this->order->addRefund($refund);

        $this->assertCount(1, $this->order->getRefunds());
        $this->assertTrue($this->order->getRefunds()->contains($refund));
    }

    public function testAddRefundAlreadyExists(): void
    {
        $refund = $this->createMock(PayByRefund::class);
        $refund->expects($this->once())
            ->method('setOrder')
            ->with($this->order)
        ;

        $this->order->addRefund($refund);
        $this->order->addRefund($refund); // 添加相同的退款

        $this->assertCount(1, $this->order->getRefunds());
    }

    public function testRemoveRefund(): void
    {
        $refund = $this->createMock(PayByRefund::class);
        $refund->expects($this->once())
            ->method('setOrder')
            ->with($this->order)
        ;

        $this->order->addRefund($refund);
        $this->assertCount(1, $this->order->getRefunds());

        $this->order->removeRefund($refund);
        $this->assertCount(0, $this->order->getRefunds());
    }

    public function testIsPaid(): void
    {
        $this->order->setStatus(PayByOrderStatus::PENDING);
        $this->assertFalse($this->order->isPaid());

        $this->order->setStatus(PayByOrderStatus::SUCCESS);
        $this->assertTrue($this->order->isPaid());

        $this->order->setStatus(PayByOrderStatus::FAILED);
        $this->assertFalse($this->order->isPaid());
    }

    public function testCanBeCancelled(): void
    {
        $this->order->setStatus(PayByOrderStatus::PENDING);
        $this->assertTrue($this->order->canBeCancelled());

        $this->order->setStatus(PayByOrderStatus::PROCESSING);
        $this->assertTrue($this->order->canBeCancelled());

        $this->order->setStatus(PayByOrderStatus::SUCCESS);
        $this->assertFalse($this->order->canBeCancelled());

        $this->order->setStatus(PayByOrderStatus::FAILED);
        $this->assertFalse($this->order->canBeCancelled());
    }

    public function testIsFinal(): void
    {
        $this->order->setStatus(PayByOrderStatus::PENDING);
        $this->assertFalse($this->order->isFinal());

        $this->order->setStatus(PayByOrderStatus::PROCESSING);
        $this->assertFalse($this->order->isFinal());

        $this->order->setStatus(PayByOrderStatus::SUCCESS);
        $this->assertTrue($this->order->isFinal());

        $this->order->setStatus(PayByOrderStatus::FAILED);
        $this->assertTrue($this->order->isFinal());

        $this->order->setStatus(PayByOrderStatus::CANCELLED);
        $this->assertTrue($this->order->isFinal());
    }

    public function testGetRefundableAmountWhenNotPaid(): void
    {
        $this->order->setStatus(PayByOrderStatus::PENDING);
        $this->order->setTotalAmount(new PayByAmount('100.00', 'USD'));

        $this->assertSame('0.00', $this->order->getRefundableAmount());
    }

    public function testGetRefundableAmountWhenPaidWithoutRefunds(): void
    {
        $this->order->setStatus(PayByOrderStatus::SUCCESS);
        $this->order->setTotalAmount(new PayByAmount('100.00', 'USD'));

        $this->assertSame('100.00', $this->order->getRefundableAmount());
    }

    public function testGetRefundableAmountWithSuccessfulRefunds(): void
    {
        $this->order->setStatus(PayByOrderStatus::SUCCESS);
        $this->order->setTotalAmount(new PayByAmount('100.00', 'USD'));

        // 创建成功的退款
        $refund1 = $this->createMock(PayByRefund::class);
        $refund1->method('getStatus')->willReturn(PayByRefundStatus::SUCCESS);
        $refund1->method('getRefundAmount')->willReturn(new PayByAmount('30.00', 'USD'));

        $refund2 = $this->createMock(PayByRefund::class);
        $refund2->method('getStatus')->willReturn(PayByRefundStatus::SUCCESS);
        $refund2->method('getRefundAmount')->willReturn(new PayByAmount('20.00', 'USD'));

        // 创建失败的退款（不应该计算在内）
        $refund3 = $this->createMock(PayByRefund::class);
        $refund3->method('getStatus')->willReturn(PayByRefundStatus::FAILED);
        $refund3->method('getRefundAmount')->willReturn(new PayByAmount('10.00', 'USD'));

        $this->order->addRefund($refund1);
        $this->order->addRefund($refund2);
        $this->order->addRefund($refund3);

        $this->assertSame('50.00', $this->order->getRefundableAmount());
    }

    public function testGetAmountFormatted(): void
    {
        $amount = new PayByAmount('100.50', 'USD');
        $this->order->setTotalAmount($amount);

        $this->assertSame('100.50 USD', $this->order->getAmountFormatted());
    }

    public function testDeprecatedCreatedAtMethods(): void
    {
        $createTime = new \DateTimeImmutable('2023-01-01 10:00:00');

        $this->order->setCreateTime($createTime);
        $this->assertSame($createTime, $this->order->getCreateTime());
    }

    public function testDeprecatedUpdatedAtMethods(): void
    {
        $updateTime = new \DateTimeImmutable('2023-01-01 12:00:00');

        $this->order->setUpdateTime($updateTime);
        $this->assertSame($updateTime, $this->order->getUpdateTime());
    }

    public function testToString(): void
    {
        $orderId = 'order-123456';
        $subject = 'Test Payment';

        $this->order->setOrderId($orderId);
        $this->order->setSubject($subject);

        $expected = sprintf('%s (%s)', $orderId, $subject);
        $this->assertSame($expected, (string) $this->order);
        $this->assertSame($expected, $this->order->__toString());
    }
}
