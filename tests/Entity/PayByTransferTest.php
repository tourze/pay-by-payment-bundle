<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByTransfer;
use Tourze\PayByPaymentBundle\Enum\PayByTransferStatus;
use Tourze\PayByPaymentBundle\Enum\PayByTransferType;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(PayByTransfer::class)]
class PayByTransferTest extends AbstractEntityTestCase
{
    private PayByTransfer $transfer;

    protected function setUp(): void
    {
        $this->transfer = $this->createEntity();
    }

    protected function createEntity(): PayByTransfer
    {
        return new PayByTransfer();
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $properties = [
            'transferId' => 'test_transfer_123',
            'merchantTransferNo' => 'merchant_transfer_123',
            'fromAccount' => 'from_account_123',
            'toAccount' => 'to_account_123',
            'transferReason' => 'Test transfer reason',
            'notifyUrl' => 'https://example.com/notify',
        ];

        foreach ($properties as $property => $sampleValue) {
            yield $property => [$property, $sampleValue];
        }
    }

    public function testConstructor(): void
    {
        $transfer = new PayByTransfer();

        $this->assertSame(PayByTransferStatus::PENDING, $transfer->getStatus());
    }

    public function testTransferIdGetterAndSetter(): void
    {
        $transferId = 'transfer-123456';
        $this->transfer->setTransferId($transferId);

        $this->assertSame($transferId, $this->transfer->getTransferId());
    }

    public function testMerchantTransferNoGetterAndSetter(): void
    {
        $merchantTransferNo = 'merchant-transfer-123';
        $this->transfer->setMerchantTransferNo($merchantTransferNo);

        $this->assertSame($merchantTransferNo, $this->transfer->getMerchantTransferNo());
    }

    public function testTransferTypeGetterAndSetter(): void
    {
        $transferType = PayByTransferType::BANK_TRANSFER;
        $this->transfer->setTransferType($transferType);

        $this->assertSame($transferType, $this->transfer->getTransferType());
    }

    public function testFromAccountGetterAndSetter(): void
    {
        $fromAccount = 'account-from-123';
        $this->transfer->setFromAccount($fromAccount);

        $this->assertSame($fromAccount, $this->transfer->getFromAccount());
    }

    public function testToAccountGetterAndSetter(): void
    {
        $toAccount = 'account-to-456';
        $this->transfer->setToAccount($toAccount);

        $this->assertSame($toAccount, $this->transfer->getToAccount());
    }

    public function testToAccountWithNull(): void
    {
        $this->transfer->setToAccount(null);

        $this->assertNull($this->transfer->getToAccount());
    }

    public function testTransferAmountGetterAndSetter(): void
    {
        $amount = new PayByAmount('250.75', 'EUR');
        $this->transfer->setTransferAmount($amount);

        $this->assertSame($amount, $this->transfer->getTransferAmount());
    }

    public function testStatusGetterAndSetter(): void
    {
        $this->transfer->setStatus(PayByTransferStatus::SUCCESS);

        $this->assertSame(PayByTransferStatus::SUCCESS, $this->transfer->getStatus());
    }

    public function testTransferReasonGetterAndSetter(): void
    {
        $transferReason = 'Monthly salary payment';
        $this->transfer->setTransferReason($transferReason);

        $this->assertSame($transferReason, $this->transfer->getTransferReason());
    }

    public function testTransferReasonWithNull(): void
    {
        $this->transfer->setTransferReason(null);

        $this->assertNull($this->transfer->getTransferReason());
    }

    public function testNotifyUrlGetterAndSetter(): void
    {
        $notifyUrl = 'https://example.com/transfer-notify';
        $this->transfer->setNotifyUrl($notifyUrl);

        $this->assertSame($notifyUrl, $this->transfer->getNotifyUrl());
    }

    public function testNotifyUrlWithNull(): void
    {
        $this->transfer->setNotifyUrl(null);

        $this->assertNull($this->transfer->getNotifyUrl());
    }

    public function testBankTransferInfoGetterAndSetter(): void
    {
        $bankTransferInfo = [
            'bank_name' => 'Test Bank',
            'account_number' => '1234567890',
            'routing_number' => '987654321',
        ];
        $this->transfer->setBankTransferInfo($bankTransferInfo);

        $this->assertSame($bankTransferInfo, $this->transfer->getBankTransferInfo());
    }

    public function testBankTransferInfoWithNull(): void
    {
        $this->transfer->setBankTransferInfo(null);

        $this->assertNull($this->transfer->getBankTransferInfo());
    }

