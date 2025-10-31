<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByConfig;
use Tourze\PayByPaymentBundle\Entity\PayByOrder;
use Tourze\PayByPaymentBundle\Enum\PayByOrderStatus;
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;
use Tourze\PayByPaymentBundle\Exception\PayByException;
use Tourze\PayByPaymentBundle\Service\PayByOrderService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(PayByOrderService::class)]
#[RunTestsInSeparateProcesses]
final class PayByOrderServiceTest extends AbstractIntegrationTestCase
{
    private PayByOrderService $service;

    protected function onSetUp(): void
    {
        // Create default PayBy configuration
        $this->createDefaultPayByConfig();

        $this->service = self::getService(PayByOrderService::class);
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

    public function testGetOrderFound(): void
    {
        $merchantOrderNo = 'test-order-' . uniqid();

        // Create a test order first
        $order = new PayByOrder();
        $order->setMerchantOrderNo($merchantOrderNo);
        $order->setOrderId('api-order-123');
        $order->setSubject('Test Order');
        $order->setTotalAmount(new PayByAmount('100', 'CNY'));
        $order->setPaySceneCode(PayByPaySceneCode::ONLINE);
        $order->setStatus(PayByOrderStatus::PENDING);

        $entityManager = self::getEntityManager();
        $entityManager->persist($order);
        $entityManager->flush();

        $result = $this->service->getOrder($merchantOrderNo);

        $this->assertNotNull($result);
        $this->assertEquals($merchantOrderNo, $result->getMerchantOrderNo());
    }

    public function testGetOrderNotFound(): void
    {
        $merchantOrderNo = 'nonexistent-order';

        $result = $this->service->getOrder($merchantOrderNo);

        $this->assertNull($result);
    }

    public function testUpdateOrderStatusSuccess(): void
    {
        $merchantOrderNo = 'test-order-status-' . uniqid();
        $status = PayByOrderStatus::SUCCESS;

        // Create order first
        $order = new PayByOrder();
        $order->setMerchantOrderNo($merchantOrderNo);
        $order->setOrderId('api-order-456');
        $order->setSubject('Test Order');
        $order->setTotalAmount(new PayByAmount('100', 'CNY'));
        $order->setPaySceneCode(PayByPaySceneCode::ONLINE);
        $order->setStatus(PayByOrderStatus::PENDING);

        $entityManager = self::getEntityManager();
        $entityManager->persist($order);
        $entityManager->flush();

        $result = $this->service->updateOrderStatus($merchantOrderNo, $status);

        $this->assertTrue($result);

        // Verify status was updated
        $updatedOrder = $this->service->getOrder($merchantOrderNo);
        $this->assertNotNull($updatedOrder);
        $this->assertEquals($status, $updatedOrder->getStatus());
    }

    public function testUpdateOrderStatusOrderNotFound(): void
    {
        $merchantOrderNo = 'nonexistent-order';
        $status = PayByOrderStatus::SUCCESS;

        $result = $this->service->updateOrderStatus($merchantOrderNo, $status);

        $this->assertFalse($result);
    }

    public function testHandlePaymentNotificationSuccess(): void
    {
        $merchantOrderNo = 'test-notification-' . uniqid();
        $orderId = 'api-order-notification-' . uniqid();

        // Create order first
        $order = new PayByOrder();
        $order->setMerchantOrderNo($merchantOrderNo);
        $order->setOrderId($orderId);
        $order->setSubject('Test Order');
        $order->setTotalAmount(new PayByAmount('100', 'CNY'));
        $order->setPaySceneCode(PayByPaySceneCode::ONLINE);
        $order->setStatus(PayByOrderStatus::PENDING);

        $entityManager = self::getEntityManager();
        $entityManager->persist($order);
        $entityManager->flush();

        $notificationData = [
            'orderId' => $orderId,
            'status' => PayByOrderStatus::SUCCESS->value,
            'paymentMethod' => 'alipay',
            'payTime' => '2024-01-01T12:00:00Z',
        ];

        $result = $this->service->handlePaymentNotification($notificationData);

        $this->assertTrue($result);

        // Verify order was updated
        $updatedOrder = $this->service->getOrder($merchantOrderNo);
        $this->assertNotNull($updatedOrder);
        $this->assertEquals(PayByOrderStatus::SUCCESS, $updatedOrder->getStatus());
        $this->assertEquals('alipay', $updatedOrder->getPaymentMethod());
        $this->assertNotNull($updatedOrder->getPayTime());
    }

    public function testHandlePaymentNotificationMissingOrderId(): void
    {
        $notificationData = [
            'status' => PayByOrderStatus::SUCCESS->value,
        ];

        $result = $this->service->handlePaymentNotification($notificationData);

        $this->assertFalse($result);
    }

    public function testHandlePaymentNotificationOrderNotFound(): void
    {
        $orderId = 'nonexistent-order';
        $notificationData = [
            'orderId' => $orderId,
            'status' => PayByOrderStatus::SUCCESS->value,
        ];

        $result = $this->service->handlePaymentNotification($notificationData);

        $this->assertFalse($result);
    }

    public function testHandlePaymentNotificationInvalidStatus(): void
    {
        $merchantOrderNo = 'test-invalid-status-' . uniqid();
        $orderId = 'api-order-invalid-' . uniqid();

        // Create order first
        $order = new PayByOrder();
        $order->setMerchantOrderNo($merchantOrderNo);
        $order->setOrderId($orderId);
        $order->setSubject('Test Order');
        $order->setTotalAmount(new PayByAmount('100', 'CNY'));
        $order->setPaySceneCode(PayByPaySceneCode::ONLINE);
        $order->setStatus(PayByOrderStatus::PENDING);

        $entityManager = self::getEntityManager();
        $entityManager->persist($order);
        $entityManager->flush();

        $notificationData = [
            'orderId' => $orderId,
            'status' => 'INVALID_STATUS',
        ];

        $result = $this->service->handlePaymentNotification($notificationData);

        $this->assertFalse($result);
    }

    public function testHandlePaymentNotificationInvalidPayTime(): void
    {
        $merchantOrderNo = 'test-invalid-time-' . uniqid();
        $orderId = 'api-order-time-' . uniqid();

        // Create order first
        $order = new PayByOrder();
        $order->setMerchantOrderNo($merchantOrderNo);
        $order->setOrderId($orderId);
        $order->setSubject('Test Order');
        $order->setTotalAmount(new PayByAmount('100', 'CNY'));
        $order->setPaySceneCode(PayByPaySceneCode::ONLINE);
        $order->setStatus(PayByOrderStatus::PENDING);

        $entityManager = self::getEntityManager();
        $entityManager->persist($order);
        $entityManager->flush();

        $notificationData = [
            'orderId' => $orderId,
            'status' => PayByOrderStatus::SUCCESS->value,
            'payTime' => 'invalid-date',
        ];

        $result = $this->service->handlePaymentNotification($notificationData);

        $this->assertFalse($result);
    }

    /**
     * 测试 createOrder 方法 - 预期抛出异常因为没有真实API配置
     */
    public function testCreateOrder(): void
    {
        $merchantOrderNo = 'test-create-order-' . uniqid();
        $subject = 'Test Order Subject';
        $amount = new PayByAmount('100.50', 'AED');
        $paySceneCode = PayByPaySceneCode::ONLINE;
        $notifyUrl = 'https://example.com/notify';
        $returnUrl = 'https://example.com/return';
        $accessoryContent = ['key' => 'value'];

        // 期望抛出异常，因为没有真实的API配置
        $this->expectException(\Throwable::class);

        $this->service->createOrder(
            $merchantOrderNo,
            $subject,
            $amount,
            $paySceneCode,
            $notifyUrl,
            $returnUrl,
            $accessoryContent
        );
    }

    /**
     * 测试 queryOrder 方法
     */
    public function testQueryOrder(): void
    {
        $orderId = 'test-query-order-' . uniqid();

        // 测试订单不存在的情况
        $result = $this->service->queryOrder($orderId);
        $this->assertNull($result);
    }

    /**
     * 测试 cancelOrder 方法
     */
    public function testCancelOrder(): void
    {
        $orderId = 'test-cancel-order-' . uniqid();

        // 测试订单不存在的情况
        $this->expectException(PayByException::class);
        $this->expectExceptionMessage('Order not found');

        $this->service->cancelOrder($orderId);
    }
}
