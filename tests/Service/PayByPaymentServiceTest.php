<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByOrder;
use Tourze\PayByPaymentBundle\Entity\PayByRefund;
use Tourze\PayByPaymentBundle\Entity\PayByTransfer;
use Tourze\PayByPaymentBundle\Enum\PayByOrderStatus;
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;
use Tourze\PayByPaymentBundle\Enum\PayByRefundStatus;
use Tourze\PayByPaymentBundle\Enum\PayByTransferStatus;
use Tourze\PayByPaymentBundle\Enum\PayByTransferType;
use Tourze\PayByPaymentBundle\Service\PayByPaymentService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(PayByPaymentService::class)]
#[RunTestsInSeparateProcesses]
final class PayByPaymentServiceTest extends AbstractIntegrationTestCase
{
    private PayByPaymentService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(PayByPaymentService::class);
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

    public function testFindOrderByMerchantNoFound(): void
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

        $result = $this->service->findOrderByMerchantNo($merchantOrderNo);

        $this->assertNotNull($result);
        $this->assertEquals($merchantOrderNo, $result->getMerchantOrderNo());
    }

    public function testFindOrderByMerchantNoNotFound(): void
    {
        $merchantOrderNo = 'nonexistent-order';

        $result = $this->service->findOrderByMerchantNo($merchantOrderNo);

        $this->assertNull($result);
    }

    public function testFindTransferByMerchantNoFound(): void
    {
        $merchantTransferNo = 'test-transfer-' . uniqid();

        // Create a test transfer first
        $transfer = new PayByTransfer();
        $transfer->setMerchantTransferNo($merchantTransferNo);
        $transfer->setTransferId('api-transfer-123');
        $transfer->setTransferType(PayByTransferType::INTERNAL);
        $transfer->setFromAccount('acc1');
        $transfer->setToAccount('acc2');
        $transfer->setTransferAmount(new PayByAmount('100', 'CNY'));
        $transfer->setTransferReason('Test transfer');
        $transfer->setStatus(PayByTransferStatus::PENDING);

        $entityManager = self::getEntityManager();
        $entityManager->persist($transfer);
        $entityManager->flush();

        $result = $this->service->findTransferByMerchantNo($merchantTransferNo);

        $this->assertNotNull($result);
        $this->assertEquals($merchantTransferNo, $result->getMerchantTransferNo());
    }

    public function testFindTransferByMerchantNoNotFound(): void
    {
        $merchantTransferNo = 'nonexistent-transfer';

        $result = $this->service->findTransferByMerchantNo($merchantTransferNo);

        $this->assertNull($result);
    }

    public function testFindRefundByMerchantNoFound(): void
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

        $result = $this->service->findRefundByMerchantNo($merchantRefundNo);

        $this->assertNotNull($result);
        $this->assertEquals($merchantRefundNo, $result->getMerchantRefundNo());
    }

    public function testFindRefundByMerchantNoNotFound(): void
    {
        $merchantRefundNo = 'nonexistent-refund';

        $result = $this->service->findRefundByMerchantNo($merchantRefundNo);

        $this->assertNull($result);
    }

    public function testGetOrderStats(): void
    {
        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-01-31');

        // Create test orders within the date range
        $order1 = new PayByOrder();
        $order1->setMerchantOrderNo('stats-order-1-' . uniqid());
        $order1->setOrderId('api-order-stats-1');
        $order1->setSubject('Stats Order 1');
        $order1->setTotalAmount(new PayByAmount('100', 'CNY'));
        $order1->setPaySceneCode(PayByPaySceneCode::ONLINE);
        $order1->setStatus(PayByOrderStatus::PENDING);
        $order1->setCreateTime(new \DateTimeImmutable('2024-01-15'));

        $order2 = new PayByOrder();
        $order2->setMerchantOrderNo('stats-order-2-' . uniqid());
        $order2->setOrderId('api-order-stats-2');
        $order2->setSubject('Stats Order 2');
        $order2->setTotalAmount(new PayByAmount('200', 'CNY'));
        $order2->setPaySceneCode(PayByPaySceneCode::ONLINE);
        $order2->setStatus(PayByOrderStatus::SUCCESS);
        $order2->setCreateTime(new \DateTimeImmutable('2024-01-20'));

        $entityManager = self::getEntityManager();
        $entityManager->persist($order1);
        $entityManager->persist($order2);
        $entityManager->flush();

        $result = $this->service->getOrderStats($startDate, $endDate);

        $this->assertArrayHasKey('total_orders', $result);
        $this->assertArrayHasKey('total_amount', $result);
        $this->assertGreaterThanOrEqual(2, $result['total_orders']);
    }

    public function testGetTransferStats(): void
    {
        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-01-31');

        // Create test transfers within the date range
        $transfer1 = new PayByTransfer();
        $transfer1->setMerchantTransferNo('stats-transfer-1-' . uniqid());
        $transfer1->setTransferId('api-transfer-stats-1');
        $transfer1->setTransferType(PayByTransferType::INTERNAL);
        $transfer1->setFromAccount('acc1');
        $transfer1->setToAccount('acc2');
        $transfer1->setTransferAmount(new PayByAmount('100', 'CNY'));
        $transfer1->setTransferReason('Stats transfer 1');
        $transfer1->setStatus(PayByTransferStatus::SUCCESS);
        $transfer1->setCreateTime(new \DateTimeImmutable('2024-01-15'));

        $entityManager = self::getEntityManager();
        $entityManager->persist($transfer1);
        $entityManager->flush();

        $result = $this->service->getTransferStats($startDate, $endDate);

        $this->assertArrayHasKey('total_transfers', $result);
        $this->assertArrayHasKey('total_amount', $result);
        $this->assertGreaterThanOrEqual(1, $result['total_transfers']);
    }

    public function testGetRefundStats(): void
    {
        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-01-31');

        // Create test order and refunds within the date range
        $order = $this->createTestOrder();

        $refund1 = new PayByRefund();
        $refund1->setMerchantRefundNo('stats-refund-1-' . uniqid());
        $refund1->setRefundId('api-refund-stats-1');
        $refund1->setOrder($order);
        $refund1->setRefundAmount(new PayByAmount('50.00', 'USD'));
        $refund1->setRefundReason('Stats refund 1');
        $refund1->setStatus(PayByRefundStatus::SUCCESS);
        $refund1->setCreateTime(new \DateTimeImmutable('2024-01-15'));

        $entityManager = self::getEntityManager();
        $entityManager->persist($refund1);
        $entityManager->flush();

        $result = $this->service->getRefundStats($startDate, $endDate);

        $this->assertArrayHasKey('total_refunds', $result);
        $this->assertArrayHasKey('total_amount', $result);
        $this->assertGreaterThanOrEqual(1, $result['total_refunds']);
    }
}
