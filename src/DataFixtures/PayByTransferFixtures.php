<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByTransfer;
use Tourze\PayByPaymentBundle\Enum\PayByTransferStatus;
use Tourze\PayByPaymentBundle\Enum\PayByTransferType;

class PayByTransferFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 在测试环境中不加载Fixtures，避免干扰单元测试
        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null;
        if ('test' === $env) {
            return;
        }

        $transfer = new PayByTransfer();
        $transfer->setTransferId('test_transfer_001');
        $transfer->setMerchantTransferNo('merchant_transfer_001');
        $transfer->setTransferAmount(new PayByAmount('10.00', 'CNY'));
        $transfer->setFromAccount('from_account_001');
        $transfer->setToAccount('to_account_001');
        $transfer->setTransferType(PayByTransferType::BANK_TRANSFER);
        $transfer->setStatus(PayByTransferStatus::SUCCESS);
        $transfer->setTransferTime(new \DateTimeImmutable());
        $transfer->setTransferReason('测试转账');

        $manager->persist($transfer);
        $manager->flush();
    }
}
