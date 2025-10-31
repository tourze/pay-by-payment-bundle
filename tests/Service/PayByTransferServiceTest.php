<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByConfig;
use Tourze\PayByPaymentBundle\Entity\PayByTransfer;
use Tourze\PayByPaymentBundle\Enum\PayByTransferStatus;
use Tourze\PayByPaymentBundle\Enum\PayByTransferType;
use Tourze\PayByPaymentBundle\Service\PayByTransferService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(PayByTransferService::class)]
#[RunTestsInSeparateProcesses]
final class PayByTransferServiceTest extends AbstractIntegrationTestCase
{
    private PayByTransferService $service;

    protected function onSetUp(): void
    {
        // Create default PayBy configuration
        $this->createDefaultPayByConfig();

        $this->service = self::getService(PayByTransferService::class);
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

    public function testGetTransferFound(): void
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

        $result = $this->service->getTransfer($merchantTransferNo);

        $this->assertNotNull($result);
        $this->assertEquals($merchantTransferNo, $result->getMerchantTransferNo());
    }

    public function testGetTransferNotFound(): void
    {
        $merchantTransferNo = 'nonexistent-transfer';

        $result = $this->service->getTransfer($merchantTransferNo);

        $this->assertNull($result);
    }

    public function testHandleTransferNotificationSuccess(): void
    {
        $merchantTransferNo = 'test-notification-' . uniqid();
        $transferId = 'api-transfer-notification-' . uniqid();

        // Create transfer first
        $transfer = new PayByTransfer();
        $transfer->setMerchantTransferNo($merchantTransferNo);
        $transfer->setTransferId($transferId);
        $transfer->setTransferType(PayByTransferType::INTERNAL);
        $transfer->setFromAccount('acc1');
        $transfer->setToAccount('acc2');
        $transfer->setTransferAmount(new PayByAmount('100', 'CNY'));
        $transfer->setTransferReason('Test transfer');
        $transfer->setStatus(PayByTransferStatus::PENDING);

        $entityManager = self::getEntityManager();
        $entityManager->persist($transfer);
        $entityManager->flush();

        $notificationData = [
            'transferId' => $transferId,
            'status' => PayByTransferStatus::SUCCESS->value,
            'transferTime' => '2024-01-01T12:00:00Z',
        ];

        $result = $this->service->handleTransferNotification($notificationData);

        $this->assertTrue($result);

        // Verify transfer was updated
        $updatedTransfer = $this->service->getTransfer($merchantTransferNo);
        $this->assertNotNull($updatedTransfer);
        $this->assertEquals(PayByTransferStatus::SUCCESS, $updatedTransfer->getStatus());
        $this->assertNotNull($updatedTransfer->getTransferTime());
    }

    public function testHandleTransferNotificationMissingTransferId(): void
    {
        $notificationData = [
            'status' => PayByTransferStatus::SUCCESS->value,
        ];

        $result = $this->service->handleTransferNotification($notificationData);

        $this->assertFalse($result);
    }

    public function testHandleTransferNotificationTransferNotFound(): void
    {
        $transferId = 'nonexistent-transfer';
        $notificationData = [
            'transferId' => $transferId,
            'status' => PayByTransferStatus::SUCCESS->value,
        ];

        $result = $this->service->handleTransferNotification($notificationData);

        $this->assertFalse($result);
    }

    public function testHandleTransferNotificationInvalidStatus(): void
    {
        $merchantTransferNo = 'test-invalid-status-' . uniqid();
        $transferId = 'api-transfer-invalid-' . uniqid();

        // Create transfer first
        $transfer = new PayByTransfer();
        $transfer->setMerchantTransferNo($merchantTransferNo);
        $transfer->setTransferId($transferId);
        $transfer->setTransferType(PayByTransferType::INTERNAL);
        $transfer->setFromAccount('acc1');
        $transfer->setToAccount('acc2');
        $transfer->setTransferAmount(new PayByAmount('100', 'CNY'));
        $transfer->setTransferReason('Test transfer');
        $transfer->setStatus(PayByTransferStatus::PENDING);

        $entityManager = self::getEntityManager();
        $entityManager->persist($transfer);
        $entityManager->flush();

        $notificationData = [
            'transferId' => $transferId,
            'status' => 'INVALID_STATUS',
        ];

        $result = $this->service->handleTransferNotification($notificationData);

        $this->assertFalse($result);
    }

    public function testHandleTransferNotificationInvalidTransferTime(): void
    {
        $merchantTransferNo = 'test-invalid-time-' . uniqid();
        $transferId = 'api-transfer-time-' . uniqid();

        // Create transfer first
        $transfer = new PayByTransfer();
        $transfer->setMerchantTransferNo($merchantTransferNo);
        $transfer->setTransferId($transferId);
        $transfer->setTransferType(PayByTransferType::INTERNAL);
        $transfer->setFromAccount('acc1');
        $transfer->setToAccount('acc2');
        $transfer->setTransferAmount(new PayByAmount('100', 'CNY'));
        $transfer->setTransferReason('Test transfer');
        $transfer->setStatus(PayByTransferStatus::PENDING);

        $entityManager = self::getEntityManager();
        $entityManager->persist($transfer);
        $entityManager->flush();

        $notificationData = [
            'transferId' => $transferId,
            'status' => PayByTransferStatus::SUCCESS->value,
            'transferTime' => 'invalid-date',
        ];

        $result = $this->service->handleTransferNotification($notificationData);

        $this->assertTrue($result);

        // Verify status was updated even with invalid time
        $updatedTransfer = $this->service->getTransfer($merchantTransferNo);
        $this->assertNotNull($updatedTransfer);
        $this->assertEquals(PayByTransferStatus::SUCCESS, $updatedTransfer->getStatus());
    }

    /**
     * 测试 createInternalTransfer 方法
     */
    public function testCreateInternalTransfer(): void
    {
        $merchantTransferNo = 'test-create-internal-' . uniqid();

        // 期望抛出异常，因为没有真实的API配置
        $this->expectException(\Throwable::class);

        $this->service->createInternalTransfer(
            $merchantTransferNo,
            'from-account',
            'to-account',
            new PayByAmount('100.00', 'AED'),
            'Test internal transfer'
        );
    }

    /**
     * 测试 createBankTransfer 方法
     */
    public function testCreateBankTransfer(): void
    {
        $merchantTransferNo = 'test-create-bank-' . uniqid();

        // 期望抛出异常，因为没有真实的API配置
        $this->expectException(\Throwable::class);

        $this->service->createBankTransfer(
            $merchantTransferNo,
            'from-account',
            [
                'bankCode' => 'TEST_BANK',
                'accountNumber' => '1234567890',
            ],
            new PayByAmount('100.00', 'AED'),
            'Test bank transfer',
            null
        );
    }

    /**
     * 测试 queryTransfer 方法
     */
    public function testQueryTransfer(): void
    {
        $transferId = 'test-query-transfer-' . uniqid();

        // 测试转账不存在的情况
        $result = $this->service->queryTransfer($transferId);
        $this->assertNull($result);
    }
}