    public function testAccessoryContentGetterAndSetter(): void
    {
        $accessoryContent = ['key' => 'value', 'metadata' => ['transfer' => true]];
        $this->transfer->setAccessoryContent($accessoryContent);

        $this->assertSame($accessoryContent, $this->transfer->getAccessoryContent());
    }

    public function testAccessoryContentWithNull(): void
    {
        $this->transfer->setAccessoryContent(null);

        $this->assertNull($this->transfer->getAccessoryContent());
    }

    public function testTransferTimeGetterAndSetter(): void
    {
        $transferTime = new \DateTimeImmutable('2023-01-01 15:00:00');
        $this->transfer->setTransferTime($transferTime);

        $this->assertSame($transferTime, $this->transfer->getTransferTime());
    }

    public function testTransferTimeWithNull(): void
    {
        $this->transfer->setTransferTime(null);

        $this->assertNull($this->transfer->getTransferTime());
    }

    public function testIsTransferred(): void
    {
        $this->transfer->setStatus(PayByTransferStatus::PENDING);
        $this->assertFalse($this->transfer->isTransferred());

        $this->transfer->setStatus(PayByTransferStatus::PROCESSING);
        $this->assertFalse($this->transfer->isTransferred());

        $this->transfer->setStatus(PayByTransferStatus::SUCCESS);
        $this->assertTrue($this->transfer->isTransferred());

        $this->transfer->setStatus(PayByTransferStatus::FAILED);
        $this->assertFalse($this->transfer->isTransferred());
    }

    public function testIsFinal(): void
    {
        $this->transfer->setStatus(PayByTransferStatus::PENDING);
        $this->assertFalse($this->transfer->isFinal());

        $this->transfer->setStatus(PayByTransferStatus::PROCESSING);
        $this->assertFalse($this->transfer->isFinal());

        $this->transfer->setStatus(PayByTransferStatus::SUCCESS);
        $this->assertTrue($this->transfer->isFinal());

        $this->transfer->setStatus(PayByTransferStatus::FAILED);
        $this->assertTrue($this->transfer->isFinal());

        $this->transfer->setStatus(PayByTransferStatus::CANCELLED);
        $this->assertTrue($this->transfer->isFinal());
    }

    public function testIsBankTransfer(): void
    {
        $this->transfer->setTransferType(PayByTransferType::BANK_TRANSFER);
        $this->assertTrue($this->transfer->isBankTransfer());

        $this->transfer->setTransferType(PayByTransferType::INTERNAL);
        $this->assertFalse($this->transfer->isBankTransfer());

        $this->transfer->setTransferType(PayByTransferType::TRANSFER_TO_BALANCE);
        $this->assertFalse($this->transfer->isBankTransfer());
    }

    public function testIsInternalTransfer(): void
    {
        $this->transfer->setTransferType(PayByTransferType::INTERNAL);
        $this->assertTrue($this->transfer->isInternalTransfer());

        $this->transfer->setTransferType(PayByTransferType::BANK_TRANSFER);
        $this->assertFalse($this->transfer->isInternalTransfer());

        $this->transfer->setTransferType(PayByTransferType::TRANSFER_TO_BANK);
        $this->assertFalse($this->transfer->isInternalTransfer());
    }

    public function testGetAmountFormatted(): void
    {
        $amount = new PayByAmount('123.45', 'GBP');
        $this->transfer->setTransferAmount($amount);

        $this->assertSame('123.45 GBP', $this->transfer->getAmountFormatted());
    }

    public function testDeprecatedCreatedAtMethods(): void
    {
        $createTime = new \DateTimeImmutable('2023-01-01 10:00:00');

        $this->transfer->setCreateTime($createTime);
        $this->assertSame($createTime, $this->transfer->getCreateTime());
    }

    public function testDeprecatedUpdatedAtMethods(): void
    {
        $updateTime = new \DateTimeImmutable('2023-01-01 12:00:00');

        $this->transfer->setUpdateTime($updateTime);
        $this->assertSame($updateTime, $this->transfer->getUpdateTime());
    }

    public function testToString(): void
    {
        $transferId = 'transfer-123456';
        $merchantTransferNo = 'merchant-transfer-123';

        $this->transfer->setTransferId($transferId);
        $this->transfer->setMerchantTransferNo($merchantTransferNo);

        $expected = sprintf('%s (%s)', $transferId, $merchantTransferNo);
        $this->assertSame($expected, (string) $this->transfer);
        $this->assertSame($expected, $this->transfer->__toString());
    }
}
