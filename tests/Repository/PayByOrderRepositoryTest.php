<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByOrder;
use Tourze\PayByPaymentBundle\Enum\PayByOrderStatus;
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;
use Tourze\PayByPaymentBundle\Repository\PayByOrderRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(PayByOrderRepository::class)]
#[RunTestsInSeparateProcesses]
class PayByOrderRepositoryTest extends AbstractRepositoryTestCase
{
    private PayByOrderRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(PayByOrderRepository::class);

        // 清理可能存在的fixture数据，确保测试隔离
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . PayByOrder::class)->execute();

        // 创建一些基础数据来满足DataFixture测试要求
        $order = $this->createNewEntity();
        $order->setOrderId('fixture-order-id');
        $order->setMerchantOrderNo('fixture-merchant-no');
        $order->setStatus(PayByOrderStatus::CANCELLED); // 设为取消状态，不影响其他状态测试
        $order->setTotalAmount(new PayByAmount('1000', 'EUR')); // 使用EUR币种，不影响AED/USD统计测试
        $this->repository->save($order, true);
    }

    protected function getRepository(): PayByOrderRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): PayByOrder
    {
        $order = new PayByOrder();
        $totalAmount = new PayByAmount('10000', 'AED');

        $order->setOrderId('order-' . uniqid());
        $order->setMerchantOrderNo('merchant-' . uniqid());
        $order->setSubject('Test Order');
        $order->setBody('Test order description');
        $order->setTotalAmount($totalAmount);
        $order->setNotifyUrl('https://example.com/notify');
        $order->setPaySceneCode(PayByPaySceneCode::PAYPAGE);
        $order->setStatus(PayByOrderStatus::PENDING);

        return $order;
    }

    public function testSaveAndFind(): void
    {
        $order = $this->createNewEntity();
        $this->repository->save($order, true);

        $foundOrder = $this->repository->find($order->getId());

        $this->assertNotNull($foundOrder);
        $this->assertSame($order->getOrderId(), $foundOrder->getOrderId());
        $this->assertSame($order->getMerchantOrderNo(), $foundOrder->getMerchantOrderNo());
        $this->assertSame($order->getSubject(), $foundOrder->getSubject());
    }

    public function testRemove(): void
    {
        $order = $this->createNewEntity();
        $this->repository->save($order, true);
        $id = $order->getId();

        $this->repository->remove($order, true);

        $foundOrder = $this->repository->find($id);
        $this->assertNull($foundOrder);
    }

    public function testFindByOrderId(): void
    {
        $order = $this->createNewEntity();
        $orderId = 'unique-order-id-12345';
        $order->setOrderId($orderId);
        $this->repository->save($order, true);

        $foundOrder = $this->repository->findByOrderId($orderId);

        $this->assertNotNull($foundOrder);
        $this->assertSame($orderId, $foundOrder->getOrderId());
        $this->assertSame($order->getId(), $foundOrder->getId());
    }

    public function testFindByOrderIdWhenNotExists(): void
    {
        $foundOrder = $this->repository->findByOrderId('non-existent-order-id');

        $this->assertNull($foundOrder);
    }

    public function testFindByMerchantOrderNo(): void
    {
        $order = $this->createNewEntity();
        $merchantOrderNo = 'unique-merchant-order-no-12345';
        $order->setMerchantOrderNo($merchantOrderNo);
        $this->repository->save($order, true);

        $foundOrder = $this->repository->findByMerchantOrderNo($merchantOrderNo);

        $this->assertNotNull($foundOrder);
        $this->assertSame($merchantOrderNo, $foundOrder->getMerchantOrderNo());
        $this->assertSame($order->getId(), $foundOrder->getId());
    }

    public function testFindByMerchantOrderNoWhenNotExists(): void
    {
        $foundOrder = $this->repository->findByMerchantOrderNo('non-existent-merchant-order-no');

        $this->assertNull($foundOrder);
    }

    public function testFindByStatus(): void
    {
        // 记录测试前现有的SUCCESS订单数量
        $existingSuccessOrders = $this->repository->findByStatus(PayByOrderStatus::SUCCESS);
        $existingSuccessCount = count($existingSuccessOrders);

        // 创建不同状态的订单
        $pendingOrder1 = $this->createNewEntity();
        $pendingOrder1->setStatus(PayByOrderStatus::PENDING);
        $this->repository->save($pendingOrder1, true);

        $pendingOrder2 = $this->createNewEntity();
        $pendingOrder2->setStatus(PayByOrderStatus::PENDING);
        $this->repository->save($pendingOrder2, true);

        $successOrder = $this->createNewEntity();
        $successOrder->setStatus(PayByOrderStatus::SUCCESS);
        $this->repository->save($successOrder, true);

        $pendingOrders = $this->repository->findByStatus(PayByOrderStatus::PENDING);
        $successOrders = $this->repository->findByStatus(PayByOrderStatus::SUCCESS);

        // 验证新创建的订单被正确查询到
        $this->assertGreaterThanOrEqual(2, count($pendingOrders));
        $this->assertSame($existingSuccessCount + 1, count($successOrders));

        // 验证最后创建的订单状态正确
        $newPendingOrders = array_slice($pendingOrders, -2);
        foreach ($newPendingOrders as $order) {
            $this->assertSame(PayByOrderStatus::PENDING, $order->getStatus());
        }

        // 验证最后创建的SUCCESS订单状态正确
        $lastSuccessOrder = end($successOrders);
        $this->assertNotFalse($lastSuccessOrder);
        $this->assertSame(PayByOrderStatus::SUCCESS, $lastSuccessOrder->getStatus());
    }

    public function testFindPendingOrders(): void
    {
        // 创建待支付订单
        $pendingOrder1 = $this->createNewEntity();
        $pendingOrder1->setStatus(PayByOrderStatus::PENDING);
        $this->repository->save($pendingOrder1, true);

        $pendingOrder2 = $this->createNewEntity();
        $pendingOrder2->setStatus(PayByOrderStatus::PENDING);
        $this->repository->save($pendingOrder2, true);

        // 创建其他状态订单
        $successOrder = $this->createNewEntity();
        $successOrder->setStatus(PayByOrderStatus::SUCCESS);
        $this->repository->save($successOrder, true);

        $pendingOrders = $this->repository->findPendingOrders();

        $this->assertCount(2, $pendingOrders);
        foreach ($pendingOrders as $order) {
            $this->assertSame(PayByOrderStatus::PENDING, $order->getStatus());
        }
    }

    public function testFindPaidOrders(): void
    {
        // 记录测试前现有的SUCCESS订单数量
        $existingPaidOrders = $this->repository->findPaidOrders();
        $existingPaidCount = count($existingPaidOrders);

        // 创建成功订单
        $successOrder1 = $this->createNewEntity();
        $successOrder1->setStatus(PayByOrderStatus::SUCCESS);
        $this->repository->save($successOrder1, true);

        $successOrder2 = $this->createNewEntity();
        $successOrder2->setStatus(PayByOrderStatus::SUCCESS);
        $this->repository->save($successOrder2, true);

        // 创建其他状态订单
        $pendingOrder = $this->createNewEntity();
        $pendingOrder->setStatus(PayByOrderStatus::PENDING);
        $this->repository->save($pendingOrder, true);

        $paidOrders = $this->repository->findPaidOrders();

        // 验证新增了2个成功订单
        $this->assertSame($existingPaidCount + 2, count($paidOrders));

        // 验证最后2个订单的状态
        $newPaidOrders = array_slice($paidOrders, -2);
        foreach ($newPaidOrders as $order) {
            $this->assertSame(PayByOrderStatus::SUCCESS, $order->getStatus());
        }
    }

    public function testFindOrdersByDateRange(): void
    {
        $startDate = new \DateTimeImmutable('2023-01-01 00:00:00');
        $endDate = new \DateTimeImmutable('2023-01-31 23:59:59');
        $outsideDate = new \DateTimeImmutable('2023-02-01 00:00:00');

        // 创建在范围内的订单
        $orderInRange1 = $this->createNewEntity();
        $orderInRange1->setCreateTime(new \DateTimeImmutable('2023-01-15 10:00:00'));
        $this->repository->save($orderInRange1, true);

        $orderInRange2 = $this->createNewEntity();
        $orderInRange2->setCreateTime(new \DateTimeImmutable('2023-01-20 15:30:00'));
        $this->repository->save($orderInRange2, true);

        // 创建在范围外的订单
        $orderOutOfRange = $this->createNewEntity();
        $orderOutOfRange->setCreateTime($outsideDate);
        $this->repository->save($orderOutOfRange, true);

        $ordersInRange = $this->repository->findOrdersByDateRange($startDate, $endDate);

        $this->assertCount(2, $ordersInRange);

        // 验证排序（按createTime DESC）
        $this->assertTrue($ordersInRange[0]->getCreateTime() >= $ordersInRange[1]->getCreateTime());

        foreach ($ordersInRange as $order) {
            $this->assertTrue($order->getCreateTime() >= $startDate);
            $this->assertTrue($order->getCreateTime() <= $endDate);
        }
    }

    public function testGetOrderStatistics(): void
    {
        // 创建不同状态和币种的订单
        $aedAmount1 = new PayByAmount('10000', 'AED');
        $successOrder1 = $this->createNewEntity();
        $successOrder1->setTotalAmount($aedAmount1);
        $successOrder1->setStatus(PayByOrderStatus::SUCCESS);
        $this->repository->save($successOrder1, true);

        $aedAmount2 = new PayByAmount('20000', 'AED');
        $successOrder2 = $this->createNewEntity();
        $successOrder2->setTotalAmount($aedAmount2);
        $successOrder2->setStatus(PayByOrderStatus::SUCCESS);
        $this->repository->save($successOrder2, true);

        $aedAmount3 = new PayByAmount('15000', 'AED');
        $pendingOrder = $this->createNewEntity();
        $pendingOrder->setTotalAmount($aedAmount3);
        $pendingOrder->setStatus(PayByOrderStatus::PENDING);
        $this->repository->save($pendingOrder, true);

        $usdAmount = new PayByAmount('5000', 'USD');
        $successOrderUsd = $this->createNewEntity();
        $successOrderUsd->setTotalAmount($usdAmount);
        $successOrderUsd->setStatus(PayByOrderStatus::SUCCESS);
        $this->repository->save($successOrderUsd, true);

        $statistics = $this->repository->getOrderStatistics();

        $this->assertCount(3, $statistics); // AED、USD和EUR三种币种

        // 找到AED的统计
        /** @var array{totalCount: int, successCount: int, totalAmount: int, 'totalAmount.currency': string}|null $aedStats */
        $aedStats = null;
        /** @var array{totalCount: int, successCount: int, totalAmount: int, 'totalAmount.currency': string}|null $usdStats */
        $usdStats = null;
        foreach ($statistics as $stat) {
            $this->assertIsArray($stat);
            $this->assertArrayHasKey('totalAmount.currency', $stat);
            $this->assertIsString($stat['totalAmount.currency']);
            if ('AED' === $stat['totalAmount.currency']) {
                $aedStats = $stat;
            } elseif ('USD' === $stat['totalAmount.currency']) {
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
        $this->assertSame(3, $aedStats['totalCount']); // 3个AED订单
        $this->assertSame(2, $aedStats['successCount']); // 2个成功的AED订单
        $this->assertSame(30000, $aedStats['totalAmount']); // 总金额30000

        $this->assertNotNull($usdStats);
        $this->assertArrayHasKey('totalCount', $usdStats);
        $this->assertArrayHasKey('successCount', $usdStats);
        $this->assertArrayHasKey('totalAmount', $usdStats);
        $this->assertIsInt($usdStats['totalCount']);
        $this->assertIsInt($usdStats['successCount']);
        $this->assertIsInt($usdStats['totalAmount']);
        $this->assertSame(1, $usdStats['totalCount']); // 1个USD订单
        $this->assertSame(1, $usdStats['successCount']); // 1个成功的USD订单
        $this->assertSame(5000, $usdStats['totalAmount']); // 总金额5000
    }

    public function testGetOrderStatisticsWithDateRange(): void
    {
        $startDate = new \DateTimeImmutable('2023-01-01 00:00:00');
        $endDate = new \DateTimeImmutable('2023-01-31 23:59:59');

        // 创建在范围内的订单
        $amount1 = new PayByAmount('10000', 'AED');
        $orderInRange = $this->createNewEntity();
        $orderInRange->setTotalAmount($amount1);
        $orderInRange->setStatus(PayByOrderStatus::SUCCESS);
        $orderInRange->setCreateTime(new \DateTimeImmutable('2023-01-15 10:00:00'));
        $this->repository->save($orderInRange, true);

        // 创建在范围外的订单
        $amount2 = new PayByAmount('20000', 'AED');
        $orderOutOfRange = $this->createNewEntity();
        $orderOutOfRange->setTotalAmount($amount2);
        $orderOutOfRange->setStatus(PayByOrderStatus::SUCCESS);
        $orderOutOfRange->setCreateTime(new \DateTimeImmutable('2023-02-01 10:00:00'));
        $this->repository->save($orderOutOfRange, true);

        $statistics = $this->repository->getOrderStatistics($startDate, $endDate);

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
        $this->assertSame(10000, $firstStat['totalAmount']);
    }
}
