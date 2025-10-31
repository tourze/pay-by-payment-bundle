<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByTransfer;
use Tourze\PayByPaymentBundle\Enum\PayByTransferStatus;
use Tourze\PayByPaymentBundle\Enum\PayByTransferType;
use Tourze\PayByPaymentBundle\Repository\PayByTransferRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(PayByTransferRepository::class)]
#[RunTestsInSeparateProcesses]
class PayByTransferRepositoryTest extends AbstractRepositoryTestCase
{
    private PayByTransferRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(PayByTransferRepository::class);

        // 清理可能存在的fixture数据，确保测试隔离
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . PayByTransfer::class)->execute();

        // 创建一些基础数据来满足DataFixture测试要求
        $transfer = $this->createNewEntity();
        $transfer->setTransferId('fixture-transfer-id');
        $transfer->setMerchantTransferNo('fixture-merchant-transfer-no');
        $transfer->setStatus(PayByTransferStatus::CANCELLED); // 设为取消状态，不影响其他状态测试
        $transfer->setTransferAmount(new PayByAmount('1000', 'EUR')); // 使用EUR币种，不影响AED/USD统计测试
        $transfer->setTransferType(PayByTransferType::TRANSFER_TO_THIRD_PARTY); // 使用不同类型，不影响统计测试
        $this->repository->save($transfer, true);
    }

    protected function getRepository(): PayByTransferRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): PayByTransfer
    {
        $transferAmount = new PayByAmount('5000', 'AED');

        $transfer = new PayByTransfer();
        $transfer->setTransferId('transfer-' . uniqid());
        $transfer->setMerchantTransferNo('merchant-transfer-' . uniqid());
        $transfer->setTransferAmount($transferAmount);
        $transfer->setTransferType(PayByTransferType::TRANSFER_TO_BANK);
        $transfer->setFromAccount('from-account-123');
        $transfer->setToAccount('to-account-456');
        $transfer->setTransferReason('Test transfer');
        $transfer->setNotifyUrl('https://example.com/transfer-notify');
        $transfer->setStatus(PayByTransferStatus::PENDING);

        return $transfer;
    }

    public function testSaveAndFind(): void
    {
        $transfer = $this->createNewEntity();
        $this->repository->save($transfer, true);

        $foundTransfer = $this->repository->find($transfer->getId());

        $this->assertNotNull($foundTransfer);
        $this->assertSame($transfer->getTransferId(), $foundTransfer->getTransferId());
        $this->assertSame($transfer->getMerchantTransferNo(), $foundTransfer->getMerchantTransferNo());
        $this->assertSame($transfer->getFromAccount(), $foundTransfer->getFromAccount());
        $this->assertSame($transfer->getToAccount(), $foundTransfer->getToAccount());
    }

    public function testRemove(): void
    {
        $transfer = $this->createNewEntity();
        $this->repository->save($transfer, true);
        $id = $transfer->getId();

        $this->repository->remove($transfer, true);

        $foundTransfer = $this->repository->find($id);
        $this->assertNull($foundTransfer);
    }

    public function testFindByTransferId(): void
    {
        $transfer = $this->createNewEntity();
        $transferId = 'unique-transfer-id-12345';
        $transfer->setTransferId($transferId);
        $this->repository->save($transfer, true);

        $foundTransfer = $this->repository->findByTransferId($transferId);

        $this->assertNotNull($foundTransfer);
        $this->assertSame($transferId, $foundTransfer->getTransferId());
        $this->assertSame($transfer->getId(), $foundTransfer->getId());
    }

    public function testFindByTransferIdWhenNotExists(): void
    {
        $foundTransfer = $this->repository->findByTransferId('non-existent-transfer-id');

        $this->assertNull($foundTransfer);
    }

    public function testFindByMerchantTransferNo(): void
    {
        $transfer = $this->createNewEntity();
        $merchantTransferNo = 'unique-merchant-transfer-no-12345';
        $transfer->setMerchantTransferNo($merchantTransferNo);
        $this->repository->save($transfer, true);

        $foundTransfer = $this->repository->findByMerchantTransferNo($merchantTransferNo);

        $this->assertNotNull($foundTransfer);
        $this->assertSame($merchantTransferNo, $foundTransfer->getMerchantTransferNo());
        $this->assertSame($transfer->getId(), $foundTransfer->getId());
    }

    public function testFindByMerchantTransferNoWhenNotExists(): void
    {
        $foundTransfer = $this->repository->findByMerchantTransferNo('non-existent-merchant-transfer-no');

        $this->assertNull($foundTransfer);
    }

    public function testFindByFromAccount(): void
    {
        $fromAccount = 'test-from-account-123';

        // 创建多个转账使用相同的from账户
        $transfer1 = $this->createNewEntity();
        $transfer1->setFromAccount($fromAccount);
        $this->repository->save($transfer1, true);

        $transfer2 = $this->createNewEntity();
        $transfer2->setFromAccount($fromAccount);
        $this->repository->save($transfer2, true);

        // 创建使用不同from账户的转账
        $transfer3 = $this->createNewEntity();
        $transfer3->setFromAccount('different-from-account');
        $this->repository->save($transfer3, true);

        $transfers = $this->repository->findByFromAccount($fromAccount);

        $this->assertCount(2, $transfers);
        foreach ($transfers as $transfer) {
            $this->assertSame($fromAccount, $transfer->getFromAccount());
        }
    }

    public function testFindByFromAccountWhenNotExists(): void
    {
        $transfers = $this->repository->findByFromAccount('non-existent-from-account');

        $this->assertEmpty($transfers);
    }

    public function testFindByToAccount(): void
    {
        $toAccount = 'test-to-account-456';

        // 创建多个转账使用相同的to账户
        $transfer1 = $this->createNewEntity();
        $transfer1->setToAccount($toAccount);
        $this->repository->save($transfer1, true);

        $transfer2 = $this->createNewEntity();
        $transfer2->setToAccount($toAccount);
        $this->repository->save($transfer2, true);

        // 创建使用不同to账户的转账
        $transfer3 = $this->createNewEntity();
        $transfer3->setToAccount('different-to-account');
        $this->repository->save($transfer3, true);

        $transfers = $this->repository->findByToAccount($toAccount);

        $this->assertCount(2, $transfers);
        foreach ($transfers as $transfer) {
            $this->assertSame($toAccount, $transfer->getToAccount());
        }
    }

    public function testFindByToAccountWhenNotExists(): void
    {
        $transfers = $this->repository->findByToAccount('non-existent-to-account');

        $this->assertEmpty($transfers);
    }

    public function testFindByStatus(): void
    {
        // 创建不同状态的转账
        $pendingTransfer1 = $this->createNewEntity();
        $pendingTransfer1->setStatus(PayByTransferStatus::PENDING);
        $this->repository->save($pendingTransfer1, true);

        $pendingTransfer2 = $this->createNewEntity();
        $pendingTransfer2->setStatus(PayByTransferStatus::PENDING);
        $this->repository->save($pendingTransfer2, true);

        $successTransfer = $this->createNewEntity();
        $successTransfer->setStatus(PayByTransferStatus::SUCCESS);
        $this->repository->save($successTransfer, true);

        $pendingTransfers = $this->repository->findByStatus(PayByTransferStatus::PENDING);
        $successTransfers = $this->repository->findByStatus(PayByTransferStatus::SUCCESS);

        $this->assertCount(2, $pendingTransfers);
        $this->assertCount(1, $successTransfers);

        foreach ($pendingTransfers as $transfer) {
            $this->assertSame(PayByTransferStatus::PENDING, $transfer->getStatus());
        }

        $this->assertSame(PayByTransferStatus::SUCCESS, $successTransfers[0]->getStatus());
    }

    public function testFindPendingTransfers(): void
    {
        // 创建待处理转账
        $pendingTransfer1 = $this->createNewEntity();
        $pendingTransfer1->setStatus(PayByTransferStatus::PENDING);
        $this->repository->save($pendingTransfer1, true);

        $pendingTransfer2 = $this->createNewEntity();
        $pendingTransfer2->setStatus(PayByTransferStatus::PENDING);
        $this->repository->save($pendingTransfer2, true);

        // 创建其他状态转账
        $successTransfer = $this->createNewEntity();
        $successTransfer->setStatus(PayByTransferStatus::SUCCESS);
        $this->repository->save($successTransfer, true);

        $pendingTransfers = $this->repository->findPendingTransfers();

        $this->assertCount(2, $pendingTransfers);
        foreach ($pendingTransfers as $transfer) {
            $this->assertSame(PayByTransferStatus::PENDING, $transfer->getStatus());
        }
    }

    public function testFindSuccessfulTransfers(): void
    {
        // 创建成功转账
        $successTransfer1 = $this->createNewEntity();
        $successTransfer1->setStatus(PayByTransferStatus::SUCCESS);
        $this->repository->save($successTransfer1, true);

        $successTransfer2 = $this->createNewEntity();
        $successTransfer2->setStatus(PayByTransferStatus::SUCCESS);
        $this->repository->save($successTransfer2, true);

        // 创建其他状态转账
        $pendingTransfer = $this->createNewEntity();
        $pendingTransfer->setStatus(PayByTransferStatus::PENDING);
        $this->repository->save($pendingTransfer, true);

        $successfulTransfers = $this->repository->findSuccessfulTransfers();

        $this->assertCount(2, $successfulTransfers);
        foreach ($successfulTransfers as $transfer) {
            $this->assertSame(PayByTransferStatus::SUCCESS, $transfer->getStatus());
        }
    }

    public function testFindTransfersByDateRange(): void
    {
        $startDate = new \DateTimeImmutable('2023-01-01 00:00:00');
        $endDate = new \DateTimeImmutable('2023-01-31 23:59:59');
        $outsideDate = new \DateTimeImmutable('2023-02-01 00:00:00');

        // 创建在范围内的转账
        $transferInRange1 = $this->createNewEntity();
        $transferInRange1->setCreateTime(new \DateTimeImmutable('2023-01-15 10:00:00'));
        $this->repository->save($transferInRange1, true);

        $transferInRange2 = $this->createNewEntity();
        $transferInRange2->setCreateTime(new \DateTimeImmutable('2023-01-20 15:30:00'));
        $this->repository->save($transferInRange2, true);

        // 创建在范围外的转账
        $transferOutOfRange = $this->createNewEntity();
        $transferOutOfRange->setCreateTime($outsideDate);
        $this->repository->save($transferOutOfRange, true);

        $transfersInRange = $this->repository->findTransfersByDateRange($startDate, $endDate);

        $this->assertCount(2, $transfersInRange);

        // 验证排序（按createTime DESC）
        $this->assertTrue($transfersInRange[0]->getCreateTime() >= $transfersInRange[1]->getCreateTime());

        foreach ($transfersInRange as $transfer) {
            $this->assertTrue($transfer->getCreateTime() >= $startDate);
            $this->assertTrue($transfer->getCreateTime() <= $endDate);
        }
    }

    public function testGetTransferStatistics(): void
    {
        // 创建不同状态、币种和类型的转账
        $aedAmount1 = new PayByAmount('5000', 'AED');
        $successTransfer1 = $this->createNewEntity();
        $successTransfer1->setTransferAmount($aedAmount1);
        $successTransfer1->setStatus(PayByTransferStatus::SUCCESS);
        $successTransfer1->setTransferType(PayByTransferType::TRANSFER_TO_BANK);
        $this->repository->save($successTransfer1, true);

        $aedAmount2 = new PayByAmount('3000', 'AED');
        $successTransfer2 = $this->createNewEntity();
        $successTransfer2->setTransferAmount($aedAmount2);
        $successTransfer2->setStatus(PayByTransferStatus::SUCCESS);
        $successTransfer2->setTransferType(PayByTransferType::TRANSFER_TO_BANK);
        $this->repository->save($successTransfer2, true);

        $aedAmount3 = new PayByAmount('2000', 'AED');
        $pendingTransfer = $this->createNewEntity();
        $pendingTransfer->setTransferAmount($aedAmount3);
        $pendingTransfer->setStatus(PayByTransferStatus::PENDING);
        $pendingTransfer->setTransferType(PayByTransferType::TRANSFER_TO_BANK);
        $this->repository->save($pendingTransfer, true);

        $usdAmount = new PayByAmount('1000', 'USD');
        $successTransferUsd = $this->createNewEntity();
        $successTransferUsd->setTransferAmount($usdAmount);
        $successTransferUsd->setStatus(PayByTransferStatus::SUCCESS);
        $successTransferUsd->setTransferType(PayByTransferType::INTERNAL);
        $this->repository->save($successTransferUsd, true);

        $statistics = $this->repository->getTransferStatistics();

        $this->assertCount(3, $statistics); // AED-TRANSFER_TO_BANK、USD-INTERNAL和EUR-TRANSFER_TO_THIRD_PARTY三个组合

        // 找到对应的统计
        /** @var array{totalCount: int, successCount: int, totalAmount: int, 'transferAmount.currency': string, transferType: PayByTransferType}|null $aedStats */
        $aedStats = null;
        /** @var array{totalCount: int, successCount: int, totalAmount: int, 'transferAmount.currency': string, transferType: PayByTransferType}|null $usdStats */
        $usdStats = null;
        foreach ($statistics as $stat) {
            $this->assertIsArray($stat);
            $this->assertArrayHasKey('transferAmount.currency', $stat);
            $this->assertArrayHasKey('transferType', $stat);
            $this->assertIsString($stat['transferAmount.currency']);
            $this->assertInstanceOf(PayByTransferType::class, $stat['transferType']);
            if ('AED' === $stat['transferAmount.currency'] && PayByTransferType::TRANSFER_TO_BANK === $stat['transferType']) {
                $aedStats = $stat;
            } elseif ('USD' === $stat['transferAmount.currency'] && PayByTransferType::INTERNAL === $stat['transferType']) {
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
        $this->assertSame(3, $aedStats['totalCount']); // 3个AED转账
        $this->assertSame(2, $aedStats['successCount']); // 2个成功的AED转账
        $this->assertSame(8000, $aedStats['totalAmount']); // 总金额8000

        $this->assertNotNull($usdStats);
        $this->assertArrayHasKey('totalCount', $usdStats);
        $this->assertArrayHasKey('successCount', $usdStats);
        $this->assertArrayHasKey('totalAmount', $usdStats);
        $this->assertIsInt($usdStats['totalCount']);
        $this->assertIsInt($usdStats['successCount']);
        $this->assertIsInt($usdStats['totalAmount']);
        $this->assertSame(1, $usdStats['totalCount']); // 1个USD转账
        $this->assertSame(1, $usdStats['successCount']); // 1个成功的USD转账
        $this->assertSame(1000, $usdStats['totalAmount']); // 总金额1000
    }

    public function testGetTransferStatisticsWithDateRange(): void
    {
        $startDate = new \DateTimeImmutable('2023-01-01 00:00:00');
        $endDate = new \DateTimeImmutable('2023-01-31 23:59:59');

        // 创建在范围内的转账
        $amount1 = new PayByAmount('5000', 'AED');
        $transferInRange = $this->createNewEntity();
        $transferInRange->setTransferAmount($amount1);
        $transferInRange->setStatus(PayByTransferStatus::SUCCESS);
        $transferInRange->setTransferType(PayByTransferType::TRANSFER_TO_BANK);
        $transferInRange->setCreateTime(new \DateTimeImmutable('2023-01-15 10:00:00'));
        $this->repository->save($transferInRange, true);

        // 创建在范围外的转账
        $amount2 = new PayByAmount('3000', 'AED');
        $transferOutOfRange = $this->createNewEntity();
        $transferOutOfRange->setTransferAmount($amount2);
        $transferOutOfRange->setStatus(PayByTransferStatus::SUCCESS);
        $transferOutOfRange->setTransferType(PayByTransferType::TRANSFER_TO_BANK);
        $transferOutOfRange->setCreateTime(new \DateTimeImmutable('2023-02-01 10:00:00'));
        $this->repository->save($transferOutOfRange, true);

        $statistics = $this->repository->getTransferStatistics($startDate, $endDate);

        $this->assertCount(1, $statistics);
        $this->assertNotEmpty($statistics);
        $firstStat = reset($statistics);
        $this->assertIsArray($firstStat);
        $this->assertArrayHasKey('totalCount', $firstStat);
        $this->assertArrayHasKey('successCount', $firstStat);
        $this->assertArrayHasKey('totalAmount', $firstStat);
        $this->assertArrayHasKey('transferType', $firstStat);
        $this->assertIsInt($firstStat['totalCount']);
        $this->assertIsInt($firstStat['successCount']);
        $this->assertIsInt($firstStat['totalAmount']);
        $this->assertInstanceOf(PayByTransferType::class, $firstStat['transferType']);
        $this->assertSame(1, $firstStat['totalCount']);
        $this->assertSame(1, $firstStat['successCount']);
        $this->assertSame(5000, $firstStat['totalAmount']);
        $this->assertSame(PayByTransferType::TRANSFER_TO_BANK, $firstStat['transferType']);
    }
}
