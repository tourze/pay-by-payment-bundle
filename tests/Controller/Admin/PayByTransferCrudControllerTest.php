<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Controller\Admin;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\PayByPaymentBundle\Controller\Admin\PayByTransferCrudController;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByTransfer;
use Tourze\PayByPaymentBundle\Enum\PayByTransferStatus;
use Tourze\PayByPaymentBundle\Enum\PayByTransferType;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(PayByTransferCrudController::class)]
#[RunTestsInSeparateProcesses]
class PayByTransferCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function afterEasyAdminSetUp(): void
    {
        // 确保测试数据存在
        $container = self::getContainer();
        /** @var ObjectManager $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');

        // 检查是否已有转账数据
        $transferRepository = $entityManager->getRepository(PayByTransfer::class);
        $existingTransfers = method_exists($transferRepository, 'count') ? $transferRepository->count() : count($transferRepository->findAll());
        if (0 === $existingTransfers) {
            // 创建转账记录
            $transfer = new PayByTransfer();
            $transfer->setTransferId('TEST_TRANSFER_001');
            $transfer->setMerchantTransferNo('MERCHANT_TRANSFER_001');
            $transfer->setTransferType(PayByTransferType::INTERNAL);
            $transfer->setFromAccount('test_from_account');
            $transfer->setToAccount('test_to_account');
            $transfer->setTransferAmount(new PayByAmount('100.00', 'AED'));
            $transfer->setStatus(PayByTransferStatus::PENDING);
            $transfer->setTransferReason('Test transfer reason');

            $entityManager->persist($transfer);
            $entityManager->flush();
        }
    }

    protected function getControllerService(): PayByTransferCrudController
    {
        return new PayByTransferCrudController();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield 'PayBy转账ID' => ['PayBy转账ID'];
        yield '商户转账号' => ['商户转账号'];
        yield '转账金额' => ['转账金额'];
        yield '转账类型' => ['转账类型'];
        yield '转出账户' => ['转出账户'];
        yield '转入账户' => ['转入账户'];
        yield '转账状态' => ['转账状态'];
        yield '转账原因' => ['转账原因'];
        yield '通知地址' => ['通知地址'];
        yield '转账时间' => ['转账时间'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'transferId' => ['transferId'];
        yield 'merchantTransferNo' => ['merchantTransferNo'];
        yield 'amount' => ['amount'];
        yield 'currency' => ['currency'];
        yield 'transferType' => ['transferType'];
        yield 'fromAccount' => ['fromAccount'];
        yield 'toAccount' => ['toAccount'];
        yield 'status' => ['status'];
        yield 'transferReason' => ['transferReason'];
        yield 'notifyUrl' => ['notifyUrl'];
        yield 'bankTransferInfo' => ['bankTransferInfo'];
        yield 'accessoryContent' => ['accessoryContent'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'transferId' => ['transferId'];
        yield 'merchantTransferNo' => ['merchantTransferNo'];
        yield 'amount' => ['amount'];
        yield 'currency' => ['currency'];
        yield 'transferType' => ['transferType'];
        yield 'fromAccount' => ['fromAccount'];
        yield 'toAccount' => ['toAccount'];
        yield 'status' => ['status'];
        yield 'transferReason' => ['transferReason'];
        yield 'notifyUrl' => ['notifyUrl'];
        yield 'bankTransferInfo' => ['bankTransferInfo'];
        yield 'accessoryContent' => ['accessoryContent'];
    }

    public function testEntityFqcn(): void
    {
        self::assertSame(PayByTransfer::class, PayByTransferCrudController::getEntityFqcn());
    }

    public function testValidationErrors(): void
    {
        $validator = self::getService(ValidatorInterface::class);
        self::assertInstanceOf(ValidatorInterface::class, $validator);

        $entity = new PayByTransfer();
        $violations = $validator->validate($entity);

        self::assertGreaterThan(0, $violations->count());

        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[] = $violation->getMessage();
        }

        self::assertContains('This value should not be blank.', $violationMessages);
    }
}
