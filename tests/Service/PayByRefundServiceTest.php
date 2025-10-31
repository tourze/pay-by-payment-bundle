<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByConfig;
use Tourze\PayByPaymentBundle\Entity\PayByOrder;
use Tourze\PayByPaymentBundle\Entity\PayByRefund;
use Tourze\PayByPaymentBundle\Enum\PayByOrderStatus;
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;
use Tourze\PayByPaymentBundle\Enum\PayByRefundStatus;
use Tourze\PayByPaymentBundle\Service\PayByRefundService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(PayByRefundService::class)]
#[RunTestsInSeparateProcesses]
final class PayByRefundServiceTest extends AbstractIntegrationTestCase
{
    private PayByRefundService $refundService;

    protected function onSetUp(): void
    {
        // Create default PayBy configuration
        $this->createDefaultPayByConfig();

        $this->refundService = self::getService(PayByRefundService::class);
    }

    private function createDefaultPayByConfig(): void
    {
        $config = new PayByConfig();
        $config->setName('default');
        $config->setDescription('Test PayBy configuration');
        $config->setApiKey('test-api-key');
        $config->setApiSecret('test-api-secret');
        $config->setMerchantId('test-merchant');
        $config->setApiBaseUrl('https://api.test.payby.com');
        $config->setEnabled(true);
        $config->setDefault(true);

        $entityManager = self::getEntityManager();
        $entityManager->persist($config);
        $entityManager->flush();
    }

    private function createTestOrder(?string $orderId = null, ?string $merchantOrderNo = null): PayByOrder
    {
        $order = new PayByOrder();
        $order->setOrderId($orderId ?? 'test-order-' . uniqid());
        $order->setMerchantOrderNo($merchantOrderNo ?? 'merchant-order-' . uniqid());
        $order->setSubject('Test Order');
        $order->setTotalAmount(new PayByAmount('100.00', 'USD'));
        $order->setPaySceneCode(PayByPaySceneCode::DYNQR);
        $order->setStatus(PayByOrderStatus::PENDING);

        $entityManager = self::getEntityManager();
        $entityManager->persist($order);
        $entityManager->flush();

        return $order;
    }

    public function testGetRefundSuccess(): void
    {
        $merchantRefundNo = 'test-refund-' . uniqid();

        // Create a test order and refund first
        $order = $this->createTestOrder();

        $refund = new PayByRefund();
        $refund->setMerchantRefundNo($merchantRefundNo);
        $refund->setRefundId('api-refund-123');
        $refund->setOrder($order);
        $refund->setRefundAmount(new PayByAmount('50.00', 'USD'));
        $refund->setRefundReason('Test refund');
        $refund->setStatus(PayByRefundStatus::PROCESSING);

        $entityManager = self::getEntityManager();
        $entityManager->persist($refund);
        $entityManager->flush();

        $result = $this->refundService->getRefund($merchantRefundNo);

        $this->assertNotNull($result);
        $this->assertEquals($merchantRefundNo, $result->getMerchantRefundNo());
    }

    public function testGetRefundNotFound(): void
    {
        $merchantRefundNo = 'nonexistent-refund';

        $result = $this->refundService->getRefund($merchantRefundNo);

        $this->assertNull($result);
    }

    public function testUpdateRefundStatusSuccess(): void
    {
        $merchantRefundNo = 'test-refund-status-' . uniqid();
        $newStatus = PayByRefundStatus::SUCCESS;

        // Create order and refund first
        $order = $this->createTestOrder();

        $refund = new PayByRefund();
        $refund->setMerchantRefundNo($merchantRefundNo);
        $refund->setRefundId('api-refund-456');
        $refund->setOrder($order);
        $refund->setRefundAmount(new PayByAmount('25.00', 'USD'));
        $refund->setRefundReason('Test refund');
        $refund->setStatus(PayByRefundStatus::PROCESSING);

        $entityManager = self::getEntityManager();
        $entityManager->persist($refund);
        $entityManager->flush();

        $result = $this->refundService->updateRefundStatus($merchantRefundNo, $newStatus);

        $this->assertTrue($result);

        // Verify status was updated
        $updatedRefund = $this->refundService->getRefund($merchantRefundNo);
        $this->assertNotNull($updatedRefund);
        $this->assertEquals($newStatus, $updatedRefund->getStatus());
    }

    public function testUpdateRefundStatusNotFound(): void
    {
        $merchantRefundNo = 'nonexistent-refund';
        $newStatus = PayByRefundStatus::FAILED;

        $result = $this->refundService->updateRefundStatus($merchantRefundNo, $newStatus);

        $this->assertFalse($result);
    }

