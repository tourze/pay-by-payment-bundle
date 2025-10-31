<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByOrder;
use Tourze\PayByPaymentBundle\Entity\PayByRefund;
use Tourze\PayByPaymentBundle\Enum\PayByOrderStatus;
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;
use Tourze\PayByPaymentBundle\Enum\PayByRefundStatus;
use Tourze\PayByPaymentBundle\Repository\PayByRefundRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(PayByRefundRepository::class)]
#[RunTestsInSeparateProcesses]
class PayByRefundRepositoryTest extends AbstractRepositoryTestCase
{
    private PayByRefundRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(PayByRefundRepository::class);

        // 清理可能存在的fixture数据，确保测试隔离
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . PayByRefund::class)->execute();
        // 同时清理可能关联的Order数据
        $em->createQuery('DELETE FROM ' . PayByOrder::class)->execute();

        // 创建一些基础数据来满足DataFixture测试要求
        $refund = $this->createNewEntity();
        $refund->setRefundId('fixture-refund-id');
        $refund->setMerchantRefundNo('fixture-merchant-refund-no');
        $refund->setStatus(PayByRefundStatus::CANCELLED); // 设为取消状态，不影响其他状态测试
        $refund->setRefundAmount(new PayByAmount('1000', 'EUR')); // 使用EUR币种，不影响AED/USD统计测试
        $this->repository->save($refund, true);
    }

    protected function getRepository(): PayByRefundRepository
    {
        return $this->repository;
    }

    private function createOrder(): PayByOrder
    {
        $totalAmount = new PayByAmount('10000', 'AED');

        $order = new PayByOrder();
        $order->setOrderId('order-' . uniqid());
        $order->setMerchantOrderNo('merchant-' . uniqid());
        $order->setSubject('Test Order');
        $order->setBody('Test order description');
        $order->setTotalAmount($totalAmount);
        $order->setNotifyUrl('https://example.com/notify');
        $order->setPaySceneCode(PayByPaySceneCode::PAYPAGE);
        $order->setStatus(PayByOrderStatus::SUCCESS);

        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        return $order;
    }

    protected function createNewEntity(): PayByRefund
    {
        $order = $this->createOrder();

        $refundAmount = new PayByAmount('5000', 'AED');

        $refund = new PayByRefund();
        $refund->setRefundId('refund-' . uniqid());
        $refund->setMerchantRefundNo('merchant-refund-' . uniqid());
        $refund->setOrder($order);
        $refund->setRefundAmount($refundAmount);
        $refund->setReason('Customer request');
        $refund->setNotifyUrl('https://example.com/refund-notify');
        $refund->setStatus(PayByRefundStatus::PENDING);

        return $refund;
    }

    public function testSaveAndFind(): void
    {
        $refund = $this->createNewEntity();
        $this->repository->save($refund, true);

        $foundRefund = $this->repository->find($refund->getId());

        $this->assertNotNull($foundRefund);
        $this->assertSame($refund->getRefundId(), $foundRefund->getRefundId());
        $this->assertSame($refund->getMerchantRefundNo(), $foundRefund->getMerchantRefundNo());
        $this->assertSame($refund->getReason(), $foundRefund->getReason());
    }

    public function testRemove(): void
    {
        $refund = $this->createNewEntity();
        $this->repository->save($refund, true);
        $id = $refund->getId();

        $this->repository->remove($refund, true);

        $foundRefund = $this->repository->find($id);
        $this->assertNull($foundRefund);
    }

    public function testFindByRefundId(): void
    {
        $refund = $this->createNewEntity();
        $refundId = 'unique-refund-id-12345';
        $refund->setRefundId($refundId);
        $this->repository->save($refund, true);

        $foundRefund = $this->repository->findByRefundId($refundId);

        $this->assertNotNull($foundRefund);
        $this->assertSame($refundId, $foundRefund->getRefundId());
        $this->assertSame($refund->getId(), $foundRefund->getId());
    }

    public function testFindByRefundIdWhenNotExists(): void
    {
        $foundRefund = $this->repository->findByRefundId('non-existent-refund-id');

        $this->assertNull($foundRefund);
    }

    public function testFindByMerchantRefundNo(): void
    {
        $refund = $this->createNewEntity();
        $merchantRefundNo = 'unique-merchant-refund-no-12345';
        $refund->setMerchantRefundNo($merchantRefundNo);
        $this->repository->save($refund, true);

        $foundRefund = $this->repository->findByMerchantRefundNo($merchantRefundNo);

        $this->assertNotNull($foundRefund);
        $this->assertSame($merchantRefundNo, $foundRefund->getMerchantRefundNo());
        $this->assertSame($refund->getId(), $foundRefund->getId());
    }

    public function testFindByMerchantRefundNoWhenNotExists(): void
    {
        $foundRefund = $this->repository->findByMerchantRefundNo('non-existent-merchant-refund-no');

        $this->assertNull($foundRefund);
    }

    public function testFindByOrderId(): void
    {
        $order1 = $this->createOrder();
        $order2 = $this->createOrder();

        // 为第一个订单创建退款
        $refund1 = $this->createNewEntity();
        $refund1->setOrder($order1);
        $this->repository->save($refund1, true);

        $refund2 = $this->createNewEntity();
        $refund2->setOrder($order1);
        $refund2->setCreateTime(new \DateTimeImmutable('2023-01-02 10:00:00'));
        $this->repository->save($refund2, true);

        // 为第二个订单创建退款
        $refund3 = $this->createNewEntity();
        $refund3->setOrder($order2);
        $this->repository->save($refund3, true);

        $refundsForOrder1 = $this->repository->findByOrderId($order1->getOrderId());
        $refundsForOrder2 = $this->repository->findByOrderId($order2->getOrderId());

        $this->assertCount(2, $refundsForOrder1);
        $this->assertCount(1, $refundsForOrder2);

        // 验证排序（按createTime DESC）
        $this->assertTrue($refundsForOrder1[0]->getCreateTime() >= $refundsForOrder1[1]->getCreateTime());

        // 验证都属于正确的订单
        foreach ($refundsForOrder1 as $refund) {
            $this->assertSame($order1->getId(), $refund->getOrder()->getId());
        }
    }

    public function testFindByOrderIdWhenNotExists(): void
    {
        $refunds = $this->repository->findByOrderId('non-existent-order-id');

        $this->assertEmpty($refunds);
    }

    public function testFindByStatus(): void
    {
        // 创建不同状态的退款
        $pendingRefund1 = $this->createNewEntity();
        $pendingRefund1->setStatus(PayByRefundStatus::PENDING);
        $this->repository->save($pendingRefund1, true);

        $pendingRefund2 = $this->createNewEntity();
        $pendingRefund2->setStatus(PayByRefundStatus::PENDING);
        $this->repository->save($pendingRefund2, true);

        $successRefund = $this->createNewEntity();
        $successRefund->setStatus(PayByRefundStatus::SUCCESS);
        $this->repository->save($successRefund, true);

        $pendingRefunds = $this->repository->findByStatus(PayByRefundStatus::PENDING);
        $successRefunds = $this->repository->findByStatus(PayByRefundStatus::SUCCESS);

        $this->assertCount(2, $pendingRefunds);
        $this->assertCount(1, $successRefunds);

        foreach ($pendingRefunds as $refund) {
            $this->assertSame(PayByRefundStatus::PENDING, $refund->getStatus());
        }

        $this->assertSame(PayByRefundStatus::SUCCESS, $successRefunds[0]->getStatus());
    }

    public function testFindPendingRefunds(): void
    {
        // 创建待处理退款
        $pendingRefund1 = $this->createNewEntity();
        $pendingRefund1->setStatus(PayByRefundStatus::PENDING);
        $this->repository->save($pendingRefund1, true);

        $pendingRefund2 = $this->createNewEntity();
        $pendingRefund2->setStatus(PayByRefundStatus::PENDING);
        $this->repository->save($pendingRefund2, true);

        // 创建其他状态退款
        $successRefund = $this->createNewEntity();
        $successRefund->setStatus(PayByRefundStatus::SUCCESS);
        $this->repository->save($successRefund, true);

        $pendingRefunds = $this->repository->findPendingRefunds();

        $this->assertCount(2, $pendingRefunds);
        foreach ($pendingRefunds as $refund) {
            $this->assertSame(PayByRefundStatus::PENDING, $refund->getStatus());
        }
    }

    public function testFindSuccessfulRefunds(): void
    {
        // 创建成功退款
        $successRefund1 = $this->createNewEntity();
        $successRefund1->setStatus(PayByRefundStatus::SUCCESS);
        $this->repository->save($successRefund1, true);

        $successRefund2 = $this->createNewEntity();
        $successRefund2->setStatus(PayByRefundStatus::SUCCESS);
        $this->repository->save($successRefund2, true);

        // 创建其他状态退款
        $pendingRefund = $this->createNewEntity();
        $pendingRefund->setStatus(PayByRefundStatus::PENDING);
        $this->repository->save($pendingRefund, true);

        $successfulRefunds = $this->repository->findSuccessfulRefunds();

        $this->assertCount(2, $successfulRefunds);
        foreach ($successfulRefunds as $refund) {
            $this->assertSame(PayByRefundStatus::SUCCESS, $refund->getStatus());
        }
    }

    public function testFindRefundsByDateRange(): void
    {
        $startDate = new \DateTimeImmutable('2023-01-01 00:00:00');
        $endDate = new \DateTimeImmutable('2023-01-31 23:59:59');
        $outsideDate = new \DateTimeImmutable('2023-02-01 00:00:00');

        // 创建在范围内的退款
        $refundInRange1 = $this->createNewEntity();
        $refundInRange1->setCreateTime(new \DateTimeImmutable('2023-01-15 10:00:00'));
        $this->repository->save($refundInRange1, true);

        $refundInRange2 = $this->createNewEntity();
        $refundInRange2->setCreateTime(new \DateTimeImmutable('2023-01-20 15:30:00'));
        $this->repository->save($refundInRange2, true);

        // 创建在范围外的退款
        $refundOutOfRange = $this->createNewEntity();
        $refundOutOfRange->setCreateTime($outsideDate);
        $this->repository->save($refundOutOfRange, true);

        $refundsInRange = $this->repository->findRefundsByDateRange($startDate, $endDate);

        $this->assertCount(2, $refundsInRange);

        // 验证排序（按createTime DESC）
        $this->assertTrue($refundsInRange[0]->getCreateTime() >= $refundsInRange[1]->getCreateTime());

        foreach ($refundsInRange as $refund) {
            $this->assertTrue($refund->getCreateTime() >= $startDate);
            $this->assertTrue($refund->getCreateTime() <= $endDate);
        }
    }

    public function testGetRefundStatistics(): void
    {
        // 创建不同状态和币种的退款
        $aedAmount1 = new PayByAmount('5000', 'AED');
        $successRefund1 = $this->createNewEntity();
        $successRefund1->setRefundAmount($aedAmount1);
        $successRefund1->setStatus(PayByRefundStatus::SUCCESS);
        $this->repository->save($successRefund1, true);

        $aedAmount2 = new PayByAmount('3000', 'AED');
        $successRefund2 = $this->createNewEntity();
        $successRefund2->setRefundAmount($aedAmount2);
        $successRefund2->setStatus(PayByRefundStatus::SUCCESS);
        $this->repository->save($successRefund2, true);

        $aedAmount3 = new PayByAmount('2000', 'AED');
        $pendingRefund = $this->createNewEntity();
        $pendingRefund->setRefundAmount($aedAmount3);
        $pendingRefund->setStatus(PayByRefundStatus::PENDING);
        $this->repository->save($pendingRefund, true);

        $usdAmount = new PayByAmount('1000', 'USD');
        $successRefundUsd = $this->createNewEntity();
        $successRefundUsd->setRefundAmount($usdAmount);
        $successRefundUsd->setStatus(PayByRefundStatus::SUCCESS);
        $this->repository->save($successRefundUsd, true);

        $statistics = $this->repository->getRefundStatistics();

        $this->assertCount(3, $statistics); // AED、USD和EUR三种币种

        // 找到AED的统计
        /** @var array{totalCount: int, successCount: int, totalAmount: int, 'refundAmount.currency': string}|null $aedStats */
        $aedStats = null;
        /** @var array{totalCount: int, successCount: int, totalAmount: int, 'refundAmount.currency': string}|null $usdStats */
        $usdStats = null;
        foreach ($statistics as $stat) {
            $this->assertIsArray($stat);
            $this->assertArrayHasKey('refundAmount.currency', $stat);
            $this->assertIsString($stat['refundAmount.currency']);
            if ('AED' === $stat['refundAmount.currency']) {
                $aedStats = $stat;
            } elseif ('USD' === $stat['refundAmount.currency']) {
                $usdStats = $stat;
            }
        }

        $this->assertNotNull($aedStats);
        $this->assertArrayHasKey('totalCount', $aedStats);
        $this->assertArrayHasKey('successCount', $aedStats);
        $this->assertArrayHasKey('totalAmount', $aedStats);
        $this->assertIsInt($aedStats['totalCount']);
        $this->assertIsInt($aedStats['successCount']);
        $this->assertIsInt($aedStats['totalAmount']);
        $this->assertSame(3, $aedStats['totalCount']); // 3个AED退款
        $this->assertSame(2, $aedStats['successCount']); // 2个成功的AED退款
        $this->assertSame(8000, $aedStats['totalAmount']); // 总金额8000

        $this->assertNotNull($usdStats);
        $this->assertArrayHasKey('totalCount', $usdStats);
        $this->assertArrayHasKey('successCount', $usdStats);
        $this->assertArrayHasKey('totalAmount', $usdStats);
        $this->assertIsInt($usdStats['totalCount']);
        $this->assertIsInt($usdStats['successCount']);
        $this->assertIsInt($usdStats['totalAmount']);
        $this->assertSame(1, $usdStats['totalCount']); // 1个USD退款
        $this->assertSame(1, $usdStats['successCount']); // 1个成功的USD退款
        $this->assertSame(1000, $usdStats['totalAmount']); // 总金额1000
    }

    public function testGetRefundStatisticsWithDateRange(): void
    {
        $startDate = new \DateTimeImmutable('2023-01-01 00:00:00');
        $endDate = new \DateTimeImmutable('2023-01-31 23:59:59');

        // 创建在范围内的退款
        $amount1 = new PayByAmount('5000', 'AED');
        $refundInRange = $this->createNewEntity();
        $refundInRange->setRefundAmount($amount1);
        $refundInRange->setStatus(PayByRefundStatus::SUCCESS);
        $refundInRange->setCreateTime(new \DateTimeImmutable('2023-01-15 10:00:00'));
        $this->repository->save($refundInRange, true);

        // 创建在范围外的退款
        $amount2 = new PayByAmount('3000', 'AED');
        $refundOutOfRange = $this->createNewEntity();
        $refundOutOfRange->setRefundAmount($amount2);
        $refundOutOfRange->setStatus(PayByRefundStatus::SUCCESS);
        $refundOutOfRange->setCreateTime(new \DateTimeImmutable('2023-02-01 10:00:00'));
        $this->repository->save($refundOutOfRange, true);

        $statistics = $this->repository->getRefundStatistics($startDate, $endDate);

        $this->assertCount(1, $statistics);
        $this->assertNotEmpty($statistics);
        $firstStat = reset($statistics);
        $this->assertIsArray($firstStat);
        $this->assertArrayHasKey('totalCount', $firstStat);
        $this->assertArrayHasKey('successCount', $firstStat);
        $this->assertArrayHasKey('totalAmount', $firstStat);
        $this->assertIsInt($firstStat['totalCount']);
        $this->assertIsInt($firstStat['successCount']);
        $this->assertIsInt($firstStat['totalAmount']);
        $this->assertSame(1, $firstStat['totalCount']);
        $this->assertSame(1, $firstStat['successCount']);
        $this->assertSame(5000, $firstStat['totalAmount']);
    }
}