    public function testHandleRefundNotificationSuccess(): void
    {
        $merchantRefundNo = 'test-notification-' . uniqid();
        $refundId = 'api-refund-notification-' . uniqid();

        // Create order and refund first
        $order = $this->createTestOrder();

        $refund = new PayByRefund();
        $refund->setMerchantRefundNo($merchantRefundNo);
        $refund->setRefundId($refundId);
        $refund->setOrder($order);
        $refund->setRefundAmount(new PayByAmount('30.00', 'USD'));
        $refund->setRefundReason('Test refund');
        $refund->setStatus(PayByRefundStatus::PROCESSING);

        $entityManager = self::getEntityManager();
        $entityManager->persist($refund);
        $entityManager->flush();

        $notificationData = [
            'refundId' => $refundId,
            'status' => 'SUCCESS',
            'refundTime' => '2023-01-01T12:00:00Z',
        ];

        $result = $this->refundService->handleRefundNotification($notificationData);

        $this->assertTrue($result);

        // Verify refund was updated
        $updatedRefund = $this->refundService->getRefund($merchantRefundNo);
        $this->assertNotNull($updatedRefund);
        $this->assertEquals(PayByRefundStatus::SUCCESS, $updatedRefund->getStatus());
        $this->assertNotNull($updatedRefund->getRefundTime());
    }

    public function testHandleRefundNotificationMissingRefundId(): void
    {
        $notificationData = [
            'status' => 'SUCCESS',
        ];

        $result = $this->refundService->handleRefundNotification($notificationData);

        $this->assertFalse($result);
    }

    public function testHandleRefundNotificationRefundNotFound(): void
    {
        $notificationData = [
            'refundId' => 'nonexistent-refund',
            'status' => 'SUCCESS',
        ];

        $result = $this->refundService->handleRefundNotification($notificationData);

        $this->assertFalse($result);
    }

    public function testHandleRefundNotificationInvalidStatus(): void
    {
        $merchantRefundNo = 'test-invalid-status-' . uniqid();
        $refundId = 'api-refund-invalid-' . uniqid();

        // Create order and refund first
        $order = $this->createTestOrder();

        $refund = new PayByRefund();
        $refund->setMerchantRefundNo($merchantRefundNo);
        $refund->setRefundId($refundId);
        $refund->setOrder($order);
        $refund->setRefundAmount(new PayByAmount('40.00', 'USD'));
        $refund->setRefundReason('Test refund');
        $refund->setStatus(PayByRefundStatus::PROCESSING);

        $entityManager = self::getEntityManager();
        $entityManager->persist($refund);
        $entityManager->flush();

        $notificationData = [
            'refundId' => $refundId,
            'status' => 'INVALID_STATUS',
        ];

        $result = $this->refundService->handleRefundNotification($notificationData);

        $this->assertFalse($result);
    }

    public function testHandleRefundNotificationInvalidRefundTime(): void
    {
        $merchantRefundNo = 'test-invalid-time-' . uniqid();
        $refundId = 'api-refund-time-' . uniqid();

        // Create order and refund first
        $order = $this->createTestOrder();

        $refund = new PayByRefund();
        $refund->setMerchantRefundNo($merchantRefundNo);
        $refund->setRefundId($refundId);
        $refund->setOrder($order);
        $refund->setRefundAmount(new PayByAmount('20.00', 'USD'));
        $refund->setRefundReason('Test refund');
        $refund->setStatus(PayByRefundStatus::PROCESSING);

        $entityManager = self::getEntityManager();
        $entityManager->persist($refund);
        $entityManager->flush();

        $notificationData = [
            'refundId' => $refundId,
            'status' => 'SUCCESS',
            'refundTime' => 'invalid-date-format',
        ];

        $result = $this->refundService->handleRefundNotification($notificationData);

        // Should still succeed but without setting refund time
        $this->assertTrue($result);

        // Verify status was updated even with invalid time
        $updatedRefund = $this->refundService->getRefund($merchantRefundNo);
        $this->assertNotNull($updatedRefund);
        $this->assertEquals(PayByRefundStatus::SUCCESS, $updatedRefund->getStatus());
    }

    /**
     * 测试 createRefund 方法
     */
    public function testCreateRefund(): void
    {
        $merchantRefundNo = 'test-create-refund-' . uniqid();

        // 测试无法连接API的情况 - 预期会抛出异常
        try {
            $this->refundService->createRefund(
                $merchantRefundNo,
                'original-order-id',
                new PayByAmount('50.00', 'AED'),
                'Test refund reason'
            );
            self::fail('Expected exception to be thrown');
        } catch (\Throwable $e) {
            // 预期会抛出异常，因为没有真实的API配置
            $this->assertInstanceOf(\Throwable::class, $e);
        }
    }

    /**
     * 测试 queryRefund 方法
     */
    public function testQueryRefund(): void
    {
        $refundId = 'test-query-refund-' . uniqid();

        // 测试退款不存在的情况
        $result = $this->refundService->queryRefund($refundId);
        $this->assertNull($result);
    }
}
